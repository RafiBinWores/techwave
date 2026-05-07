<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['service_id', 'name', 'slug', 'badge', 'description', 'price', 'features', 'buy_url', 'sort_order', 'is_active'])]
class ServicePlan extends Model
{
    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
