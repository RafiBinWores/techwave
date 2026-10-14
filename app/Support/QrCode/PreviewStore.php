<?php

namespace App\Support\QrCode;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

class PreviewStore
{
    public function store(string $token, string $svg): array
    {
        $version = hash('sha256', $svg);
        $key = 'qr-preview:'.$token;
        $versions = Cache::get($key, []);
        unset($versions[$version]);
        $versions[$version] = $svg;
        Cache::put($key, array_slice($versions, -2, null, true), now()->addHours(2));

        return [
            'version' => $version,
            'url' => URL::temporarySignedRoute('qr.preview', now()->addHours(2), compact('token', 'version')),
        ];
    }

    public function get(string $token, ?string $version): ?string
    {
        return Cache::get('qr-preview:'.$token, [])[$version] ?? null;
    }

    public function putLogo(string $token, array $logo): void
    {
        $logo['contents'] = base64_encode($logo['contents']);
        $logo['encoding'] = 'base64';
        Cache::put('qr-logo:'.$token, $logo, now()->addHours(2));
    }

    public function logo(string $token): ?array
    {
        $logo = Cache::get('qr-logo:'.$token);
        if (! is_array($logo) || ($logo['encoding'] ?? null) !== 'base64') {
            return $logo;
        }

        $contents = base64_decode($logo['contents'], true);
        if ($contents === false) {
            $this->forgetLogo($token);

            return null;
        }
        $logo['contents'] = $contents;
        unset($logo['encoding']);

        return $logo;
    }

    public function forgetLogo(string $token): void
    {
        Cache::forget('qr-logo:'.$token);
    }

    public function forgetPreview(string $token): void
    {
        Cache::forget('qr-preview:'.$token);
    }

    public function forget(string $token): void
    {
        $this->forgetPreview($token);
        $this->forgetLogo($token);
    }
}
