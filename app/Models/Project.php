<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'category_id',
    'title',
    'slug',
    'client_name',
    'client_place',
    'project_type',
    'thumbnail',
    'short_description',
    'overview',
    'technologies',
    'live_url',
    'case_study_url',
    'completed_at',
    'is_featured',
    'is_active',
    'meta_title',
    'meta_description',
    'meta_keywords',
])]
class Project extends Model
{
    protected $casts = [
        'technologies' => 'array',
        'completed_at' => 'date',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
