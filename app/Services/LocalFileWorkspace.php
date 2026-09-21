<?php

namespace App\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class LocalFileWorkspace
{
    private FilesystemAdapter $local;

    public function __construct()
    {
        $this->local = Storage::build([
            'driver' => 'local',
            'root' => storage_path('app/processing/'.Str::uuid()),
            'throw' => true,
        ]);
    }

    public function disk(): FilesystemAdapter
    {
        return $this->local;
    }

    public function import(FilesystemAdapter $source, string $path): void
    {
        self::transfer($source, $this->local, $path);
    }

    public function publish(FilesystemAdapter $destination, string $path): void
    {
        self::transfer($this->local, $destination, $path);
    }

    public function cleanup(): void
    {
        $this->local->deleteDirectory('');
    }

    public static function transfer(FilesystemAdapter $source, FilesystemAdapter $destination, string $path): void
    {
        $stream = $source->readStream($path);

        if (! is_resource($stream)) {
            throw new RuntimeException('Unable to read the stored file.');
        }

        try {
            if (! $destination->put($path, $stream)) {
                throw new RuntimeException('Unable to save the stored file.');
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}
