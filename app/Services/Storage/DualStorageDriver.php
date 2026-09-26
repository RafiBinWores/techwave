<?php

namespace App\Services\Storage;

use App\Models\StorageSetting;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem as Flysystem;
use RuntimeException;
use Throwable;

/**
 * Builds the logical "dual" disks. Cloud disks are constructed at runtime
 * from the admin-managed credentials in storage_settings (not from env), so
 * credentials entered in the admin panel take effect without a config cache
 * rebuild. The env-driven legacy disk is an optional migration fallback: when
 * its credentials are missing or invalid the disk is simply left out instead
 * of taking the whole application down.
 */
class DualStorageDriver
{
    public static function make(array $config): FilesystemContract
    {
        if (! isset($config['legacy']) || ! is_array($config['legacy'])) {
            throw new RuntimeException('The dual storage disk requires a legacy disk configuration.');
        }

        $diskName = (string) ($config['disk_name'] ?? 'public');
        $root = (string) ($config['root'] ?? '');

        /** @var FilesystemAdapter|null $legacy */
        $legacy = null;

        try {
            $legacy = Storage::build($config['legacy']);
        } catch (Throwable $exception) {
            Log::warning('Legacy storage disk could not be built; continuing without it.', [
                'disk' => $diskName,
                'error' => $exception->getMessage(),
            ]);
        }

        $setting = StorageSetting::resolve();
        $clouds = [];

        if ($setting !== null && $setting->isS3Configured()) {
            $clouds[StorageSetting::SOURCE_S3] = self::buildCloud($setting->s3Config(), $root, StorageSetting::SOURCE_S3);
        }

        if ($setting !== null && $setting->isR2Configured()) {
            $clouds[StorageSetting::SOURCE_R2] = self::buildCloud($setting->r2Config(), $root, StorageSetting::SOURCE_R2);
        }

        $preferred = $setting !== null ? $setting->renderSource() : StorageSetting::SOURCE_S3;

        if (! isset($clouds[$preferred])) {
            $preferred = array_key_first($clouds) ?? 'legacy';
        }

        $hasCloudPrimary = isset($clouds[$preferred]);

        if (! $hasCloudPrimary && $legacy === null) {
            throw new RuntimeException(
                "No storage target is available for [{$diskName}]. Configure storage credentials on the admin Storage page (Admin → Storage), or set UPLOADS_DISK=local."
            );
        }

        $primary = $hasCloudPrimary ? $clouds[$preferred] : $legacy;
        $primaryProvider = $hasCloudPrimary ? $preferred : 'legacy';

        $replicas = [];

        foreach ($clouds as $provider => $cloud) {
            if ($provider !== $primaryProvider) {
                $replicas[$provider] = $cloud->getAdapter();
            }
        }

        $adapter = new DualFilesystemAdapter(
            primary: $primary->getAdapter(),
            primaryProvider: $primaryProvider,
            replicas: $replicas,
            legacy: $hasCloudPrimary ? $legacy?->getAdapter() : null,
            diskName: $diskName,
            urlResolver: static fn (string $path): string => $primary->url($path),
            temporaryUrlResolver: static fn (string $path, \DateTimeInterface $expiration, array $options = []): string => $primary->temporaryUrl($path, $expiration, $options),
        );

        $filesystem = new Flysystem($adapter, Arr::only($config, [
            'directory_visibility',
            'disable_asserts',
            'retain_visibility',
            'temporary_url',
            'url',
            'visibility',
        ]));

        return new FilesystemAdapter($filesystem, $adapter, $config);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function buildCloud(array $config, string $root, string $provider): FilesystemAdapter
    {
        try {
            /** @var FilesystemAdapter $disk */
            $disk = Storage::build([...$config, 'root' => $root]);

            return $disk;
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "Cloud storage for [{$provider}] could not be initialized: {$exception->getMessage()} Update the credentials on the admin Storage page (Admin → Storage).",
                0,
                $exception,
            );
        }
    }
}
