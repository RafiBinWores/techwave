<?php

namespace App\Services\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Throwable;

/**
 * Copies files from a source disk onto every target that is missing them.
 */
class StorageSyncer
{
    /**
     * @param  array<string, Filesystem>  $targets
     * @return array{copied: int, skipped: int, failed: int}
     */
    public function sync(Filesystem $source, array $targets): array
    {
        $result = ['copied' => 0, 'skipped' => 0, 'failed' => 0];

        foreach ($source->allFiles() as $path) {
            foreach ($targets as $target) {
                try {
                    if ($target->exists($path)) {
                        $result['skipped']++;

                        continue;
                    }

                    $stream = $source->readStream($path);

                    if (! is_resource($stream)) {
                        $result['failed']++;

                        continue;
                    }

                    try {
                        if ($target->put($path, $stream)) {
                            $result['copied']++;
                        } else {
                            $result['failed']++;
                        }
                    } finally {
                        if (is_resource($stream)) {
                            fclose($stream);
                        }
                    }
                } catch (Throwable) {
                    $result['failed']++;
                }
            }
        }

        return $result;
    }
}
