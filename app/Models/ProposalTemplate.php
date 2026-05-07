<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'subject_prefix',
    'title',
    'greeting',
    'intro_text',
    'footer_text',
    'terms_text',
    'brand_color',
    'is_active',
])]
class ProposalTemplate extends Model
{
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function activeTemplate(): self
    {
        $template = static::query()->where('is_active', true)->first();

        if ($template) {
            return $template;
        }

        return static::query()->firstOrCreate([
            'name' => 'Default Template',
        ], [
            'subject_prefix' => 'Proposal',
            'title' => 'Service Proposal',
            'greeting' => 'Dear valued customer,',
            'intro_text' => 'We have prepared a proposal for your selected services.',
            'footer_text' => 'Thank you for choosing us.',
            'terms_text' => 'This proposal is valid until the mentioned validity date.',
            'brand_color' => '#0F52BA',
            'is_active' => true,
        ]);
    }
}
