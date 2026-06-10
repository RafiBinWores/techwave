<?php

namespace App\Models;

use Database\Factories\LiveTvChannelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'url',
    'category',
    'icon',
    'is_active',
    'sort_order',
])]
class LiveTvChannel extends Model
{
    /** @use HasFactory<LiveTvChannelFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
