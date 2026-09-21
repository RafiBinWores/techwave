<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Image\Image;

class ImageService
{
    public function optimizeAndStore(
        UploadedFile $image,
        string $directory = 'images',
        ?int $maxWidth = null,
        int $quality = 80,
        ?string $filename = null,
        string $disk = 'public'
    ): string {
        $extension = strtolower($image->getClientOriginalExtension());

        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        if (! in_array($extension, ['jpg', 'png', 'webp'])) {
            throw new \Exception('Unsupported image format.');
        }

        $name = $filename
            ? Str::slug(pathinfo($filename, PATHINFO_FILENAME))
            : Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME));

        $name = $name ?: 'image';

        $directory = trim($directory, '/');
        $path = $directory.'/'.$name.'-'.time().'.'.$extension;

        $workspace = new LocalFileWorkspace;

        try {
            $workspace->disk()->makeDirectory($directory);

            $outputPath = $workspace->disk()->path($path);

            $spatieImage = Image::load($image->getRealPath());

            if ($maxWidth) {
                $spatieImage->width($maxWidth);
            }

            if (in_array($extension, ['jpg', 'webp'])) {
                $spatieImage->quality($quality);
            }

            $spatieImage
                ->optimize()
                ->save($outputPath);

            $workspace->publish(Storage::disk($disk), $path);

            return $path;
        } finally {
            $workspace->cleanup();
        }
    }
}
