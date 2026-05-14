<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'pricing_plan_id',
    'booking_no',
    'billing_cycle',
    'company_name',
    'company_phone',
    'company_email',
    'plan_price',
    'requested_price',
    'quoted_price',
    'user_note',
    'admin_note',
    'status',
    'pricing_order_id',
    'admin_read_at',
])]
class PricingPlanBooking extends Model
{
    protected $casts = [
        'plan_price' => 'decimal:2',
        'requested_price' => 'decimal:2',
        'quoted_price' => 'decimal:2',
        'admin_read_at' => 'datetime',
    ];

    public function pricingPlan()
    {
        return $this->belongsTo(PricingPlan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pricingOrder()
    {
        return $this->belongsTo(PricingOrder::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isQuoted(): bool
    {
        return $this->status === 'quoted';
    }

    public function isConverted(): bool
    {
        return $this->status === 'converted';
    }
}
