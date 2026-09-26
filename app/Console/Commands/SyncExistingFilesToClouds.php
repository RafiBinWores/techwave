<?php

namespace App\Console\Commands;

use App\Models\StorageSetting;
use App\Services\Storage\StorageSyncer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

#[Signature('storage:sync-existing {--disk= : Limit the sync to one logical disk (public or documents)}')]
#[Description('Copy existing files to every configured storage provider (AWS S3 and Cloudflare R2)')]
class SyncExistingFilesToClouds extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(StorageSyncer $syncer): int
    {
        $setting = StorageSetting::current();

        $providers = [];

        if ($setting->isS3Configured()) {
            $providers[StorageSetting::SOURCE_S3] = $setting->s3Config();
        }

        if ($setting->isR2Configured()) {
            $providers[StorageSetting::SOURCE_R2] = $setting->r2Config();
        }

        if ($providers === []) {
            $this->warn('No cloud storage providers are configured. Save credentials on the admin Storage page first.');

            return self::FAILURE;
        }

        $diskNames = filled($this->option('disk'))
            ? [(string) $this->option('disk')]
            : ['public', 'documents'];

        foreach ($diskNames as $diskName) {
            $config = config("filesystems.disks.{$diskName}");

            if (! is_array($config) || ($config['driver'] ?? null) !== 'dual') {
                $this->warn("Skipping [{$diskName}]: not a dual storage disk.");

                continue;
            }

            if (! isset($config['legacy'])) {
                $this->warn("Skipping [{$diskName}]: no legacy disk configured.");

                continue;
            }

            $root = (string) ($config['root'] ?? '');

            $legacy = null;

            try {
                /** @var Filesystem $legacy */
                $legacy = Storage::build($config['legacy']);
            } catch (Throwable $exception) {
                $this->warn(sprintf(
                    '[%s] legacy source unavailable — skipping legacy→cloud copy. %s',
                    $diskName,
                    Str::limit(str_replace("\n", ' ', $exception->getMessage()), 200),
                ));
            }

            $sources = $legacy !== null ? ['legacy' => $legacy] : [];
            $cloudDisks = [];

            foreach ($providers as $provider => $providerConfig) {
                /** @var Filesystem $cloud */
                $cloud = Storage::build([...$providerConfig, 'root' => $root]);

                $cloudDisks[$provider] = $cloud;
                $sources[$provider] = $cloud;
            }

            foreach ($sources as $sourceName => $source) {
                $targets = array_filter(
                    $cloudDisks,
                    static fn (string $provider): bool => $provider !== $sourceName,
                    ARRAY_FILTER_USE_KEY,
                );

                if ($targets === []) {
                    continue;
                }

                $result = $syncer->sync($source, $targets);

                $this->info(sprintf(
                    '[%s] source [%s]: %d copied, %d skipped, %d failed.',
                    $diskName,
                    $sourceName,
                    $result['copied'],
                    $result['skipped'],
                    $result['failed'],
                ));
            }
        }

        $this->info('Storage sync complete.');

        return self::SUCCESS;
    }
}
