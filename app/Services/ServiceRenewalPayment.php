<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ServiceRenewalPayment
{
    public function start(Order $renewal, array $customer): string
    {
        return DB::transaction(function () use ($renewal, $customer): string {
            $root = Order::query()->lockForUpdate()->findOrFail($renewal->renewal_of_id);
            $order = Order::query()->lockForUpdate()->findOrFail($renewal->id);
            if ($order->order_type !== 'service' || $order->payment_status === 'paid' || $order->status === 'cancelled' || $root->status === 'cancelled') {
                throw ValidationException::withMessages(['payment' => 'This renewal is not available for payment.']);
            }
            if ($order->renewal_manual_submitted_at && ! $order->renewal_manual_verified_at) {
                throw ValidationException::withMessages(['payment' => 'Your manual payment is awaiting verification. Contact the team for help.']);
            }
            if ((float) $order->amount <= 0) {
                throw ValidationException::withMessages(['payment' => 'Contact the team to set a valid renewal amount.']);
            }
            $attempts = $order->renewal_payment_attempts ?? [];
            $latest = $attempts === [] ? null : $attempts[array_key_last($attempts)];
            if ($latest && $latest['status'] === 'pending' && $latest['amount'] === $order->amount
                && $latest['currency'] === $order->currency) {
                return $latest['gateway_url'];
            }
            $this->requireCredentials();
            $transaction = 'SR-'.Str::upper(Str::random(24));
            $payload = [
                'store_id' => config('sslcommerz.apiCredentials.store_id'),
                'store_passwd' => config('sslcommerz.apiCredentials.store_password'),
                'total_amount' => $order->amount, 'currency' => $order->currency, 'tran_id' => $transaction,
                'success_url' => route('client.renewals.success', $order),
                'fail_url' => route('client.renewals.fail', $order),
                'cancel_url' => route('client.renewals.cancel', $order),
                'ipn_url' => route('client.renewals.ipn', $order),
                'cus_name' => $customer['customer_name'], 'cus_email' => $customer['customer_email'],
                'cus_phone' => $customer['customer_phone'], 'cus_add1' => $customer['customer_address'],
                'cus_city' => 'Dhaka', 'cus_postcode' => '1200', 'cus_country' => 'Bangladesh',
                'shipping_method' => 'NO', 'num_of_item' => 1,
                'product_name' => $order->plan_name ?: 'Service renewal '.$order->order_no,
                'product_category' => 'Service Renewal', 'product_profile' => 'non-physical-goods',
            ];
            try {
                $response = Http::asForm()->acceptJson()->timeout(20)
                    ->post($this->endpoint('make_payment'), $payload)->throw()->json();
            } catch (ConnectionException|RequestException $exception) {
                throw ValidationException::withMessages(['payment' => 'Checkout could not be opened. Please try again.']);
            }
            $url = is_array($response) ? ($response['GatewayPageURL'] ?? null) : null;
            $host = is_string($url) ? parse_url($url, PHP_URL_HOST) : null;
            if (($response['status'] ?? null) !== 'SUCCESS' || empty($response['sessionkey']) || ! is_string($host)
                || parse_url($url, PHP_URL_SCHEME) !== 'https'
                || ! ($host === 'sslcommerz.com' || str_ends_with($host, '.sslcommerz.com'))) {
                throw ValidationException::withMessages(['payment' => 'The payment gateway is unavailable. Please try again later.']);
            }
            $attempts[] = ['transaction_id' => $transaction, 'session_key' => $response['sessionkey'],
                'gateway_url' => $url, 'amount' => $order->amount, 'currency' => $order->currency,
                'status' => 'pending', 'created_at' => now()->toIso8601String()];
            $order->forceFill(['full_name' => $customer['customer_name'], 'email' => $customer['customer_email'],
                'phone' => $customer['customer_phone'], 'renewal_payment_attempts' => $attempts])->save();

            return $url;
        });
    }

    public function verify(Order $renewal, string $transaction, string $validationId): bool
    {
        $attempt = collect($renewal->renewal_payment_attempts ?? [])->firstWhere('transaction_id', $transaction);
        if (! $renewal->renewal_of_id || ! $attempt || $validationId === '' || strlen($validationId) > 100) {
            return false;
        }
        if ($attempt['status'] === 'paid' && ($attempt['validation_id'] ?? $renewal->renewal_payment_validation_id) === $validationId) {
            return true;
        }
        $this->requireCredentials();
        try {
            $verified = Http::acceptJson()->timeout(20)->get($this->endpoint('order_validate'), [
                'val_id' => $validationId, 'store_id' => config('sslcommerz.apiCredentials.store_id'),
                'store_passwd' => config('sslcommerz.apiCredentials.store_password'), 'format' => 'json', 'v' => 1,
            ])->throw()->json();
        } catch (ConnectionException|RequestException $exception) {
            return false;
        }
        if (! is_array($verified) || ! $this->matches($verified, $renewal, $attempt, $transaction, $validationId)) {
            return false;
        }

        return DB::transaction(function () use ($renewal, $transaction, $validationId, $verified): bool {
            Order::query()->lockForUpdate()->findOrFail($renewal->renewal_of_id);
            $order = Order::query()->lockForUpdate()->findOrFail($renewal->id);
            $attempts = $order->renewal_payment_attempts ?? [];
            $key = collect($attempts)->search(fn ($item) => $item['transaction_id'] === $transaction);
            if ($key === false || ! $this->matches($verified, $order, $attempts[$key], $transaction, $validationId)) {
                return false;
            }
            if ($attempts[$key]['status'] === 'paid') {
                return true;
            }
            if ($order->payment_status !== 'paid') {
                $order->setPaymentStatus('paid');
            }
            $attempts[$key]['status'] = 'paid';
            $attempts[$key]['validation_id'] = $validationId;
            $order->forceFill(['renewal_payment_attempts' => $attempts,
                'renewal_payment_validation_id' => $order->renewal_payment_validation_id ?? $validationId,
                'renewal_paid_at' => $order->renewal_paid_at ?? now()])->save();
            app(ServiceRenewalNotifications::class)->queue($order, 'confirmed');

            return true;
        });
    }

    public function stopAttempt(Order $renewal, string $transaction, string $status): void
    {
        if (! $renewal->renewal_of_id) {
            return;
        }
        DB::transaction(function () use ($renewal, $transaction, $status): void {
            Order::query()->lockForUpdate()->findOrFail($renewal->renewal_of_id);
            $order = Order::query()->lockForUpdate()->findOrFail($renewal->id);
            if ($order->payment_status === 'paid') {
                return;
            }
            $attempts = $order->renewal_payment_attempts ?? [];
            foreach ($attempts as &$attempt) {
                if ($attempt['transaction_id'] === $transaction && $attempt['status'] !== 'paid') {
                    $attempt['status'] = $status;
                }
            }
            unset($attempt);
            $order->forceFill(['renewal_payment_attempts' => $attempts])->save();
        });
    }

    private function matches(array $verified, Order $order, array $attempt, string $transaction, string $validationId): bool
    {
        $currency = $verified['currency_type'] ?? $verified['currency'] ?? null;
        $amount = isset($verified['currency_type']) ? ($verified['currency_amount'] ?? null) : ($verified['amount'] ?? null);

        return $order->order_type === 'service'
            && in_array($verified['status'] ?? null, ['VALID', 'VALIDATED'], true)
            && ($verified['tran_id'] ?? null) === $transaction
            && ($verified['val_id'] ?? null) === $validationId
            && $currency === $order->currency && $attempt['currency'] === $order->currency
            && is_scalar($amount) && preg_match('/^\d+(\.\d{1,2})?$/D', (string) $amount)
            && number_format((float) $amount, 2, '.', '') === $order->amount
            && $attempt['amount'] === $order->amount
            && (string) ($verified['risk_level'] ?? '0') === '0';
    }

    private function requireCredentials(): void
    {
        if (! config('sslcommerz.apiCredentials.store_id') || ! config('sslcommerz.apiCredentials.store_password')) {
            throw ValidationException::withMessages(['payment' => 'Online payment is unavailable. Please contact the team.']);
        }
    }

    private function endpoint(string $key): string
    {
        return rtrim(config('sslcommerz.apiDomain'), '/').config('sslcommerz.apiUrl.'.$key);
    }
}
