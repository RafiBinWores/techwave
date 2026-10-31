<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductPlan extends Model
{
    use HasFactory,SoftDeletes;

    protected $guarded = ['id'];

    protected $attributes = ['features' => '[]', 'limits' => '[]'];

    protected function casts(): array
    {
        return ['product_id' => 'integer', 'tool_plan_id' => 'integer', 'features' => 'array', 'limits' => 'array', 'is_active' => 'boolean', 'sort_order' => 'integer', 'monthly_price' => 'decimal:2', 'yearly_price' => 'decimal:2', 'one_time_price' => 'decimal:2'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function toolPlan(): BelongsTo
    {
        return $this->belongsTo(ToolPlan::class);
    }

    /** @return array<string, string|null> */
    public function effectivePricing(): array
    {
        $source = $this;
        $positive = fn (?string $value): ?string => $value !== null && preg_match('/[1-9]/', $value) ? $value : null;

        return ['monthly' => $this->pricing_mode === 'subscription' ? $positive($source?->monthly_price) : null, 'yearly' => $this->pricing_mode === 'subscription' ? $positive($source?->yearly_price) : null, 'one_time' => $this->pricing_mode === 'one_time' ? $positive($this->one_time_price) : null];
    }

    /** @return array<string, array<string, mixed>> */
    public function effectiveLimits(): array
    {
        $values = $this->limits ?? [];
        $out = [];
        foreach ($this->product?->limit_definitions ?? [] as $definition) {
            $key = $definition['key'];
            if (isset($values[$key])) {
                $out[$key] = [...$definition, ...$values[$key]];
            }
        }

        return $out;
    }
}
