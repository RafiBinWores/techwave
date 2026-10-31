<?php

namespace App\Jobs;

use App\Mail\ProductConfirmedMail;
use App\Models\ProductPurchase;
use App\Services\UserNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendProductPurchaseConfirmation implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $purchaseId) {}

    public function handle(): void
    {
        $purchase = ProductPurchase::findOrFail($this->purchaseId);
        if ($purchase->status !== 'active') {
            return;
        }
        UserNotificationService::notifyUser($purchase->user_id, $purchase->renewal_of_id ? 'Product subscription renewed' : 'Product purchase confirmed',
            $purchase->product_name.' — '.$purchase->plan_name.' is active. Your invoice is available in My Invoices.',
            from: 'Support Team', url: route('account.products'), type: 'order');
        Mail::to($purchase->customer['email'])->send(new ProductConfirmedMail($purchase));
    }
}
