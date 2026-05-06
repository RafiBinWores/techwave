<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'pricing_plan_id', 'order_no', 'transaction_id', 'billing_cycle', 'amount', 'currency', 'payment_status', 'ssl_status', 'bank_transaction_id', 'val_id', 'payment_response', 'paid_at'])]
class PricingOrder extends Model
{
    protected $casts = [
        'amount' => 'decimal:2',
        'payment_response' => 'array',
        'paid_at' => 'datetime',
    ];

    public function pricingPlan()
    {
        return $this->belongsTo(PricingPlan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }
}
