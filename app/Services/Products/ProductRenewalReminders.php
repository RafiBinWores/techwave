<?php

namespace App\Services\Products;

use App\Jobs\SendProductRenewalReminder;
use App\Mail\ProductRenewalReminderMail;
use App\Models\ProductPurchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ProductRenewalReminders
{
    public function queue(ProductPurchase $purchase): bool
    {
        $kind = $this->dueKind($purchase);
        if ($kind === null) {
            return false;
        }
        SendProductRenewalReminder::dispatch($purchase->id, $kind, $purchase->expires_at->toIso8601String())->afterCommit();

        return true;
    }

    public function dueKind(ProductPurchase $purchase): ?string
    {
        if ($purchase->status !== 'active' || ! $purchase->invoice_id || ! $purchase->verified_at || ! $purchase->expires_at
            || ! $purchase->expires_at->isFuture() || $purchase->renewalUrl() === null || $this->recipient($purchase) === null) {
            return null;
        }
        // A completed or pending renewal must not trigger another request to pay.
        $latestId = ProductPurchase::where('user_id', $purchase->user_id)->where('product_id', $purchase->product_id)
            ->whereNotNull('invoice_id')->whereIn('status', ['active', 'expired'])->max('id');
        if ((int) $latestId !== $purchase->id || ProductPurchase::where('user_id', $purchase->user_id)
            ->where('product_id', $purchase->product_id)->where('status', 'pending')->exists()) {
            return null;
        }
        $kind = $purchase->expires_at->lessThanOrEqualTo(now()->addHours(24)) ? 'day' : 'week';
        $period = $purchase->expires_at->toIso8601String();

        return empty($purchase->renewal_reminder_deliveries[$period][$kind]) ? $kind : null;
    }

    public function deliver(int $purchaseId, string $kind, string $period): void
    {
        if (! in_array($kind, ['week', 'day'], true)) {
            return;
        }
        DB::transaction(function () use ($purchaseId, $kind, $period): void {
            $candidate = ProductPurchase::find($purchaseId);
            if (! $candidate || ! User::whereKey($candidate->user_id)->lockForUpdate()->first()) {
                return;
            }
            $purchase = ProductPurchase::with(['user', 'product', 'plan'])->lockForUpdate()->find($purchaseId);
            // Recheck the expiry and payment state at delivery time; queued jobs may be stale.
            if (! $purchase || $purchase->expires_at?->toIso8601String() !== $period || $this->dueKind($purchase) !== $kind) {
                return;
            }
            Mail::to($this->recipient($purchase))->send(new ProductRenewalReminderMail($purchase, $kind, $purchase->renewalUrl()));
            // Mark only after the mail transport accepts the message so failed sends can retry.
            $deliveries = $purchase->renewal_reminder_deliveries ?? [];
            $deliveries[$period][$kind] = now()->toIso8601String();
            $purchase->update(['renewal_reminder_deliveries' => $deliveries]);
        });
    }

    private function recipient(ProductPurchase $purchase): ?string
    {
        $email = $purchase->user?->email ?: ($purchase->customer['email'] ?? null);

        return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
}
