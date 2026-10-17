<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Order;
use App\Models\SiteSetting;
use App\Models\ToolSubscription;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServiceRenewalManualPayment
{
    public function submit(Order $renewal, array $details): void
    {
        try {
            DB::transaction(function () use ($renewal, $details): void {
                $root = Order::query()->lockForUpdate()->findOrFail($renewal->renewal_of_id);
                $order = Order::query()->lockForUpdate()->findOrFail($renewal->id);
                if ($order->order_type !== 'service' || $order->payment_status === 'paid'
                    || $order->status === 'cancelled' || $root->status === 'cancelled'
                    || (float) $order->amount <= 0 || $order->currency !== 'BDT'
                    || blank(SiteSetting::current()->bkash_number)) {
                    throw ValidationException::withMessages(['payment' => 'Manual payment is unavailable for this renewal. Please contact the team.']);
                }
                $sender = preg_replace('/^(?:\+88|88)/', '', $details['sender_bkash']);
                $transaction = strtoupper(trim($details['transaction_id']));
                if ($order->renewal_manual_submitted_at) {
                    if ($order->renewal_sender_bkash === $sender && $order->renewal_manual_transaction_id === $transaction) {
                        return;
                    }
                    throw ValidationException::withMessages(['payment' => 'Payment details are already awaiting verification. Contact the team to make a correction.']);
                }
                if (collect($order->renewal_payment_attempts ?? [])->contains('status', 'pending')) {
                    throw ValidationException::withMessages(['payment' => 'Finish or cancel your online payment session before submitting manual payment.']);
                }
                if (Order::query()->where('renewal_manual_transaction_id', $transaction)->exists()
                    || Booking::query()->whereRaw('UPPER(TRIM(transaction_id)) = ?', [$transaction])->exists()
                    || ToolSubscription::query()->whereRaw('UPPER(TRIM(transaction_id)) = ?', [$transaction])->exists()) {
                    throw ValidationException::withMessages(['transaction_id' => 'This transaction ID has already been submitted.']);
                }
                $order->forceFill([
                    'full_name' => $details['customer_name'], 'email' => $details['customer_email'], 'phone' => $details['customer_phone'],
                    'renewal_sender_bkash' => $sender, 'renewal_manual_transaction_id' => $transaction,
                    'renewal_manual_submitted_at' => now(),
                ])->save();
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages(['transaction_id' => 'This transaction ID has already been submitted.']);
        }
    }

    public function confirm(Order $renewal): void
    {
        DB::transaction(function () use ($renewal): void {
            Order::query()->lockForUpdate()->findOrFail($renewal->renewal_of_id);
            $order = Order::query()->lockForUpdate()->findOrFail($renewal->id);
            if ($order->payment_status === 'paid' || $order->renewal_manual_verified_at !== null) {
                return;
            }
            if (! $order->renewal_manual_submitted_at || ! $order->renewal_manual_transaction_id || ! $order->renewal_sender_bkash) {
                throw ValidationException::withMessages(['payment_status' => 'No manual payment details have been submitted.']);
            }
            $order->setPaymentStatus('paid');
            $order->forceFill(['renewal_manual_verified_at' => now(), 'renewal_paid_at' => now()])->save();
            app(ServiceRenewalNotifications::class)->queue($order, 'confirmed');
        });
        $renewal->refresh();
    }
}
