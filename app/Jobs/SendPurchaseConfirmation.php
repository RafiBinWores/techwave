<?php

namespace App\Jobs;

use App\Events\BookingCreated;
use App\Events\ToolSubscriptionUpdated;
use App\Mail\OrderConfirmedMail;
use App\Mail\ToolSubscriptionConfirmedMail;
use App\Models\Booking;
use App\Models\CheckoutPayment;
use App\Models\ProductPurchase;
use App\Models\ToolSubscription;
use App\Services\UserNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendPurchaseConfirmation implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $paymentId) {}

    public function handle(): void
    {
        $payment = CheckoutPayment::findOrFail($this->paymentId);
        $purchase = $payment->payable;
        if ($payment->status !== 'paid') {
            return;
        }
        if ($payment->needs_review) {
            UserNotificationService::notifyUser($payment->user_id, 'Payment received — review required',
                'Your payment '.$payment->transaction_id.' was verified. Please contact our team to review this purchase.',
                from: 'Support Team', url: route('purchases.show', $payment), type: 'payment');
            if ($purchase instanceof ToolSubscription) {
                ToolSubscriptionUpdated::dispatch($purchase, 'user');
            } elseif ($purchase instanceof Booking) {
                BookingCreated::dispatch($purchase);
            }
            Mail::raw('Your SSLCommerz payment '.$payment->transaction_id.' for '.$payment->currency.' '.$payment->amount
                .' was received. Our team needs to review this purchase before activation. Please contact support.',
                fn ($message) => $message->to($payment->customer['email'])->subject('Payment received — review required'));

            return;
        }
        if ($purchase instanceof ProductPurchase) {
            SendProductPurchaseConfirmation::dispatch($purchase->id);
        } elseif ($purchase instanceof ToolSubscription) {
            ToolSubscriptionUpdated::dispatch($purchase);
            UserNotificationService::notifyUser($purchase->user_id, 'Subscription Confirmed',
                'Your tool subscription is active. Your invoice is available in My Subscriptions.',
                from: 'Support Team', url: route('account.tool-subscriptions'), type: 'subscription');
            Mail::to($payment->customer['email'])->send(new ToolSubscriptionConfirmedMail($purchase, $purchase->invoices()->latest('id')->first()));
        } elseif ($purchase instanceof Booking) {
            BookingCreated::dispatch($purchase);
            $order = $purchase->order()->firstOrFail();
            UserNotificationService::notifyUser($order->user_id, 'Order Confirmed',
                'Your order '.$order->order_no.' is paid and active. Your invoice is available in My Invoices.',
                from: 'Support Team', url: route('account.invoices'), type: 'order');
            $email = $order->email ?: $order->user?->email;
            if ($email) {
                Mail::to($email)->send(new OrderConfirmedMail($order));
            }
        }
    }
}
