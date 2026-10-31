<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CheckoutPayment;
use App\Models\ProductPurchase;
use App\Services\PurchasePayment;
use Illuminate\Http\Request;

class PurchasePaymentController extends Controller
{
    public function success(Request $request, CheckoutPayment $payment, PurchasePayment $gateway)
    {
        $valid = $this->verified($request, $payment, $gateway);

        return redirect()->route('purchases.show', $payment)->with($valid ? 'success' : 'error',
            $valid ? 'Payment verified successfully.' : 'Payment could not be verified. Please check its status before paying again.');
    }

    public function ipn(Request $request, CheckoutPayment $payment, PurchasePayment $gateway)
    {
        return $this->verified($request, $payment, $gateway)
            ? response('Payment verified', 200) : response('Payment not verified', 422);
    }

    public function fail(Request $request, CheckoutPayment $payment, PurchasePayment $gateway)
    {
        return $this->stopped($request, $payment, $gateway, 'failed');
    }

    public function cancel(Request $request, CheckoutPayment $payment, PurchasePayment $gateway)
    {
        return $this->stopped($request, $payment, $gateway, 'cancelled');
    }

    public function show(Request $request, CheckoutPayment $payment)
    {
        abort_unless((int) $payment->user_id === (int) $request->user()->id, 404);

        abort_if($payment->payable instanceof ProductPurchase && $payment->payable->trashed(), 404);

        return view('pages.client.checkout.payment-status', ['payment' => $payment->load('payable'),
            'servicePurchase' => $payment->payable instanceof Booking]);
    }

    public function retry(Request $request, CheckoutPayment $payment, PurchasePayment $gateway)
    {
        abort_unless((int) $payment->user_id === (int) $request->user()->id, 404);
        abort_unless($payment->payable, 404);

        return redirect()->away($gateway->start($payment->payable, $payment->customer));
    }

    private function verified(Request $request, CheckoutPayment $payment, PurchasePayment $gateway): bool
    {
        $transaction = $request->input('tran_id');
        $validationId = $request->input('val_id');

        return is_string($transaction) && is_string($validationId)
            && $gateway->verify($payment, $transaction, $validationId);
    }

    private function stopped(Request $request, CheckoutPayment $payment, PurchasePayment $gateway, string $status)
    {
        $transaction = $request->input('tran_id');
        if (is_string($transaction)) {
            $gateway->stop($payment, $transaction, $status);
        }

        return redirect()->route('purchases.show', $payment);
    }
}
