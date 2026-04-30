<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'is_active'])]
class Department extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Department $department) {
            if (blank($department->slug)) {
                $department->slug = Str::slug($department->name);
            }
        });

        static::updating(function (Department $department) {
            if ($department->isDirty('name')) {
                $department->slug = Str::slug($department->name);
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
