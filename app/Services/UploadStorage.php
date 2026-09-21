<?php

namespace App\Services;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use League\Flysystem\Local\LocalFilesystemAdapter;

class UploadStorage
{
    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            $urlPath = (string) parse_url($path, PHP_URL_PATH);
            $appHost = parse_url(config('app.url'), PHP_URL_HOST);

            if (parse_url($path, PHP_URL_HOST) !== $appHost || ! str_starts_with($urlPath, '/storage/')) {
                return $path;
            }

            $path = rawurldecode(substr($urlPath, strlen('/storage/')));
        }

        return URL::signedRoute('uploads.show', ['path' => self::normalizePath($path)]);
    }

    public static function normalizePath(string $path): string
    {
        return preg_replace('#^storage/#', '', ltrim($path, '/'));
    }

    public static function ensureDirectory(string $diskName, string $directory): void
    {
        $disk = Storage::disk($diskName);

        if ($disk->getAdapter() instanceof LocalFilesystemAdapter) {
            $disk->makeDirectory($directory);
        }
    }

    public static function emailLogo(?string $path, ?Message $message = null): ?string
    {
        if (blank($path) || preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $path = self::normalizePath($path);
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        if ($message === null) {
            return self::url($path);
        }

        $contents = $disk->get($path);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents) ?: 'image/png';

        return $message->embedData($contents, basename($path), $mime);
    }

    public static function dataUri(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $disk = Storage::disk('public');
        $path = self::normalizePath($path);

        if (! $disk->exists($path)) {
            return null;
        }

        $contents = $disk->get($path);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents) ?: 'application/octet-stream';

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }
}
