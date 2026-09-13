<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'tool_category_id', 'tool_plan_id', 'billing_cycle', 'amount', 'status', 'starts_at', 'expires_at', 'transaction_id', 'sender_bkash', 'verified_at', 'admin_note', 'admin_read_at'])]
class ToolSubscription extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toolCategory(): BelongsTo
    {
        return $this->belongsTo(ToolCategory::class, 'tool_category_id');
    }

    public function toolPlan(): BelongsTo
    {
        return $this->belongsTo(ToolPlan::class, 'tool_plan_id');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'tool_subscription_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'tool_subscription_id')->latest();
    }

    public function scopeRenewable($query)
    {
        return $query->where(function ($q) {
            $q->whereIn('status', ['expired', 'cancelled'])
                ->orWhere(function ($inner) {
                    $inner->where('status', 'active')
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '<=', now());
                });
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }
}
