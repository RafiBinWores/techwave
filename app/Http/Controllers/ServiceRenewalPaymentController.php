<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\ServiceRenewalManualPayment;
use App\Services\ServiceRenewalPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class ServiceRenewalPaymentController extends Controller
{
    public function pay(Request $request, Order $order, ServiceRenewalPayment $payments): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id && $order->order_type === 'service' && $order->renewal_of_id, 404);
        if ($order->payment_status === 'paid') {
            return redirect()->route('client.renewals.checkout', $order);
        }
        $customer = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_address' => ['required', 'string', 'max:500'],
        ]);

        $method = $request->validate(['payment_method' => ['sometimes', 'required', 'in:sslcommerz,manual']]);
        if (($method['payment_method'] ?? 'sslcommerz') === 'manual') {
            $proof = $request->validate([
                'sender_bkash' => ['required', 'string', 'max:20', 'regex:/^(?:\+88|88)?01[3-9][0-9]{8}$/D'],
                'transaction_id' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9]+$/D'],
            ]);
            app(ServiceRenewalManualPayment::class)->submit($order, $customer + $proof);

            return redirect()->route('client.renewals.checkout', $order)
                ->with('success', 'Payment details submitted. Your renewal will activate after the team verifies your payment.');
        }

        return redirect()->away($payments->start($order, $customer));
    }

    public function success(Request $request, Order $order, ServiceRenewalPayment $payments): RedirectResponse
    {
        $valid = $this->verify($request, $order, $payments) && $order->fresh()->payment_status === 'paid';

        return redirect()->route('client.renewals.checkout', $order)
            ->with($valid ? 'success' : 'error', $valid ? 'Payment verified. Your renewal period is confirmed.' : 'Payment has not been verified. Your renewal remains unpaid.');
    }

    public function ipn(Request $request, Order $order, ServiceRenewalPayment $payments): Response
    {
        return $this->verify($request, $order, $payments)
            ? response('Payment verified', 200) : response('Payment not verified', 422);
    }

    public function fail(Request $request, Order $order, ServiceRenewalPayment $payments): RedirectResponse
    {
        return $this->stopped($request, $order, $payments, 'failed');
    }

    public function cancel(Request $request, Order $order, ServiceRenewalPayment $payments): RedirectResponse
    {
        return $this->stopped($request, $order, $payments, 'cancelled');
    }

    private function verify(Request $request, Order $order, ServiceRenewalPayment $payments): bool
    {
        $transaction = $request->input('tran_id');
        $validationId = $request->input('val_id');
        if (! is_string($transaction) || ! is_string($validationId)) {
            return false;
        }
        try {
            return $payments->verify($order, $transaction, $validationId);
        } catch (ValidationException $exception) {
            return false;
        }
    }

    private function stopped(Request $request, Order $order, ServiceRenewalPayment $payments, string $status): RedirectResponse
    {
        $transaction = $request->input('tran_id');
        if (is_string($transaction)) {
            $payments->stopAttempt($order, $transaction, $status);
        }

        return redirect()->route('client.renewals.checkout', $order)
            ->with('error', 'Payment '.$status.'. You can complete payment below.');
    }
}
