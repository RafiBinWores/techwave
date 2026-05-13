<?php

namespace App\Http\Controllers;

use App\Library\SslCommerz\SslCommerzNotification;
use App\Mail\OrderInvoiceMail;
use App\Models\PricingOrder;
use App\Models\PricingPlan;
use App\Models\PricingPlanBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SslCommerzController extends Controller
{
    public function pay(Request $request, PricingPlan $pricingPlan)
    {
        abort_if($pricingPlan->status !== 'active', 404);

        $userId = Auth::id();

        if ($this->userHasActiveOrPendingPlan($userId, $pricingPlan->id)) {
            return back()
                ->withInput()
                ->withErrors([
                    'pricing_plan' => 'You already have this plan active or pending. You cannot purchase the same plan again until it expires or is completed.',
                ]);
        }

        $validated = $request->validate([
            'billing' => ['required', 'in:monthly,yearly'],

            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^(?:\+88|88)?01[3-9][0-9]{8}$/',
            ],
            'customer_address' => ['required', 'string', 'max:500'],
            'customer_city' => ['required', 'string', 'max:100'],
            'customer_postcode' => ['required', 'string', 'max:20'],

            'company_name' => ['required', 'string', 'max:255'],
            'company_email' => ['required', 'email', 'max:255'],
            'company_phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^(?:\+88|88)?01[3-9][0-9]{8}$/',
            ],

            'requested_price' => ['nullable', 'numeric', 'min:0'],
            'user_note' => ['nullable', 'string', 'max:2000'],
        ], [
            'customer_phone.regex' => 'Please enter a valid Bangladeshi phone number.',
            'company_phone.regex' => 'Please enter a valid Bangladeshi company phone number.',
        ]);

        $subtotal = (float) (
            $validated['billing'] === 'yearly'
            ? $pricingPlan->yearly_price
            : $pricingPlan->monthly_price
        );

        abort_if($subtotal <= 0, 404);

        $taxRate = 0.15;
        $taxAmount = round($subtotal * $taxRate, 2);
        $totalAmount = round($subtotal + $taxAmount, 2);

        $transactionId = 'TW-' . now()->format('Y') . '-' . strtoupper(Str::random(6));

        $order = PricingOrder::query()->create([
            'user_id' => Auth::id(),
            'pricing_plan_id' => $pricingPlan->id,

            'order_no' => 'ORD-' . now()->format('Y') . '-' . strtoupper(Str::random(5)),
            'transaction_id' => $transactionId,

            'billing_cycle' => $validated['billing'],

            // Better to save subtotal, tax, and final amount separately
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate * 100,
            'tax_amount' => $taxAmount,
            'amount' => $totalAmount,

            'currency' => 'BDT',
            'payment_status' => 'pending',

            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'],

            'customer_address' => $validated['customer_address'],
            'customer_city' => $validated['customer_city'],
            'customer_postcode' => $validated['customer_postcode'],
        ]);

        $order->update([
            'order_no' => 'ORD-' . now()->format('Y') . '-' . $order->id,
        ]);

        $postData = [
            'total_amount' => $order->amount,
            'currency' => $order->currency,
            'tran_id' => $order->transaction_id,

            'success_url' => route('sslcommerz.success'),
            'fail_url' => route('sslcommerz.fail'),
            'cancel_url' => route('sslcommerz.cancel'),
            'ipn_url' => route('sslcommerz.ipn'),

            'cus_name' => $order->customer_name,
            'cus_email' => $order->customer_email,
            'cus_add1' => $order->customer_address,
            'cus_add2' => '',
            'cus_city' => $order->customer_city,
            'cus_state' => $order->customer_city,
            'cus_postcode' => $order->customer_postcode,
            'cus_country' => $order->phone_country === 'BD' ? 'Bangladesh' : $order->phone_country,
            'cus_phone' => $order->phone_e164,

            'shipping_method' => 'NO',
            'num_of_item' => 1,

            'product_name' => $pricingPlan->title,
            'product_category' => 'IT Service Plan',
            'product_profile' => 'non-physical-goods',

            'order_id' => $order->id,
            'pricing_id' => $pricingPlan->id,
            'pricing_cycle' => $validated['billing'],
            'user_id' => Auth::id(),
        ];

        $sslcz = new SslCommerzNotification;

        return $sslcz->makePayment($postData, 'hosted');
    }

    public function success(Request $request)
    {
        // dd($request->all());
        $transactionId = $request->input('tran_id');

        $order = PricingOrder::query()
            ->where('transaction_id', $transactionId)
            ->first();

        if (! $order) {
            return redirect()->route('home')->with('error', 'Order not found.');
        }

        $status = $request->input('status');

        if (in_array($status, ['VALID', 'VALIDATED']) && $order->payment_status !== 'paid') {
            $startsAt = now();

            $expiresAt = $order->billing_cycle === 'yearly'
                ? $startsAt->copy()->addYear()
                : $startsAt->copy()->addMonth();

            $order->update([
                'payment_status'      => 'paid',
                'ssl_status'          => $status,
                'bank_transaction_id' => $request->input('bank_tran_id'),
                'val_id'              => $request->input('val_id'),
                'payment_response'    => $request->all(),
                'paid_at'             => now(),
                'starts_at'           => $startsAt,
                'expires_at'          => $expiresAt,
            ]);

            $order->pricingPlan()->increment('purchase_count');

            $email = $order->user?->email;
            if ($email) {
                Mail::to($email)->send(new OrderInvoiceMail($order));
            }
        }

        // Guard: if still not paid, something went wrong
        if ($order->fresh()->payment_status !== 'paid') {
            return redirect()->route('home')->with('error', 'Payment could not be verified.');
        }

        return redirect()
            ->route('client.checkout.success', $order->id)
            ->with('success', 'Payment completed successfully.');
    }

    public function fail(Request $request)
    {
        return $this->markFailed($request, 'failed');
    }

    public function cancel(Request $request)
    {
        return $this->markFailed($request, 'cancelled');
    }

    public function ipn(Request $request)
    {
        return $this->handleResponse($request, true);
    }

    private function handleResponse(Request $request, bool $isIpn = false)
    {
        $transactionId = $request->input('tran_id');
        $amount = $request->input('amount');
        $currency = $request->input('currency');

        $order = PricingOrder::query()
            ->where('transaction_id', $transactionId)
            ->first();

        if (! $order) {
            return $isIpn
                ? response('Order not found', 404)
                : redirect()->route('home')->with('error', 'Order not found.');
        }

        $sslcz = new SslCommerzNotification;

        $validation = $sslcz->orderValidate(
            $request->all(),
            $transactionId,
            $amount,
            $currency
        );

        if ($validation === true) {
            if ($order->payment_status !== 'paid') {
                $order->update([
                    'payment_status' => 'paid',
                    'ssl_status' => $request->input('status'),
                    'bank_transaction_id' => $request->input('bank_tran_id'),
                    'val_id' => $request->input('val_id'),
                    'payment_response' => $request->all(),
                    'paid_at' => now(),
                ]);

                $order->pricingPlan()->increment('purchase_count');
            }

            return $isIpn
                ? response('IPN received', 200)
                : redirect()->route('client.checkout.success', $order->id)
                ->with('success', 'Payment completed successfully.');
        }

        $order->update([
            'payment_status' => 'failed',
            'ssl_status' => $request->input('status') ?: 'validation_failed',
            'payment_response' => $request->all(),
        ]);

        return $isIpn
            ? response('Payment validation failed', 400)
            : redirect()->route('client.checkout.pricing', [
                'pricingPlan' => $order->pricing_plan_id,
                'billing' => $order->billing_cycle,
            ])->with('error', 'Payment validation failed.');
    }

    private function markFailed(Request $request, string $status)
    {
        $order = PricingOrder::query()
            ->where('transaction_id', $request->input('tran_id'))
            ->first();

        if ($order) {
            $order->update([
                'payment_status' => $status,
                'ssl_status' => $request->input('status'),
                'payment_response' => $request->all(),
            ]);
        }

        return redirect()
            ->route('home')
            ->with('error', 'Payment ' . $status . '.');
    }

    private function userHasActiveOrPendingPlan(int $userId, int $pricingPlanId): bool
    {
        $hasActiveOrder = PricingOrder::query()
            ->where('user_id', $userId)
            ->where('pricing_plan_id', $pricingPlanId)
            ->where(function ($query) {
                $query
                    // Paid and not expired
                    ->where(function ($subQuery) {
                        $subQuery
                            ->where('payment_status', 'paid')
                            ->whereNotNull('expires_at')
                            ->where('expires_at', '>=', now());
                    })

                    // Payment still pending
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('payment_status', 'pending');
                    });
            })
            ->exists();

        $hasActiveBooking = PricingPlanBooking::query()
            ->where('user_id', $userId)
            ->where('pricing_plan_id', $pricingPlanId)
            ->whereIn('status', [
                'pending',
                'reviewing',
                'quoted',
                'accepted',
            ])
            ->exists();

        return $hasActiveOrder || $hasActiveBooking;
    }
}
