<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductPurchase extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'renewal_of_id' => 'integer',
            'renewal_reminder_deliveries' => 'array',
            'features' => 'array',
            'limits' => 'array',
            'customer' => 'array',
            'verified_at' => 'datetime',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'admin_read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ProductPlan::class, 'product_plan_id');
    }

    public function renewalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'renewal_of_id');
    }

    public function renewalUrl(): ?string
    {
        if (! in_array($this->displayStatus(), ['active', 'expired'], true) || $this->billing_cycle === 'one_time'
            || ! $this->invoice_id || ! $this->expires_at || $this->expires_at->greaterThan(now()->addDays(7))
            || ! $this->product?->is_published || ! $this->product->pricing_enabled
            || ! $this->plan?->is_active || $this->plan->pricing_mode !== 'subscription') {
            return null;
        }
        $prices = $this->plan->effectivePricing();
        $cycle = $prices[$this->billing_cycle] !== null ? $this->billing_cycle : ($prices['monthly'] !== null ? 'monthly' : 'yearly');
        if ($prices[$cycle] === null) {
            return null;
        }

        return route('client.products.purchase', ['slug' => $this->product->slug, 'plan' => $this->product_plan_id,
            'cycle' => $cycle, 'renew' => $this->id]);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function onlinePayments(): MorphMany
    {
        return $this->morphMany(CheckoutPayment::class, 'payable');
    }

    public function displayStatus(): string
    {
        return $this->status === 'active' && $this->expires_at?->isPast() ? 'expired' : $this->status;
    }
}
