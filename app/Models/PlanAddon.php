<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'price',
    'monthly_price',
    'yearly_price',
    'is_active',
])]
class PlanAddon extends Model
{
    protected $casts = [
        'price' => 'decimal:2',
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function servicePlans()
    {
        return $this->belongsToMany(ServicePlan::class, 'addon_service_plan')
            ->withPivot(['price', 'monthly_price', 'yearly_price', 'sort_order'])
            ->withTimestamps();
    }
}
