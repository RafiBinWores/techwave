<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory,SoftDeletes;

    protected $guarded = ['id'];

    protected $attributes = ['pricing_enabled' => false, 'gallery' => '[]', 'tutorials' => '[]', 'installation_steps' => '[]', 'limit_definitions' => '[]'];

    protected function casts(): array
    {
        return ['gallery' => 'array', 'tutorials' => 'array', 'installation_steps' => 'array', 'limit_definitions' => 'array', 'is_published' => 'boolean', 'pricing_enabled' => 'boolean', 'show_in_navbar' => 'boolean', 'sort_order' => 'integer', 'release_size' => 'integer'];
    }

    public function plans(): HasMany
    {
        return $this->hasMany(ProductPlan::class)->orderBy('sort_order')->orderBy('id');
    }

    public function publishedPlans(): HasMany
    {
        return $this->plans()->where('is_active', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
