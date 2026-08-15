<?php

namespace App\Services;

use App\Models\SplitPdf;
use App\Services\Concerns\ValidatesPdf;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class PdfSplitService
{
    use ValidatesPdf;

    public function __construct(
        private readonly GhostscriptService $ghostscript,
    ) {}

    public function split(SplitPdf $record): void
    {
        $totalStartedAt = microtime(true);

        $diskName = (string) config(
            'pdf-compressor.storage_disk',
            'local'
        );

        $disk = Storage::disk($diskName);
        $inputPath = $disk->path($record->original_path);

        if (! is_file($inputPath)) {
            $this->markFailed(
                $record,
                'Original PDF file was not found on disk.'
            );

            return;
        }

        $validation = $this->validatePdf($inputPath);

        if (! $validation['valid']) {
            $this->markFailed(
                $record,
                $validation['error'] ?? 'Invalid PDF file.'
            );

            return;
        }

        $outputDirectory = $this->outputDirectory($record);

        $disk->makeDirectory($outputDirectory);

        Log::info('PDF split started', [
            'split_pdf_id' => $record->id,
            'mode' => $record->mode,
        ]);

        try {
            $pageCount = $this->ghostscript->pageCount($inputPath);

            if ($pageCount <= 0) {
                throw new RuntimeException(
                    'The uploaded PDF contains no pages.'
                );
            }

            $record->update(['page_count' => $pageCount]);

            if ($record->mode === 'all') {
                $this->splitAllPages($record, $disk, $outputDirectory);
            } elseif ($record->mode === 'range' && $this->shouldSplitRangesSeparately($record)) {
                $this->splitByRanges($record, $disk, $outputDirectory);
            } else {
                $this->extractSelectedPages($record, $disk, $outputDirectory);
            }

            Log::info('PDF split completed', [
                'split_pdf_id' => $record->id,
                'page_count' => $pageCount,
                'total_seconds' => round(
                    microtime(true) - $totalStartedAt,
                    2
                ),
            ]);

            if (! $record->is_backup_enabled) {
                $record->deleteOriginalFile();
            }
        } catch (Throwable $e) {
            $record->deleteOutputFile();

            $this->markFailed($record, $e->getMessage());

            Log::error('PDF split failed', [
                'split_pdf_id' => $record->id,
                'seconds' => round(
                    microtime(true) - $totalStartedAt,
                    2
                ),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function splitAllPages(
        SplitPdf $record,
        Filesystem $disk,
        string $outputDirectory,
    ): void {
        $temporaryPaths = [];

        try {
            for ($page = 1; $page <= (int) $record->page_count; $page++) {
                $relative = $outputDirectory.'/'.Str::random(24).'.pdf';
                $fullPath = $disk->path($relative);

                $this->ghostscript->extractPages(
                    inputPath: $disk->path($record->original_path),
                    outputPath: $fullPath,
                    pages: [$page],
                    timeout: (int) config(
                        'pdf-compressor.processing_timeout',
                        180
                    ),
                );

                $temporaryPaths[] = [
                    'relative' => $relative,
                    'full' => $fullPath,
                    'label' => str_pad((string) $page, 3, '0', STR_PAD_LEFT),
                    'entry' => pathinfo($record->original_name, PATHINFO_FILENAME).'_page-'.str_pad((string) $page, 3, '0', STR_PAD_LEFT).'.pdf',
                ];
            }

            $zipPath = $this->createZip(
                $disk,
                $outputDirectory,
                $temporaryPaths
            );

            foreach ($temporaryPaths as $temporary) {
                if (is_file($temporary['full'])) {
                    @unlink($temporary['full']);
                }
            }

            $this->finalizeRecord($record, $zipPath);
        } catch (Throwable $e) {
            foreach ($temporaryPaths as $temporary) {
                if (is_file($temporary['full'])) {
                    @unlink($temporary['full']);
                }
            }

            throw $e;
        }
    }

    private function extractSelectedPages(
        SplitPdf $record,
        Filesystem $disk,
        string $outputDirectory,
    ): void {
        $pages = $this->resolvedPages($record);

        $outputPath = $outputDirectory.'/'.Str::random(30).'.pdf';

        $outputFullPath = $disk->path($outputPath);

        $this->ghostscript->extractPages(
            inputPath: $disk->path($record->original_path),
            outputPath: $outputFullPath,
            pages: $pages,
            timeout: (int) config(
                'pdf-compressor.processing_timeout',
                180
            ),
        );

        $this->finalizeRecord($record, $outputPath);
    }

    /**
     * Resolve the final 1-based page list from the stored mode settings.
     *
     * @return array<int, int>
     */
    private function resolvedPages(SplitPdf $record): array
    {
        if ($record->mode === 'range') {
            $ranges = (array) $record->ranges;

            if ($ranges !== []) {
                return $this->expandRanges($ranges, (int) $record->page_count);
            }

            $start = (int) $record->range_start;
            $end = (int) $record->range_end;

            if ($start <= 0 || $end < $start) {
                throw new RuntimeException(
                    'The selected page range is invalid.'
                );
            }

            if ($end > (int) $record->page_count) {
                $end = (int) $record->page_count;
            }

            return range($start, $end);
        }

        if ($record->mode === 'custom') {
            $pages = array_values(array_unique(
                array_map(
                    'intval',
                    array_filter((array) $record->selected_pages, fn ($page) => is_numeric($page) && (int) $page > 0)
                )
            ));

            $pageCount = (int) $record->page_count;

            $pages = array_values(array_filter(
                $pages,
                fn (int $page) => $pageCount <= 0 || $page <= $pageCount
            ));

            sort($pages);

            if ($pages === []) {
                throw new RuntimeException(
                    'No valid pages were selected to extract.'
                );
            }

            return $pages;
        }

        throw new RuntimeException(
            'Unknown split mode: '.$record->mode
        );
    }

    /**
     * Expand one or more page ranges into a single ordered page list.
     *
     * @param  array<int, array{start: int, end: int}>  $ranges
     * @return array<int, int>
     */
    private function expandRanges(array $ranges, int $pageCount): array
    {
        $pages = [];

        foreach ($ranges as $range) {
            $start = (int) ($range['start'] ?? 0);
            $end = (int) ($range['end'] ?? 0);

            if ($start <= 0 || $end < $start) {
                throw new RuntimeException(
                    'One of the selected page ranges is invalid.'
                );
            }

            if ($pageCount > 0 && $end > $pageCount) {
                $end = $pageCount;
            }

            foreach (range($start, $end) as $page) {
                $pages[] = $page;
            }
        }

        return array_values(array_unique($pages));
    }

    private function shouldSplitRangesSeparately(SplitPdf $record): bool
    {
        if ($record->combine_ranges) {
            return false;
        }

        return count((array) $record->ranges) > 1;
    }

    private function splitByRanges(
        SplitPdf $record,
        Filesystem $disk,
        string $outputDirectory,
    ): void {
        $ranges = (array) $record->ranges;

        $pageCount = (int) $record->page_count;

        $temporaryPaths = [];

        try {
            foreach ($ranges as $range) {
                $start = (int) ($range['start'] ?? 0);
                $end = (int) ($range['end'] ?? 0);

                if ($start <= 0 || $end < $start) {
                    throw new RuntimeException(
                        'One of the selected page ranges is invalid.'
                    );
                }

                if ($pageCount > 0 && $end > $pageCount) {
                    $end = $pageCount;
                }

                $pages = range($start, $end);

                if ($pages === []) {
                    continue;
                }

                $relative = $outputDirectory.'/'.Str::random(24).'.pdf';
                $fullPath = $disk->path($relative);

                $this->ghostscript->extractPages(
                    inputPath: $disk->path($record->original_path),
                    outputPath: $fullPath,
                    pages: $pages,
                    timeout: (int) config(
                        'pdf-compressor.processing_timeout',
                        180
                    ),
                );

                $temporaryPaths[] = [
                    'relative' => $relative,
                    'full' => $fullPath,
                    'label' => $start.'-'.$end,
                    'entry' => pathinfo($record->original_name, PATHINFO_FILENAME).'_pages-'.$start.'-'.$end.'.pdf',
                ];
            }

            if ($temporaryPaths === []) {
                throw new RuntimeException(
                    'No valid page ranges were selected to extract.'
                );
            }

            if (count($temporaryPaths) === 1) {
                $this->finalizeRecord(
                    $record,
                    $temporaryPaths[0]['relative']
                );

                return;
            }

            $zipPath = $this->createZip(
                $disk,
                $outputDirectory,
                $temporaryPaths
            );

            foreach ($temporaryPaths as $temporary) {
                if (is_file($temporary['full'])) {
                    @unlink($temporary['full']);
                }
            }

            $this->finalizeRecord($record, $zipPath);
        } catch (Throwable $e) {
            foreach ($temporaryPaths as $temporary) {
                if (is_file($temporary['full'])) {
                    @unlink($temporary['full']);
                }
            }

            throw $e;
        }
    }

    /**
     * @param  array<int, array{relative: string, full: string, label: string, entry: string}>  $pages
     */
    private function createZip(
        Filesystem $disk,
        string $outputDirectory,
        array $pages,
    ): string {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException(
                'ZipArchive is required to split PDFs into multiple files.'
            );
        }

        $zipRelative = $outputDirectory.'/'.Str::random(30).'.zip';

        $zipFullPath = $disk->path($zipRelative);

        $zip = new ZipArchive;

        if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException(
                'Unable to create the output ZIP archive.'
            );
        }

        foreach ($pages as $page) {
            $zip->addFile(
                $page['full'],
                $page['entry']
            );
        }

        $zip->close();

        clearstatcache(true, $zipFullPath);

        if (! is_file($zipFullPath) || (int) filesize($zipFullPath) <= 0) {
            throw new RuntimeException(
                'The output ZIP archive is empty.'
            );
        }

        return $zipRelative;
    }

    private function finalizeRecord(
        SplitPdf $record,
        string $outputPath,
    ): void {
        $diskName = (string) config(
            'pdf-compressor.storage_disk',
            'local'
        );

        $disk = Storage::disk($diskName);

        $outputFullPath = $disk->path($outputPath);

        clearstatcache(true, $outputFullPath);

        $outputSize = @filesize($outputFullPath);

        if ($outputSize === false || $outputSize <= 0) {
            throw new RuntimeException(
                'The split output file is empty.'
            );
        }

        $record->update([
            'output_path' => $outputPath,
            'output_size' => (int) $outputSize,
            'status' => 'completed',
            'error_message' => null,
            'processed_at' => now(),
        ]);
    }

    private function outputDirectory(SplitPdf $record): string
    {
        if ($record->is_backup_enabled && $record->user_id) {
            return 'split-pdfs/users/'.$record->user_id;
        }

        if ($record->session_id) {
            return 'split-pdfs/guests/'.$record->session_id;
        }

        return 'split-pdfs/temporary';
    }

    private function markFailed(
        SplitPdf $record,
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
