<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Exceptions\LicenseSigningException;
use App\Models\NetworkInstallation;
use App\Models\NetworkLicense;
use App\Models\NetworkLicenseEvent;
use App\Models\NetworkLicenseTrial;
use App\Models\Product;
use App\Models\ProductPurchase;
use App\Models\User;
use App\Services\Products\ProductDownloads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NetworkLicensing
{
    public function isProduct(Product $product): bool
    {
        // Slugs remain stable when product names change, and other software must never inherit NetWatch licensing.
        return in_array(Str::slug($product->slug), ['netwatch', 'techwave-netwatch'], true)
            || in_array(Str::slug($product->name), ['netwatch', 'techwave-netwatch'], true);
    }

    public function limitKeys(ProductPurchase $purchase): array
    {
        $limits = $purchase->limits ?? [];
        $definitions = $purchase->product()->withTrashed()->first()?->limit_definitions ?? [];
        $normalize = fn (string $value): string => Str::slug(Str::snake($value), '_');
        $find = function (array $aliases) use ($limits, $definitions, $normalize): ?string {
            foreach (array_keys($limits) as $key) {
                if (in_array($normalize($key), $aliases, true)) {
                    return $key;
                }
            }
            foreach ($definitions as $definition) {
                if (array_key_exists($definition['key'], $limits)
                    && in_array($normalize($definition['label']), $aliases, true)) {
                    return $definition['key'];
                }
            }

            return null;
        };

        return [
            'sensor_limit' => $find(['sensor_limit', 'sensors', 'sensor', 'sensor_count', 'max_sensors', 'maximum_sensors', 'monitored_checks']),
            'installation_limit' => $find(['installation_limit', 'installations', 'installation', 'installs', 'install_limit', 'max_installations']),
            'device_limit' => $find(['device_limit', 'devices', 'device', 'device_count', 'max_devices']),
        ];
    }

    public function syncActiveSubscriptions(): int
    {
        $ids = Product::withTrashed()->get(['id', 'name', 'slug'])->filter(fn ($product) => $this->isProduct($product))->pluck('id');
        $latest = ProductPurchase::whereIn('product_id', $ids)->where('status', 'active')->whereNotNull('verified_at')
            ->whereNotNull('invoice_id')->where('expires_at', '>', now())
            ->selectRaw('MAX(id)')->groupBy('user_id', 'product_id');
        $count = 0;
        ProductPurchase::whereIn('id', $latest)->chunkById(100, function ($purchases) use (&$count): void {
            foreach ($purchases as $purchase) {
                DB::transaction(function () use ($purchase, &$count): void {
                    User::whereKey($purchase->user_id)->lockForUpdate()->firstOrFail();
                    $fresh = $purchase->fresh();
                    if ($fresh && $this->syncPurchase($fresh)) {
                        $count++;
                    }
                });
            }
        });

        return $count;
    }

    public function syncPurchase(ProductPurchase $purchase): ?NetworkLicense
    {
        $product = $purchase->product()->withTrashed()->first();
        if ($purchase->status !== 'active' || ! $purchase->verified_at || ! $purchase->invoice_id
            || ! in_array($purchase->billing_cycle, ['monthly', 'yearly'], true)
            || ! $purchase->expires_at?->isFuture() || ! $product || ! $this->isProduct($product)) {
            return null;
        }
        // A customer/product owns one stable license. Renewal changes its paid purchase, not installation identity.
        $license = NetworkLicense::firstOrCreate(['user_id' => $purchase->user_id, 'product_id' => $purchase->product_id],
            ['product_purchase_id' => $purchase->id]);
        if ($license->wasRecentlyCreated) {
            $this->record($license, 'issued', ['purchase_id' => $purchase->id]);
        }
        if ($license->product_purchase_id !== $purchase->id) {
            $license->update(['product_purchase_id' => $purchase->id]);
            $this->record($license, 'subscription_updated', ['purchase_id' => $purchase->id]);
        }

        return $license;
    }

    public function keyForPurchase(ProductPurchase $purchase): string
    {
        abort_unless(auth()->check() && (int) auth()->id() === (int) $purchase->user_id, 404);

        return DB::transaction(function () use ($purchase): string {
            User::whereKey($purchase->user_id)->lockForUpdate()->firstOrFail();
            $purchase = ProductPurchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            $license = $this->syncPurchase($purchase);
            if (! $license) {
                throw ValidationException::withMessages(['license' => 'This purchase has no active NetWatch license.']);
            }

            return $this->rotateKey($license);
        });
    }

    public function prepareDownload(Product $product, bool $generateNewKey = true): array
    {
        abort_unless(auth()->check(), 403);
        abort_unless($this->isProduct($product) && app(ProductDownloads::class)->available($product), 404);

        return DB::transaction(function () use ($product, $generateNewKey): array {
            User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
            $paid = ProductPurchase::where('user_id', auth()->id())->where('product_id', $product->id)
                ->where('status', 'active')->whereNotNull('invoice_id')->whereNotNull('verified_at')
                ->where('expires_at', '>', now())->whereIn('billing_cycle', ['monthly', 'yearly'])->latest('id')->first();
            $license = $paid ? $this->syncPurchase($paid) : NetworkLicense::where('user_id', auth()->id())->where('product_id', $product->id)->first();
            if ($paid && $license) {
                $this->limits($license);

                return ['license' => $license, 'key' => ! $generateNewKey && $license->key_hash ? '' : $this->rotateKey($license)];
            }
            if ($license?->revoked_at) {
                throw ValidationException::withMessages(['license' => 'Your license is revoked or its subscription has expired. Please renew or contact support.']);
            }
            $trial = NetworkLicenseTrial::where('user_id', auth()->id())->first();
            if ($trial && (! $trial->expires_at->isFuture() || $trial->product_id !== $product->id || ! $trial->network_license_id)) {
                throw ValidationException::withMessages(['license' => 'Your free trial has already been used. Choose a paid subscription to continue.']);
            }
            $license ??= NetworkLicense::firstOrCreate(['user_id' => auth()->id(), 'product_id' => $product->id]);
            if (! $trial) {
                // Only an actual trial redemption consumes the free trial; archived purchases do not.
                $license->update(['product_purchase_id' => null]);
                $startedAt = now();
                $trial = NetworkLicenseTrial::create([
                    'user_id' => auth()->id(), 'product_id' => $product->id, 'network_license_id' => $license->id,
                    'started_at' => $startedAt, 'expires_at' => $startedAt->copy()->addDays(7),
                    'limits' => ['sensor_limit' => (int) config('network-licenses.trial_sensor_limit'),
                        'installation_limit' => (int) config('network-licenses.trial_installation_limit'), 'device_limit' => null],
                ]);
                $this->record($license, 'trial_started', ['expires_at' => $trial->expires_at->toIso8601String()], auth()->id());
            }
            if ($trial->network_license_id !== $license->id) {
                throw ValidationException::withMessages(['license' => 'Your trial is associated with a different license. Please contact support.']);
            }

            $this->limits($license);

            return ['license' => $license, 'key' => ! $generateNewKey && $license->key_hash ? '' : $this->rotateKey($license)];
        });
    }

    public function rotateKey(NetworkLicense $license): string
    {
        $admin = auth()->check() && in_array(auth()->user()->role, [UserRole::ADMIN, UserRole::ADMIN_MANAGER], true);
        abort_unless($admin || (auth()->check() && (int) auth()->id() === $license->user_id), 403);

        return DB::transaction(function () use ($license): string {
            $license = NetworkLicense::whereKey($license->id)->lockForUpdate()->firstOrFail();
            $this->limits($license);
            $key = 'NW-'.bin2hex(random_bytes(24));
            $license->update(['key_hash' => hash('sha256', $key), 'key_hint' => substr($key, -6)]);
            $this->record($license, 'key_rotated', [], auth()->id());

            return $key;
        });
    }

    public function limits(NetworkLicense $license): array
    {
        if (! $license->product_purchase_id) {
            $trial = $license->trial()->where('user_id', $license->user_id)->where('product_id', $license->product_id)->first();
            if ($license->revoked_at || ! $trial || ! $trial->expires_at->isFuture()) {
                throw ValidationException::withMessages(['license' => 'Your seven-day trial has expired or the license was revoked. Choose a paid plan to continue.']);
            }

            return [...$trial->limits, 'expires_at' => $trial->expires_at->timestamp];
        }
        $purchase = $license->purchase()->first();
        $keys = $purchase ? $this->limitKeys($purchase) : [];
        $product = $purchase?->product()->withTrashed()->first();
        if ($license->revoked_at || ! $product || ! $this->isProduct($product) || ! $purchase || $purchase->status !== 'active'
            || ! $purchase->verified_at || ! $purchase->invoice_id || ! $purchase->expires_at?->isFuture()
            || ($purchase->starts_at && $purchase->starts_at->isFuture())
            || (int) $purchase->user_id !== $license->user_id || (int) $purchase->product_id !== $license->product_id
            || ! in_array($purchase->billing_cycle, ['monthly', 'yearly'], true)) {
            throw ValidationException::withMessages(['license' => 'The license is revoked, expired, or its paid NetWatch subscription is not active.']);
        }
        $amount = function (?string $key, bool $required = true) use ($purchase): ?int {
            if (! $key && ! $required) {
                return null;
            }
            $value = $purchase->limits[$key] ?? [];
            if (! empty($value['unlimited'])) {
                return 2147483647;
            }
            $number = filter_var($value['amount'] ?? null, FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
            if ($number === false) {
                throw ValidationException::withMessages(['license' => 'The paid subscription must include a positive '.($key ?: 'sensor or installation').' limit.']);
            }

            return $number;
        };

        return ['sensor_limit' => $amount($keys['sensor_limit']), 'installation_limit' => $amount($keys['installation_limit']),
            'device_limit' => $amount($keys['device_limit'], false), 'expires_at' => $purchase->expires_at->timestamp];
    }

    public function activate(string $key, string $installationId): array
    {
        return DB::transaction(function () use ($key, $installationId): array {
            $license = NetworkLicense::where('key_hash', hash('sha256', trim($key)))->lockForUpdate()->first();
            abort_unless($license, 403, 'Activation declined.');
            $limits = $this->limits($license);
            $installation = $license->installations()->where('installation_id', $installationId)->first();
            $active = $license->installations()->whereNull('deactivated_at');
            if (! $installation || $installation->deactivated_at) {
                abort_if((clone $active)->count() >= $limits['installation_limit'], 403, 'Installation capacity reached.');
            } else {
                abort_if((clone $active)->where('id', '<=', $installation->id)->count() > $limits['installation_limit'], 403, 'Installation capacity reached.');
            }
            $credential = bin2hex(random_bytes(32));
            $installation = $license->installations()->updateOrCreate(['installation_id' => $installationId], [
                'credential_hash' => hash('sha256', $credential), 'last_verified_at' => now(), 'deactivated_at' => null,
            ]);
            $reply = ['entitlement' => $this->sign($license, $installation, $limits), 'credential' => $credential];
            $this->record($license, 'installation_activated', ['installation_id' => $installationId]);

            return $reply;
        });
    }

    public function refresh(string $credential, string $installationId, bool $deactivate = false): array
    {
        $hash = hash('sha256', $credential);
        $candidate = NetworkInstallation::where('credential_hash', $hash)->where('installation_id', $installationId)->whereNull('deactivated_at')->first();
        abort_unless($candidate, 401, 'Invalid installation credential.');

        return DB::transaction(function () use ($candidate, $hash, $deactivate): array {
            $license = NetworkLicense::whereKey($candidate->network_license_id)->lockForUpdate()->firstOrFail();
            $installation = $license->installations()->whereKey($candidate->id)->where('credential_hash', $hash)->whereNull('deactivated_at')->first();
            abort_unless($installation, 401, 'Invalid installation credential.');
            if ($deactivate) {
                $installation->update(['credential_hash' => null, 'deactivated_at' => now()]);
                $this->record($license, 'installation_deactivated', ['installation_id' => $installation->installation_id]);

                return ['deactivated' => true];
            }
            $limits = $this->limits($license);
            abort_if($license->installations()->whereNull('deactivated_at')->where('id', '<=', $installation->id)->count() > $limits['installation_limit'], 403);
            $installation->update(['last_verified_at' => now()]);

            return ['entitlement' => $this->sign($license, $installation, $limits)];
        });
    }

    private function sign(NetworkLicense $license, NetworkInstallation $installation, array $limits): array
    {
        $path = config('network-licenses.private_key_path');
        $key = is_string($path) && is_file($path) ? openssl_pkey_get_private(file_get_contents($path)) : false;
        if (! $key) {
            throw new LicenseSigningException('The license signing key is not configured.');
        }
        $issuedAt = now()->timestamp;
        $payload = json_encode([
            'product' => 'techwave-netwatch', 'license_id' => $license->id, 'installation_id' => $installation->installation_id,
            'sensor_limit' => $limits['sensor_limit'], 'installation_limit' => $limits['installation_limit'],
            'device_limit' => $limits['device_limit'], 'issued_at' => $issuedAt, 'expires_at' => $limits['expires_at'],
            'verify_by' => min($limits['expires_at'], $issuedAt + min(2, max(1, (int) config('network-licenses.verification_days', 2))) * 86400),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        if (! openssl_sign($payload, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new LicenseSigningException('The license entitlement could not be signed.');
        }
        $encode = fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');

        return ['payload' => $encode($payload), 'signature' => $encode($signature),
            'key_id' => config('network-licenses.key_id'), 'algorithm' => 'RS256'];
    }

    public function record(NetworkLicense $license, string $action, array $metadata = [], ?int $actorId = null): void
    {
        NetworkLicenseEvent::create(['network_license_id' => $license->id, 'actor_id' => $actorId,
            'action' => $action, 'metadata' => $metadata]);
    }
}
