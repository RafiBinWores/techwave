<?php

namespace App\Jobs;

use App\Models\StorageSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Retries a failed cloud replication: copies a file that already exists on
 * the primary (or any other target) onto the provider that missed it.
 */
class ReplicateFileJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60, 120, 300];

    public function __construct(
        public string $diskName,
        public string $path,
        public string $targetProvider,
    ) {}

    public function handle(): void
    {
        $setting = StorageSetting::current();

        $configured = match ($this->targetProvider) {
            StorageSetting::SOURCE_S3 => $setting->isS3Configured(),
            StorageSetting::SOURCE_R2 => $setting->isR2Configured(),
            default => false,
        };

        if (! $configured) {
            return;
        }

        $root = (string) (config("filesystems.disks.{$this->diskName}.root") ?? '');

        $config = $this->targetProvider === StorageSetting::SOURCE_S3
            ? $setting->s3Config()
            : $setting->r2Config();

        $target = Storage::build([...$config, 'root' => $root]);

        if ($target->exists($this->path)) {
            return;
        }

        $source = Storage::disk($this->diskName);
        $stream = $source->readStream($this->path);

        if (! is_resource($stream)) {
            throw new RuntimeException("Unable to read [{$this->path}] for replication.");
        }

        try {
            if (! $target->put($this->path, $stream)) {
                throw new RuntimeException("Unable to replicate [{$this->path}] to [{$this->targetProvider}].");
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        report($exception ?? new RuntimeException(
            "Unable to replicate [{$this->path}] to [{$this->targetProvider}]."
        ));
    }
}
