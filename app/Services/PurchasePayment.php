<?php

namespace App\Services;

use App\Jobs\SendPurchaseConfirmation;
use App\Models\Booking;
use App\Models\CheckoutPayment;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\ProductPurchase;
use App\Models\ToolSubscription;
use App\Models\User;
use App\Services\Products\ProductPurchases;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchasePayment
{
    public function start(Model $payable, array $customer): string
    {
        app(SslCommerzSettings::class)->requireAvailable();
        if (! ($payable instanceof Booking || $payable instanceof ToolSubscription || $payable instanceof ProductPurchase)
            || ! Auth::check() || (int) $payable->user_id !== (int) Auth::id()) {
            throw ValidationException::withMessages(['payment' => 'This purchase is not available for payment.']);
        }

        return DB::transaction(function () use ($payable, $customer): string {
            User::query()->lockForUpdate()->findOrFail($payable->user_id);
            $payable = $payable->newQuery()->when($payable instanceof ProductPurchase, fn ($query) => $query->withTrashed())->lockForUpdate()->findOrFail($payable->id);
            if ($payable instanceof ProductPurchase) {
                app(ProductPurchases::class)->assertPayable($payable);
            }
            $payments = CheckoutPayment::whereMorphedTo('payable', $payable);
            $amount = $payable instanceof Booking ? $payable->final_price : $payable->amount;
            if ($payable->payment_method !== 'sslcommerz' || $payable->status !== 'pending'
                || ($payable instanceof Booking && $payable->booking_type !== 'service')
                || (float) $amount <= 0 || ($payable instanceof Booking && $payable->currency !== 'BDT')
                || (clone $payments)->where('status', 'paid')->exists()) {
                throw ValidationException::withMessages(['payment' => 'This purchase is not available for payment.']);
            }
            $pending = (clone $payments)->where('status', 'pending')->latest('id')->first();
            if ($pending && $pending->amount === number_format((float) $amount, 2, '.', '') && $pending->gateway_url) {
                return $pending->gateway_url;
            }
            $config = app(SslCommerzSettings::class)->configuration();
            $payment = CheckoutPayment::create([
                'payable_type' => $payable->getMorphClass(), 'payable_id' => $payable->id,
                'user_id' => $payable->user_id, 'transaction_id' => 'PUR-'.Str::upper(Str::random(24)),
                'amount' => $amount, 'currency' => 'BDT', 'sandbox' => str_contains($config['apiDomain'], 'sandbox.'),
                'customer' => $customer, 'status' => 'pending',
            ]);
            $payload = [
                'store_id' => $config['apiCredentials']['store_id'], 'store_passwd' => $config['apiCredentials']['store_password'],
                'total_amount' => $payment->amount, 'currency' => 'BDT', 'tran_id' => $payment->transaction_id,
                'success_url' => route('purchases.success', $payment), 'fail_url' => route('purchases.fail', $payment),
                'cancel_url' => route('purchases.cancel', $payment), 'ipn_url' => route('purchases.ipn', $payment),
                'cus_name' => $customer['name'], 'cus_email' => $customer['email'],
                'cus_phone' => $customer['phone'], 'cus_add1' => $customer['address'],
                'cus_city' => 'Dhaka', 'cus_postcode' => '1200', 'cus_country' => 'Bangladesh',
                'shipping_method' => 'NO', 'num_of_item' => 1,
                'product_name' => $payable instanceof Booking ? $payable->plan_name : ($payable instanceof ProductPurchase ? $payable->product_name.' — '.$payable->plan_name : ($payable->toolPlan?->name ?? 'Tool subscription')),
                'product_category' => $payable instanceof Booking ? 'Service' : ($payable instanceof ProductPurchase ? 'Software' : 'Tool Subscription'),
                'product_profile' => 'non-physical-goods',
            ];
            try {
                $response = Http::asForm()->acceptJson()->timeout(20)
                    ->post($config['apiDomain'].$config['apiUrl']['make_payment'], $payload)->throw()->json();
            } catch (ConnectionException|RequestException $exception) {
                throw ValidationException::withMessages(['payment' => 'Checkout could not be opened. Please try again.']);
            }
            $url = is_array($response) ? ($response['GatewayPageURL'] ?? null) : null;
            $host = is_string($url) ? parse_url($url, PHP_URL_HOST) : null;
            if (! is_array($response) || ($response['status'] ?? null) !== 'SUCCESS' || empty($response['sessionkey'])
                || ! is_string($host) || parse_url($url, PHP_URL_SCHEME) !== 'https'
                || ! ($host === 'sslcommerz.com' || str_ends_with($host, '.sslcommerz.com'))) {
                throw ValidationException::withMessages(['payment' => 'The payment gateway is unavailable. Please try again.']);
            }
            $payment->update(['session_key' => $response['sessionkey'], 'gateway_url' => $url]);

            return $url;
        });
    }

    public function verify(CheckoutPayment $payment, string $transaction, string $validationId): bool
    {
        $payment->refresh();
        if ($transaction !== $payment->transaction_id || $validationId === '' || strlen($validationId) > 100) {
            return false;
        }
        if ($payment->status === 'paid') {
            return $payment->validation_id === $validationId;
        }
        $config = app(SslCommerzSettings::class)->configuration();
        if (blank($config['apiCredentials']['store_id']) || blank($config['apiCredentials']['store_password'])) {
            return false;
        }
        $domain = $payment->sandbox ? 'https://sandbox.sslcommerz.com' : 'https://securepay.sslcommerz.com';
        try {
            $verified = Http::acceptJson()->timeout(20)->get($domain.$config['apiUrl']['order_validate'], [
                'val_id' => $validationId, 'store_id' => $config['apiCredentials']['store_id'],
                'store_passwd' => $config['apiCredentials']['store_password'], 'format' => 'json', 'v' => 1,
            ])->throw()->json();
        } catch (ConnectionException|RequestException $exception) {
            return false;
        }
        if (! is_array($verified) || ! $this->matches($payment, $verified, $validationId)) {
            return false;
        }

        return DB::transaction(function () use ($payment, $validationId): bool {
            User::query()->lockForUpdate()->findOrFail($payment->user_id);
            $payable = $payment->payable()->first();
            if (! ($payable instanceof Booking || $payable instanceof ToolSubscription || $payable instanceof ProductPurchase)) {
                return false;
            }
            $payable = $payable->newQuery()->when($payable instanceof ProductPurchase, fn ($query) => $query->withTrashed())->lockForUpdate()->findOrFail($payable->id);
            $payment = CheckoutPayment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($payment->status === 'paid') {
                return $payment->validation_id === $validationId;
            }
            $otherPaid = CheckoutPayment::whereMorphedTo('payable', $payable)->where('status', 'paid')->exists();
            if (CheckoutPayment::where('validation_id', $validationId)->whereKeyNot($payment->id)->exists()) {
                return false;
            }
            $needsReview = $otherPaid
                || ($payable instanceof ProductPurchase && ($payable->trashed() || $payable->status !== 'pending' || app(ProductPurchases::class)->overlapping($payable)))
                || ($payable instanceof Booking && in_array($payable->status, ['cancelled', 'rejected'], true))
                || ($payable instanceof ToolSubscription && ($payable->status !== 'pending'
                    || ToolSubscription::where('user_id', $payable->user_id)->where('tool_category_id', $payable->tool_category_id)
                        ->whereKeyNot($payable->id)->whereIn('status', ['pending', 'active'])
                        ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))->exists()));
            $payment->update(['status' => 'paid', 'validation_id' => $validationId, 'paid_at' => now(), 'needs_review' => $needsReview]);
            if ($needsReview) {
                $payable->update(['admin_note' => trim(($payable->admin_note ?? '')."\nSSLCommerz payment ".$payment->transaction_id.' received; review or refund required.'),
                    'admin_read_at' => null]);
                if ($payable instanceof Booking) {
                    $payable->update(['payment_status' => 'paid']);
                }
                SendPurchaseConfirmation::dispatch($payment->id)->afterCommit();

                return true;
            }
            if ($payable instanceof ProductPurchase) {
                app(ProductPurchases::class)->activate($payable, $payment->transaction_id);
            } elseif ($payable instanceof Booking) {
                $payable->update(['payment_status' => 'paid', 'transaction_id' => $payment->transaction_id]);
                $this->confirmServiceBooking($payable, $payment);
            } else {
                $startsAt = now();
                $payable->update(['status' => 'active', 'verified_at' => $startsAt,
                    'starts_at' => $startsAt, 'expires_at' => $payable->billing_cycle === 'yearly' ? $startsAt->copy()->addYear() : $startsAt->copy()->addMonth(),
                    'transaction_id' => $payment->transaction_id]);
                $invoice = Invoice::create([
                    'user_id' => $payable->user_id, 'tool_subscription_id' => $payable->id,
                    'invoice_no' => Invoice::generateInvoiceNumber(), 'currency' => $payment->currency,
                    'customer_name' => $payment->customer['name'], 'customer_email' => $payment->customer['email'],
                    'customer_phone' => $payment->customer['phone'],
                    'subject' => 'Tool Subscription - '.($payable->toolPlan?->name ?? 'Plan'),
                    'status' => 'paid', 'issue_date' => today(), 'sent_at' => now(),
                ]);
                $invoice->items()->create(['item_type' => 'custom', 'item_id' => $payable->id,
                    'title' => ($payable->toolPlan?->name ?? 'Tool subscription').' ('.ucfirst($payable->billing_cycle).')',
                    'description' => 'SSLCommerz payment: '.$payment->transaction_id, 'quantity' => 1, 'unit_price' => $payment->amount]);
            }
            SendPurchaseConfirmation::dispatch($payment->id)->afterCommit();

            return true;
        });
    }

    private function confirmServiceBooking(Booking $booking, CheckoutPayment $payment): void
    {
        // The booking is already locked by verify(); order creation and payment
        // settlement commit together, including the model's invoice synchronization.
        if (! $booking->order()->exists()) {
            Order::create(array_merge($booking->only([
                'user_id', 'service_id', 'service_plan_id', 'pricing_plan_id', 'billing_cycle',
                'full_name', 'phone', 'email', 'company_name', 'company_phone', 'company_email',
                'plan_name', 'plan_price', 'requested_price', 'quoted_price',
                'message', 'user_note', 'admin_note',
            ]), [
                'booking_id' => $booking->id,
                'order_no' => Invoice::generateInvoiceNumber(),
                'order_type' => 'service',
                'final_price' => $payment->amount,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => 'paid',
                'payment_status' => 'paid',
                'payment_method' => 'sslcommerz',
                'start_date' => today(),
                'end_date' => match ($booking->billing_cycle) {
                    'monthly' => today()->addMonth(),
                    'yearly' => today()->addYear(),
                    default => null,
                },
            ]));
        }
        $booking->update(['status' => 'converted', 'final_price' => $payment->amount, 'admin_read_at' => null]);
    }

    public function stop(CheckoutPayment $payment, string $transaction, string $status): void
    {
        if ($transaction === $payment->transaction_id && in_array($status, ['failed', 'cancelled'], true)) {
            CheckoutPayment::whereKey($payment->id)->where('status', '!=', 'paid')->update(['status' => $status]);
        }
    }

    private function matches(CheckoutPayment $payment, array $verified, string $validationId): bool
    {
        $currency = $verified['currency_type'] ?? $verified['currency'] ?? null;
        $amount = isset($verified['currency_type']) ? ($verified['currency_amount'] ?? null) : ($verified['amount'] ?? null);

        return in_array($verified['status'] ?? null, ['VALID', 'VALIDATED'], true)
            && ($verified['tran_id'] ?? null) === $payment->transaction_id
            && ($verified['val_id'] ?? null) === $validationId && $currency === $payment->currency
            && is_scalar($amount) && preg_match('/^\d+(\.\d{1,2})?$/D', (string) $amount)
            && number_format((float) $amount, 2, '.', '') === $payment->amount
            && (string) ($verified['risk_level'] ?? '0') === '0';
    }
}
