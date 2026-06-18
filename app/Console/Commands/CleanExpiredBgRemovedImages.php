<?php

namespace App\Console\Commands;

use App\Models\UserBgRemovedImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanExpiredBgRemovedImages extends Command
{
    protected $signature = 'bg-removed-images:cleanup';

    protected $description = 'Delete expired background-removed image backups and temp files older than 1 day';

    public function handle(): void
    {
        $count = 0;

        UserBgRemovedImage::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->chunkById(100, function ($images) use (&$count) {
                foreach ($images as $image) {
                    $image->deleteFile();
                    $image->delete();

                    $count++;
                }
            });

        $this->info("Cleaned up {$count} expired backed-up background-removed image(s).");

        $tempDeleted = $this->cleanTempFiles();

        $this->info("Cleaned up {$tempDeleted} temp file(s) older than 1 day.");
    }

    private function cleanTempFiles(): int
    {
        if (! Storage::disk('public')->exists('temp/bg-remover')) {
            return 0;
        }

        $files = Storage::disk('public')->files('temp/bg-remover');

        $cutoff = now()->subDay()->timestamp;

        $deleted = 0;

        foreach ($files as $file) {
            $lastModified = Storage::disk('public')->lastModified($file);

            if ($lastModified < $cutoff) {
                Storage::disk('public')->delete($file);

                $deleted++;
            }
        }

        return $deleted;
    }
}
