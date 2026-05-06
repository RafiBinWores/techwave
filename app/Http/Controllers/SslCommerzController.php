<?php

namespace App\Http\Controllers;

use App\Library\SslCommerz\SslCommerzNotification;
use App\Models\PricingOrder;
use App\Models\PricingPlan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class SslCommerzController extends Controller
{
    public function success(Request $request)
    {
        return $this->handleResponse($request, false);
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

        $sslcz = new SslCommerzNotification();

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

    public function pay(Request $request, PricingPlan $pricingPlan)
{
    abort_if($pricingPlan->status !== 'active', 404);

    $validated = $request->validate([
        'billing' => ['required', 'in:monthly,yearly'],
    ]);

    $amount = (float) (
        $validated['billing'] === 'yearly'
            ? $pricingPlan->yearly_price
            : $pricingPlan->monthly_price
    );

    abort_if($amount <= 0, 404);

    $user = Auth::user();

    $transactionId = 'TW-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6));

    $order = PricingOrder::query()->create([
        'user_id' => Auth::id(),
        'pricing_plan_id' => $pricingPlan->id,
        'order_no' => 'ORD-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(5)),
        'transaction_id' => $transactionId,
        'billing_cycle' => $validated['billing'],
        'amount' => $amount,
        'currency' => 'BDT',
        'payment_status' => 'pending',
    ]);

    $postData = [
        'total_amount' => $order->amount,
        'currency' => $order->currency,
        'tran_id' => $order->transaction_id,

        'success_url' => route('sslcommerz.success'),
        'fail_url' => route('sslcommerz.fail'),
        'cancel_url' => route('sslcommerz.cancel'),
        'ipn_url' => route('sslcommerz.ipn'),

        'cus_name' => $user->name ?? 'Customer',
        'cus_email' => $user->email ?? 'customer@example.com',
        'cus_add1' => 'Dhaka',
        'cus_add2' => 'Bangladesh',
        'cus_city' => 'Dhaka',
        'cus_state' => 'Dhaka',
        'cus_postcode' => '1200',
        'cus_country' => 'Bangladesh',
        'cus_phone' => $user->phone ?? '01700000000',

        'shipping_method' => 'NO',
        'num_of_item' => 1,

        'product_name' => $pricingPlan->title,
        'product_category' => 'IT Service Plan',
        'product_profile' => 'non-physical-goods',

        'value_a' => $order->id,
        'value_b' => $pricingPlan->id,
        'value_c' => $validated['billing'],
        'value_d' => Auth::id(),
    ];

    $sslcz = new SslCommerzNotification();

    return $sslcz->makePayment($postData, 'hosted');
}
}
