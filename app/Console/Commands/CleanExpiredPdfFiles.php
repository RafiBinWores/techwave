<?php

namespace App\Console\Commands;

use App\Models\CompressedPdf;
use App\Models\MergedPdf;
use App\Models\SplitPdf;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('pdf-files:cleanup')]
#[Description('Delete expired PDF backups and temporary files older than 1 day')]
class CleanExpiredPdfFiles extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $count = 0;

        foreach ([MergedPdf::class, SplitPdf::class, CompressedPdf::class] as $model) {
            $model::query()
                ->where(function ($query) {
                    $query->whereNotNull('expires_at')->where('expires_at', '<=', now())
                        ->orWhereNotNull('backup_expires_at')->where('backup_expires_at', '<=', now());
                })
                ->chunkById(100, function ($records) use (&$count) {
                    foreach ($records as $record) {
                        $this->deleteRecordFiles($record);

                        $record->delete();

                        $count++;
                    }
                });
        }

        $this->info("Cleaned up {$count} expired PDF record(s).");

        $tempDeleted = $this->cleanTempFiles();

        $this->info("Cleaned up {$tempDeleted} temporary preview file(s) older than 1 day.");
    }

    private function deleteRecordFiles(MergedPdf|SplitPdf|CompressedPdf $record): void
    {
        if ($record instanceof MergedPdf) {
            $record->deleteOutputFile();
        } elseif ($record instanceof SplitPdf) {
            $record->deleteOriginalFile();
            $record->deleteOutputFile();
        } elseif ($record instanceof CompressedPdf) {
            $record->deleteAllFiles();
        }
    }

    private function cleanTempFiles(): int
    {
        $disk = Storage::disk(config('pdf-compressor.storage_disk'));

        if (! $disk->exists('split-preview')) {
            return 0;
        }

        $cutoff = now()->subDay()->timestamp;

        $deleted = 0;

        foreach ($disk->allFiles('split-preview') as $file) {
            if ($disk->lastModified($file) < $cutoff) {
                $disk->delete($file);

                $deleted++;
            }
        }

        return $deleted;
    }
}
