<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable('name', 'logo', 'website_url', 'sort_order', 'is_active')]
class CompanyLogo extends Model
{
         protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
}
