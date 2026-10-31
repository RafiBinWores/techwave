<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductQuoteRequest extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['requested_devices' => 'integer', 'requested_sensors' => 'integer', 'proposed_budget' => 'decimal:2', 'reviewed_by' => 'integer', 'reviewed_at' => 'datetime'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ProductPlan::class, 'product_plan_id')->withTrashed();
    }
}
