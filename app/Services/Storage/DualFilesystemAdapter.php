<?php

namespace App\Services\Storage;

use App\Jobs\ReplicateFileJob;
use Closure;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter as LeagueLocalFilesystemAdapter;
use RuntimeException;
use Throwable;

/**
 * Flysystem decorator that keeps S3 and Cloudflare R2 (plus the legacy disk)
 * in sync. Every write goes to the primary (the admin-selected render source)
 * and then to each configured replica; if a replica fails a queued job
 * retries it. Reads are served exclusively by the render source, falling
 * back only to the legacy disk for files uploaded before dual storage —
 * the non-selected cloud is never consulted, so the platform chosen in the
 * admin panel always decides where files are rendered from.
 */
class DualFilesystemAdapter implements FilesystemAdapter
{
    /**
     * @param  array<string, FilesystemAdapter>  $replicas  Replicating cloud adapters keyed by provider (e.g. "r2").
     * @param  Closure(string): string|null  $urlResolver
     * @param  Closure(string, \DateTimeInterface, array): string|null  $temporaryUrlResolver
     */
    public function __construct(
        private readonly FilesystemAdapter $primary,
        private readonly string $primaryProvider,
        private readonly array $replicas,
        private readonly ?FilesystemAdapter $legacy,
        private readonly string $diskName,
        private readonly ?Closure $urlResolver = null,
        private readonly ?Closure $temporaryUrlResolver = null,
    ) {}

    public function fileExists(string $path): bool
    {
        foreach ($this->readTargets() as $target) {
            try {
                if ($target->fileExists($path)) {
                    return true;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return false;
    }

    public function directoryExists(string $path): bool
    {
        foreach ($this->readTargets() as $target) {
            try {
                if ($target->directoryExists($path)) {
                    return true;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return false;
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->primary->write($path, $contents, $config);

        foreach ($this->replicas as $provider => $replica) {
            try {
                $replica->write($path, $contents, $config);
            } catch (Throwable) {
                $this->queueReplication($path, $provider);
            }
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->primary->writeStream($path, $contents, $config);

        foreach ($this->replicas as $provider => $replica) {
            try {
                if (! is_resource($contents) || ! @rewind($contents)) {
                    throw new RuntimeException('The upload stream is not seekable for replication.');
                }

                $replica->writeStream($path, $contents, $config);
            } catch (Throwable) {
                $this->queueReplication($path, $provider);
            }
        }
    }

    public function read(string $path): string
    {
        return $this->firstTargetResult(
            fn (FilesystemAdapter $target): string => $target->read($path),
            $path,
        );
    }

    public function readStream(string $path)
    {
        return $this->firstTargetResult(
            fn (FilesystemAdapter $target) => $target->readStream($path),
            $path,
        );
    }

    public function delete(string $path): void
    {
        $primaryException = null;

        try {
            $this->primary->delete($path);
        } catch (Throwable $exception) {
            $primaryException = $exception;
        }

        foreach ($this->replicas as $replica) {
            try {
                $replica->delete($path);
            } catch (Throwable) {
                // Best effort: a later sync reconciles leftovers.
            }
        }

        if ($this->legacy !== null) {
            try {
                $this->legacy->delete($path);
            } catch (Throwable) {
                // Best effort: a later sync reconciles leftovers.
            }
        }

        if ($primaryException !== null) {
            throw $primaryException;
        }
    }

    public function deleteDirectory(string $path): void
    {
        $primaryException = null;

        try {
            $this->primary->deleteDirectory($path);
        } catch (Throwable $exception) {
            $primaryException = $exception;
        }

        foreach ($this->replicas as $replica) {
            try {
                $replica->deleteDirectory($path);
            } catch (Throwable) {
                // Best effort.
            }
        }

        if ($this->legacy !== null) {
            try {
                $this->legacy->deleteDirectory($path);
            } catch (Throwable) {
                // Best effort.
            }
        }

        if ($primaryException !== null) {
            throw $primaryException;
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        foreach ($this->readTargets() as $target) {
            if ($target instanceof LeagueLocalFilesystemAdapter) {
                $target->createDirectory($path, $config);
            }
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $primaryException = null;

        try {
            $this->primary->setVisibility($path, $visibility);
        } catch (Throwable $exception) {
            $primaryException = $exception;
        }

        foreach ($this->replicas as $replica) {
            try {
                $replica->setVisibility($path, $visibility);
            } catch (Throwable) {
                // Best effort.
            }
        }

        if ($this->legacy !== null) {
            try {
                $this->legacy->setVisibility($path, $visibility);
            } catch (Throwable) {
                // Best effort.
            }
        }

        if ($primaryException !== null) {
            throw $primaryException;
        }
    }

    public function visibility(string $path): FileAttributes
    {
        return $this->firstTargetResult(
            fn (FilesystemAdapter $target): FileAttributes => $target->visibility($path),
            $path,
        );
    }

    public function mimeType(string $path): FileAttributes
    {
        return $this->firstTargetResult(
            fn (FilesystemAdapter $target): FileAttributes => $target->mimeType($path),
            $path,
        );
    }

    public function lastModified(string $path): FileAttributes
    {
        return $this->firstTargetResult(
            fn (FilesystemAdapter $target): FileAttributes => $target->lastModified($path),
            $path,
        );
    }

    public function fileSize(string $path): FileAttributes
    {
        return $this->firstTargetResult(
            fn (FilesystemAdapter $target): FileAttributes => $target->fileSize($path),
            $path,
        );
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $seen = [];

        foreach ($this->readTargets() as $target) {
            try {
                foreach ($target->listContents($path, $deep) as $item) {
                    $itemPath = $item->path();

                    if (isset($seen[$itemPath])) {
                        continue;
                    }

                    $seen[$itemPath] = true;

                    yield $item;
                }
            } catch (Throwable) {
                continue;
            }
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->primary->move($source, $destination, $config);

        foreach ($this->replicas as $provider => $replica) {
            try {
                if ($replica->fileExists($source)) {
                    $replica->move($source, $destination, $config);
                }
            } catch (Throwable) {
                $this->queueReplication($destination, $provider);

                try {
                    $replica->delete($source);
                } catch (Throwable) {
                    // Best effort.
                }
            }
        }

        if ($this->legacy !== null) {
            try {
                if ($this->legacy->fileExists($source)) {
                    $this->legacy->move($source, $destination, $config);
                }
            } catch (Throwable) {
                // Best effort.
            }
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $this->primary->copy($source, $destination, $config);

        foreach ($this->replicas as $provider => $replica) {
            try {
                $replica->copy($source, $destination, $config);
            } catch (Throwable) {
                $this->queueReplication($destination, $provider);
            }
        }

        if ($this->legacy !== null) {
            try {
                if ($this->legacy->fileExists($source)) {
                    $this->legacy->copy($source, $destination, $config);
                }
            } catch (Throwable) {
                // Best effort.
            }
        }
    }

    /**
     * Used by Illuminate\Filesystem\FilesystemAdapter::url() to generate URLs
     * from the active render source.
     */
    public function getUrl(string $path): string
    {
        if ($this->urlResolver instanceof Closure) {
            return ($this->urlResolver)($path);
        }

        throw new RuntimeException('No URL generator is available for this storage disk.');
    }

    /**
     * Used by Illuminate\Filesystem\FilesystemAdapter::temporaryUrl() to sign
     * URLs from the active render source (e.g. PDF page previews).
     */
    public function getTemporaryUrl(string $path, \DateTimeInterface $expiration, array $options = []): string
    {
        if ($this->temporaryUrlResolver instanceof Closure) {
            return ($this->temporaryUrlResolver)($path, $expiration, $options);
        }

        throw new RuntimeException('This storage disk does not support temporary URLs.');
    }

    public function hasLocalTarget(): bool
    {
        foreach ($this->readTargets() as $target) {
            if ($target instanceof LeagueLocalFilesystemAdapter) {
                return true;
            }
        }

        return false;
    }

    public function primaryProvider(): string
    {
        return $this->primaryProvider;
    }

    /**
     * The active render source first, then the legacy disk for pre-migration
     * files. The non-selected cloud is intentionally absent: serving from it
     * would hide misses on the platform the admin chose.
     *
     * @return array<int, FilesystemAdapter>
     */
    private function readTargets(): array
    {
        $targets = [$this->primary];

        if ($this->legacy !== null) {
            $targets[] = $this->legacy;
        }

        return $targets;
    }

    /**
     * @param  Closure(FilesystemAdapter): mixed  $callback
     */
    private function firstTargetResult(Closure $callback, string $path): mixed
    {
        $lastException = null;

        foreach ($this->readTargets() as $target) {
            try {
                return $callback($target);
            } catch (Throwable $exception) {
                $lastException = $exception;
            }
        }

        if ($lastException instanceof Throwable) {
            throw $lastException;
        }

        throw new RuntimeException("No storage target is available for [{$path}].");
    }

    private function queueReplication(string $path, string $provider): void
    {
        try {
            ReplicateFileJob::dispatch($this->diskName, $path, $provider);
        } catch (Throwable) {
            // A later storage sync reconciles any replica that could not be queued.
        }
    }
}
