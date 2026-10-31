<?php

namespace App\Services\Products;

use App\Events\ProductPurchaseUpdated;
use App\Jobs\SendProductPurchaseConfirmation;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPurchase;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\NetworkLicensing;
use App\Services\SslCommerzSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductPurchases
{
    public function offer(string $slug, int $id, string $cycle): ProductPlan
    {
        $product = Product::published()->where('pricing_enabled', true)->where('slug', $slug)->firstOrFail();
        $plan = $product->publishedPlans()->findOrFail($id);
        abort_unless(in_array($cycle, ['monthly', 'yearly', 'one_time'], true)
            && $plan->effectivePricing()[$cycle] !== null, 404);

        return $plan->setRelation('product', $product);
    }

    public function renewalSource(ProductPlan $plan, int $id, ?int $userId = null): ProductPurchase
    {
        return ProductPurchase::where('user_id', $userId ?? auth()->id())->where('product_id', $plan->product_id)
            ->where('product_plan_id', $plan->id)->whereIn('billing_cycle', ['monthly', 'yearly'])
            ->whereIn('status', ['active', 'expired'])->whereNotNull('invoice_id')->whereNotNull('verified_at')
            ->whereNotNull('expires_at')->where('expires_at', '<=', now()->addDays(7))->findOrFail($id);
    }

    private function sourceForPayment(ProductPurchase $purchase, bool $lock = false): ?ProductPurchase
    {
        if (! $purchase->renewal_of_id) {
            return null;
        }

        return ProductPurchase::whereKey($purchase->renewal_of_id)->where('user_id', $purchase->user_id)
            ->where('product_id', $purchase->product_id)->where('product_plan_id', $purchase->product_plan_id)
            ->whereIn('billing_cycle', ['monthly', 'yearly'])->whereIn('status', ['active', 'expired'])
            ->whereNotNull('invoice_id')->whereNotNull('verified_at')->whereNotNull('expires_at')
            ->when($lock, fn ($query) => $query->lockForUpdate())->first();
    }

    public function assertPayable(ProductPurchase $purchase): void
    {
        abort_if($purchase->trashed(), 404);
        $product = $purchase->product;
        abort_unless($product && ! $product->trashed() && $purchase->product_plan_id, 404);
        $plan = $this->offer($product->slug, $purchase->product_plan_id, $purchase->billing_cycle);
        if ($purchase->renewal_of_id && ! $this->sourceForPayment($purchase)) {
            throw ValidationException::withMessages(['payment' => 'This subscription can no longer be renewed. Please contact support.']);
        }
        if ($plan->effectivePricing()[$purchase->billing_cycle] !== $purchase->amount) {
            throw ValidationException::withMessages(['payment' => 'The plan price changed. Please contact support before paying this order.']);
        }
    }

    public function overlapping(ProductPurchase $purchase): bool
    {
        if ($purchase->renewal_of_id && ! $this->sourceForPayment($purchase)) {
            return true;
        }

        return ProductPurchase::where('user_id', $purchase->user_id)->where('product_id', $purchase->product_id)
            ->when($purchase->renewal_of_id, fn ($query) => $query->whereKeyNot($purchase->renewal_of_id))
            ->whereKeyNot($purchase->id)->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))->exists();
    }

    public function create(ProductPlan $plan, string $cycle, string $method, array $customer, ?string $sender = null, ?string $transaction = null, ?string $expectedAmount = null, ?int $renewalOf = null): ProductPurchase
    {
        abort_unless(auth()->check(), 403);
        if ($method === 'manual') {
            SiteSetting::current()->requireBkashAvailable();
        } elseif ($method === 'sslcommerz') {
            app(SslCommerzSettings::class)->requireAvailable();
        } else {
            throw ValidationException::withMessages(['paymentMethod' => 'Choose an available payment method.']);
        }

        return DB::transaction(function () use ($plan, $cycle, $method, $customer, $sender, $transaction, $expectedAmount, $renewalOf): ProductPurchase {
            User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
            $product = Product::whereKey($plan->product_id)->lockForUpdate()->firstOrFail();
            $plan = $this->offer($product->slug, $plan->id, $cycle);
            if ($expectedAmount !== null && $expectedAmount !== $plan->effectivePricing()[$cycle]) {
                throw ValidationException::withMessages(['payment' => 'The plan price changed. Refresh checkout and review the new total.']);
            }
            $source = $renewalOf ? $this->renewalSource($plan, $renewalOf) : null;
            if ($source && $cycle === 'one_time') {
                throw ValidationException::withMessages(['payment' => 'Subscriptions can be renewed monthly or yearly.']);
            }
            $existing = ProductPurchase::where('user_id', auth()->id())->where('product_id', $product->id)
                ->when($source, fn ($query) => $query->whereKeyNot($source->id))
                ->where(fn ($query) => $query->where('status', 'pending')->orWhere(fn ($query) => $query->where('status', 'active')
                    ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))))
                ->lockForUpdate()->first();
            if ($existing) {
                if ($method === 'sslcommerz' && $existing->status === 'pending' && $existing->payment_method === $method
                    && $existing->product_plan_id === $plan->id && $existing->billing_cycle === $cycle
                    && $existing->amount === $plan->effectivePricing()[$cycle] && $existing->renewal_of_id === $renewalOf) {
                    return $existing;
                }
                throw ValidationException::withMessages(['payment' => 'You already have an active or pending purchase for this product. Check My Products before purchasing again.']);
            }
            if ($method === 'manual') {
                $transaction = strtoupper(trim($transaction ?? ''));
                validator(['sender_bkash' => $sender, 'transaction_id' => $transaction], [
                    'sender_bkash' => ['required', 'regex:/^(?:\+88|88)?01[3-9][0-9]{8}$/D'],
                    'transaction_id' => ['required', 'string', 'max:50', 'unique:product_purchases,transaction_id'],
                ])->validate();
            }

            // Snapshot the purchased offer; future catalog edits must not rewrite an existing order.
            $purchase = ProductPurchase::create([
                'user_id' => auth()->id(), 'product_id' => $product->id, 'product_plan_id' => $plan->id,
                'renewal_of_id' => $source?->id,
                'product_name' => $product->name, 'plan_name' => $plan->name, 'billing_cycle' => $cycle,
                'amount' => $plan->effectivePricing()[$cycle], 'currency' => 'BDT',
                'features' => $plan->features ?? [], 'limits' => $plan->limits ?? [], 'customer' => $customer,
                'payment_method' => $method, 'sender_bkash' => $method === 'manual' ? $sender : null,
                'transaction_id' => $method === 'manual' ? $transaction : null, 'status' => 'pending',
            ]);
            ProductPurchaseUpdated::dispatch($purchase);

            return $purchase;
        });
    }

    /** Activation and invoicing run inside the caller's locked transaction. */
    public function activate(ProductPurchase $purchase, string $transaction): void
    {
        if ($purchase->status === 'active' || $purchase->invoice_id) {
            return;
        }
        $now = now();
        $source = $this->sourceForPayment($purchase, true);
        if ($purchase->renewal_of_id && ! $source) {
            throw ValidationException::withMessages(['payment' => 'The original subscription is no longer available for renewal.']);
        }
        // Extend from the current expiry for early renewal, otherwise from verified payment time.
        $continues = $source && $source->expires_at->greaterThan($now);
        $periodBase = $continues ? $source->expires_at->copy() : $now->copy();
        $startsAt = $continues ? ($source->starts_at ?? $now) : $now;
        $invoice = Invoice::create([
            'user_id' => $purchase->user_id, 'invoice_no' => Invoice::generateInvoiceNumber(), 'currency' => 'BDT',
            'customer_name' => $purchase->customer['name'], 'customer_email' => $purchase->customer['email'],
            'customer_phone' => $purchase->customer['phone'], 'subject' => $purchase->product_name.' — '.$purchase->plan_name,
            'status' => 'paid', 'issue_date' => today(), 'sent_at' => $now,
        ]);
        $invoice->items()->create([
            'item_type' => 'custom', 'item_id' => $purchase->id, 'title' => $purchase->product_name.' — '.$purchase->plan_name,
            'description' => ($source ? 'Renewal · ' : '').ucfirst(str_replace('_', '-', $purchase->billing_cycle)).' · Payment: '.$transaction,
            'quantity' => 1, 'unit_price' => $purchase->amount,
        ]);
        $purchase->update([
            'status' => 'active', 'verified_at' => $now, 'admin_read_at' => null, 'starts_at' => $startsAt,
            'expires_at' => match ($purchase->billing_cycle) {
                'monthly' => $periodBase->copy()->addMonthNoOverflow(),
                'yearly' => $periodBase->copy()->addYearNoOverflow(),
                default => null,
            },
            'transaction_id' => $transaction, 'invoice_id' => $invoice->id,
        ]);
        if ($source) {
            $source->update(['status' => 'renewed']);
        }
        app(NetworkLicensing::class)->syncPurchase($purchase->fresh());
        ProductPurchaseUpdated::dispatch($purchase->fresh());
    }

    public function confirmManual(int $id): void
    {
        ProductContent::authorizeAdmin();
        DB::transaction(function () use ($id): void {
            $initial = ProductPurchase::findOrFail($id);
            User::whereKey($initial->user_id)->lockForUpdate()->firstOrFail();
            $purchase = ProductPurchase::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless($purchase->payment_method === 'manual', 422);
            if ($purchase->status === 'active') {
                return;
            }
            if ($purchase->status !== 'pending' || $this->overlapping($purchase)) {
                throw ValidationException::withMessages(['payment' => 'This purchase cannot be activated. Review its current status and existing purchases.']);
            }
            $this->activate($purchase, $purchase->transaction_id);
            SendProductPurchaseConfirmation::dispatch($purchase->id)->afterCommit();
        });
    }
}
