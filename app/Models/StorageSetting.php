<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'render_source',
    's3_key',
    's3_secret',
    's3_region',
    's3_bucket',
    's3_endpoint',
    's3_url',
    's3_path_style',
    'r2_key',
    'r2_secret',
    'r2_region',
    'r2_bucket',
    'r2_endpoint',
    'r2_url',
    'r2_path_style',
])]
class StorageSetting extends Model
{
    public const SOURCE_S3 = 's3';

    public const SOURCE_R2 = 'r2';

    protected static ?self $currentSetting = null;

    public static function current(): self
    {
        if (static::$currentSetting instanceof self) {
            return static::$currentSetting;
        }

        static::$currentSetting = static::query()->firstOrCreate(['id' => 1]);

        return static::$currentSetting;
    }

    /**
     * Read-only lookup used by the dual storage driver. Returns null when the
     * settings row (or its table) does not exist yet, so the driver can fall
     * back to the legacy disk before migrations or first configuration.
     */
    public static function resolve(): ?self
    {
        if (static::$currentSetting instanceof self) {
            return static::$currentSetting;
        }

        try {
            static::$currentSetting = static::query()->first();
        } catch (\Throwable) {
            return null;
        }

        return static::$currentSetting;
    }

    public static function forgetCurrent(): void
    {
        static::$currentSetting = null;
    }

    public static function s3ConfiguredFrom(array $attributes): bool
    {
        return filled($attributes['s3_key'] ?? null)
            && filled($attributes['s3_secret'] ?? null)
            && filled($attributes['s3_region'] ?? null)
            && filled($attributes['s3_bucket'] ?? null);
    }

    public static function r2ConfiguredFrom(array $attributes): bool
    {
        return filled($attributes['r2_key'] ?? null)
            && filled($attributes['r2_secret'] ?? null)
            && filled($attributes['r2_bucket'] ?? null)
            && filled($attributes['r2_endpoint'] ?? null);
    }

    public function renderSource(): string
    {
        return in_array($this->render_source, [self::SOURCE_S3, self::SOURCE_R2], true)
            ? $this->render_source
            : self::SOURCE_S3;
    }

    public function isS3Configured(): bool
    {
        return static::s3ConfiguredFrom([
            's3_key' => $this->s3_key,
            's3_secret' => $this->s3_secret,
            's3_region' => $this->s3_region,
            's3_bucket' => $this->s3_bucket,
        ]);
    }

    public function isR2Configured(): bool
    {
        return static::r2ConfiguredFrom([
            'r2_key' => $this->r2_key,
            'r2_secret' => $this->r2_secret,
            'r2_bucket' => $this->r2_bucket,
            'r2_endpoint' => $this->r2_endpoint,
        ]);
    }

    public function s3Config(): array
    {
        return [
            'driver' => 's3',
            'key' => $this->s3_key,
            'secret' => $this->s3_secret,
            'region' => $this->s3_region,
            'bucket' => $this->s3_bucket,
            'endpoint' => filled($this->s3_endpoint) ? $this->s3_endpoint : null,
            'url' => filled($this->s3_url) ? $this->s3_url : null,
            'use_path_style_endpoint' => (bool) $this->s3_path_style,
            'throw' => true,
            'report' => false,
        ];
    }

    public function r2Config(): array
    {
        return [
            'driver' => 's3',
            'key' => $this->r2_key,
            'secret' => $this->r2_secret,
            // Cloudflare R2 only signs correctly with the literal lowercase region "auto".
            'region' => filled($this->r2_region) ? strtolower(trim($this->r2_region)) : 'auto',
            'bucket' => $this->r2_bucket,
            'endpoint' => $this->r2_endpoint,
            'url' => filled($this->r2_url) ? $this->r2_url : null,
            'use_path_style_endpoint' => (bool) $this->r2_path_style,
            'throw' => true,
            'report' => false,
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (): void {
            static::forgetCurrent();
            Storage::forgetDisk(['public', 'documents']);
        });

        static::deleted(function (): void {
            static::forgetCurrent();
            Storage::forgetDisk(['public', 'documents']);
        });
    }

    protected function casts(): array
    {
        return [
            's3_secret' => 'encrypted',
            'r2_secret' => 'encrypted',
            's3_path_style' => 'boolean',
            'r2_path_style' => 'boolean',
        ];
    }
}
