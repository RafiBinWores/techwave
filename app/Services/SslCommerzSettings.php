<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Validation\ValidationException;

class SslCommerzSettings
{
    public function available(): bool
    {
        $setting = SiteSetting::current();

        return $setting->sslcommerz_enabled
            && filled($setting->sslcommerz_store_id)
            && filled($setting->sslcommerz_store_password);
    }

    public function requireAvailable(): void
    {
        if (! $this->available()) {
            throw ValidationException::withMessages(['payment' => 'Online payment is unavailable. Please contact the team.']);
        }
    }

    // Callbacks need credentials even after new payments have been disabled.
    public function configuration(): array
    {
        $setting = SiteSetting::current();

        return array_replace(config('sslcommerz'), [
            'apiCredentials' => [
                'store_id' => $setting->sslcommerz_store_id,
                'store_password' => $setting->sslcommerz_store_password,
            ],
            'apiDomain' => $setting->sslcommerz_sandbox
                ? 'https://sandbox.sslcommerz.com' : 'https://securepay.sslcommerz.com',
        ]);
    }
}
