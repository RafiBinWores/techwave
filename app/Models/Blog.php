<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'category_id',
    'title',
    'slug',
    'author_name',
    'thumbnail',
    'excerpt',
    'content',
    'tags',
    'is_featured',
    'is_active',
    'published_at',
    'meta_title',
    'meta_description',
    'meta_keywords'
])]
class Blog extends Model
{
    protected $casts = [
        'tags' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
