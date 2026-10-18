<?php

namespace App\Models;

use App\Services\UploadStorage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'site_name',
    'logo',
    'favicon',
    'email',
    'phone',
    'location',
    'map_embed_link',
    'facebook_url',
    'linkedin_url',
    'twitter_url',
    'instagram_url',
    'youtube_url',
    'github_url',
    'whatsapp_url',
    'terms_conditions',
    'privacy_policy',
    'bkash_number',
    'bkash_enabled',
    'bkash_instructions',
    'sslcommerz_enabled',
    'sslcommerz_store_id',
    'sslcommerz_store_password',
    'sslcommerz_sandbox',
    'live_tv_enabled',
])]
class SiteSetting extends Model
{
    protected $hidden = ['sslcommerz_store_password'];

    protected static ?self $currentSetting = null;

    public static function current(): self
    {
        if (static::$currentSetting instanceof self) {
            return static::$currentSetting;
        }

        static::$currentSetting = static::query()->firstOrCreate(
            ['id' => 1],
            ['site_name' => config('app.name')]
        );

        return static::$currentSetting;
    }

    public static function forgetCurrent(): void
    {
        static::$currentSetting = null;
    }

    public function bkashAvailable(): bool
    {
        return (bool) $this->bkash_enabled && filled($this->bkash_number);
    }

    public function requireBkashAvailable(): void
    {
        if (! $this->bkashAvailable()) {
            throw ValidationException::withMessages([
                'payment' => 'bKash payments are currently unavailable. Please contact the team.',
            ]);
        }
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (blank($this->logo)) {
            return null;
        }

        if (str_starts_with($this->logo, 'http://') || str_starts_with($this->logo, 'https://')) {
            return $this->logo;
        }

        return UploadStorage::url($this->logo);
    }

    public function getFaviconUrlAttribute(): string
    {
        if (filled($this->favicon)) {
            if (str_starts_with($this->favicon, 'http://') || str_starts_with($this->favicon, 'https://')) {
                return $this->favicon;
            }

            return UploadStorage::url($this->favicon);
        }

        return asset('assets/images/logo/logo.png');
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCurrent());
        static::deleted(fn () => static::forgetCurrent());
    }

    protected function casts(): array
    {
        return [
            'live_tv_enabled' => 'boolean',
            'bkash_enabled' => 'boolean',
            'sslcommerz_enabled' => 'boolean',
            'sslcommerz_sandbox' => 'boolean',
            'sslcommerz_store_password' => 'encrypted',
        ];
    }
}
