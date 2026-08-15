<?php

namespace App\Services;

use App\Models\MergedPdf;
use App\Services\Concerns\ValidatesPdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class PdfMergeService
{
    use ValidatesPdf;

    public function __construct(
        private readonly GhostscriptService $ghostscript,
    ) {}

    /**
     * @param  array<int, string>  $sourcePaths
     */
    public function merge(MergedPdf $record, array $sourcePaths): void
    {
        $totalStartedAt = microtime(true);

        $diskName = (string) config(
            'pdf-compressor.storage_disk',
            'local'
        );

        $disk = Storage::disk($diskName);

        if ($sourcePaths === []) {
            $this->markFailed(
                $record,
                'No source PDF files were provided to merge.'
            );

            return;
        }

        foreach ($sourcePaths as $sourcePath) {
            $fullPath = $disk->path($sourcePath);

            $validation = $this->validatePdf($fullPath);

            if (! $validation['valid']) {
                $this->markFailed(
                    $record,
                    'Invalid source PDF: '.($validation['error'] ?? 'Unknown error.')
                );

                return;
            }
        }

        $outputDirectory = $this->outputDirectory($record);

        $disk->makeDirectory($outputDirectory);

        $outputPath = $outputDirectory.'/'.Str::random(30).'.pdf';

        $outputFullPath = $disk->path($outputPath);

        Log::info('PDF merge started', [
            'merged_pdf_id' => $record->id,
            'source_count' => count($sourcePaths),
        ]);

        try {
            $fullSourcePaths = array_map(
                fn (string $path) => $disk->path($path),
                $sourcePaths
            );

            $this->ghostscript->merge(
                inputPaths: $fullSourcePaths,
                outputPath: $outputFullPath,
                timeout: (int) config(
                    'pdf-compressor.processing_timeout',
                    180
                ),
            );

            clearstatcache(true, $outputFullPath);

            $outputSize = @filesize($outputFullPath);

            if ($outputSize === false || $outputSize <= 0) {
                throw new RuntimeException(
                    'The merged PDF output is empty.'
                );
            }

            $record->update([
                'output_path' => $outputPath,
                'output_size' => (int) $outputSize,
                'status' => 'completed',
                'error_message' => null,
                'processed_at' => now(),
            ]);

            if (! $record->is_backup_enabled) {
                foreach ($sourcePaths as $sourcePath) {
                    $disk->delete($sourcePath);
                }
            }

            Log::info('PDF merge completed', [
                'merged_pdf_id' => $record->id,
                'output_size_mb' => round(
                    $outputSize / 1024 / 1024,
                    2
                ),
                'total_seconds' => round(
                    microtime(true) - $totalStartedAt,
                    2
                ),
            ]);
        } catch (Throwable $e) {
            if (is_file($outputFullPath)) {
                @unlink($outputFullPath);
            }

            $this->markFailed($record, $e->getMessage());

            Log::error('PDF merge failed', [
                'merged_pdf_id' => $record->id,
                'seconds' => round(
                    microtime(true) - $totalStartedAt,
                    2
                ),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function outputDirectory(MergedPdf $record): string
    {
        if ($record->is_backup_enabled && $record->user_id) {
            return 'merged-pdfs/users/'.$record->user_id;
        }

        if ($record->session_id) {
            return 'merged-pdfs/guests/'.$record->session_id;
        }

        return 'merged-pdfs/temporary';
    }

    private function markFailed(
        MergedPdf $record,
        string $message,
    ): void {
        $record->update([
            'output_path' => null,
            'output_size' => null,
            'status' => 'failed',
            'error_message' => $message,
            'processed_at' => now(),
        ]);
    }
}
