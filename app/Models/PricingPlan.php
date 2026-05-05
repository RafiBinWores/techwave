<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['plan_type', 'title', 'icon', 'description', 'monthly_price', 'yearly_price', 'features', 'status', 'purchase_count'])]
class PricingPlan extends Model
{
    protected $casts = [
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'features' => 'array',
        'purchase_count' => 'integer',
    ];
}
