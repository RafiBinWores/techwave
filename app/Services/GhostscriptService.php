<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class GhostscriptService
{
    public function compress(
        string $inputPath,
        string $outputPath,
        int $imageResolution,
        int $monoResolution,
        int $jpegQuality,
        string $compatibilityLevel,
        int $timeout = 180,
    ): void {
        $binary = $this->binary();

        if (! is_file($inputPath)) {
            throw new RuntimeException(
                'Ghostscript input PDF was not found.'
            );
        }

        $this->ensureOutputDirectory($outputPath);

        $command = [
            $binary,
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel='.$compatibilityLevel,
            '-dNOPAUSE',
            '-dBATCH',
            '-dSAFER',
            '-dQUIET',

            // Avoid unnecessary metadata and thumbnail generation.
            '-dPreserveAnnots=true',
            '-dPreserveMarkedContent=true',
            '-dCreateJobTicket=false',
            '-dPrinted=false',

            // Font optimization.
            '-dEmbedAllFonts=true',
            '-dSubsetFonts=true',
            '-dCompressFonts=true',

            // Reuse repeated images where possible.
            '-dDetectDuplicateImages=true',

            // Force predictable and fast JPEG compression for color/gray images.
            '-dAutoFilterColorImages=false',
            '-dAutoFilterGrayImages=false',
            '-dColorImageFilter=/DCTEncode',
            '-dGrayImageFilter=/DCTEncode',

            // Downsampling.
            '-dDownsampleColorImages=true',
            '-dDownsampleGrayImages=true',
            '-dDownsampleMonoImages=true',

            '-dColorImageDownsampleType=/Bicubic',
            '-dGrayImageDownsampleType=/Bicubic',
            '-dMonoImageDownsampleType=/Subsample',

            '-dColorImageResolution='.$imageResolution,
            '-dGrayImageResolution='.$imageResolution,
            '-dMonoImageResolution='.$monoResolution,

            // Do not repeatedly downsample images already near the target DPI.
            '-dColorImageDownsampleThreshold=1.5',
            '-dGrayImageDownsampleThreshold=1.5',
            '-dMonoImageDownsampleThreshold=1.5',

            '-dJPEGQ='.$jpegQuality,

            // Faster PDF writing.
            '-dFastWebView=false',
            '-dOptimize=false',

            '-sOutputFile='.$outputPath,
            $inputPath,
        ];

        $process = new Process($command);
        $process->setTimeout($timeout);
        $process->setIdleTimeout(null);

        $process->run();

        if (! $process->isSuccessful()) {
            $message = trim(
                $process->getErrorOutput()
                    ?: $process->getOutput()
            );

            throw new RuntimeException(
                $message !== ''
                    ? 'Ghostscript failed: '.$message
                    : 'Ghostscript failed without an error message.'
            );
        }

        clearstatcache(true, $outputPath);

        if (
            ! is_file($outputPath)
            || ! is_readable($outputPath)
            || (int) filesize($outputPath) <= 0
        ) {
            throw new RuntimeException(
                'Ghostscript did not create a valid output PDF.'
            );
        }
    }

    /**
     * Merge multiple PDF files into a single PDF.
     *
     * @param  array<int, string>  $inputPaths
     */
    public function merge(
        array $inputPaths,
        string $outputPath,
        int $timeout = 180,
    ): void {
        $binary = $this->binary();

        if ($inputPaths === []) {
            throw new RuntimeException(
                'At least one PDF is required to merge.'
            );
        }

        foreach ($inputPaths as $inputPath) {
            if (! is_file($inputPath)) {
                throw new RuntimeException(
                    'A PDF to merge was not found: '.$inputPath
                );
            }
        }

        $this->ensureOutputDirectory($outputPath);

        $command = [
            $binary,
            '-dNOPAUSE',
            '-dBATCH',
            '-dSAFER',
            '-dQUIET',
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.5',
            '-sOutputFile='.$outputPath,
            ...$inputPaths,
        ];

        $this->run($command, $timeout, $outputPath, 'Ghostscript merge failed');
    }

    /**
     * Render a single page of a PDF to a PNG image.
     */
    public function renderPageImage(
        string $inputPath,
        int $page,
        string $outputPath,
        int $resolution = 72,
        int $timeout = 60,
    ): void {
        if (! is_file($inputPath)) {
            throw new RuntimeException(
                'The PDF to preview was not found.'
            );
        }

        $this->ensureOutputDirectory($outputPath);

        $command = [
            $this->binary(),
            '-dNOPAUSE',
            '-dBATCH',
            '-dSAFER',
            '-dQUIET',
            '-sDEVICE=png16m',
            '-dFirstPage='.$page,
            '-dLastPage='.$page,
            '-r'.$resolution,
            '-dTextAlphaBits=4',
            '-dGraphicsAlphaBits=4',
            '-sOutputFile='.$outputPath,
            $inputPath,
        ];

        $this->run($command, $timeout, $outputPath, 'Ghostscript page preview failed');
    }

    /**
     * Count the number of pages in a PDF.
     */
    public function pageCount(string $inputPath): int
    {
        $binary = $this->binary();

        if (! is_file($inputPath)) {
            throw new RuntimeException(
                'The PDF to inspect was not found.'
            );
        }

        $pdfPath = str_replace('\\', '/', $inputPath);

        $process = new Process([
            $binary,
            '-q',
            '-dNODISPLAY',
            '-dNOSAFER',
            '-c',
            "({$pdfPath}) (r) file runpdfbegin pdfpagecount = quit",
        ]);

        $process->setTimeout(60);
        $process->setIdleTimeout(null);
        $process->run();

        if (! $process->isSuccessful()) {
            $message = trim(
                $process->getErrorOutput()
                    ?: $process->getOutput()
            );

            throw new RuntimeException(
                $message !== ''
                    ? 'Ghostscript could not count PDF pages: '.$message
                    : 'Ghostscript could not count PDF pages.'
            );
        }

        $output = trim($process->getOutput());

        preg_match('/(\d+)/', $output, $matches);

        $count = isset($matches[1]) ? (int) $matches[1] : 0;

        if ($count <= 0) {
            throw new RuntimeException(
                'Ghostscript could not determine the PDF page count.'
            );
        }

        return $count;
    }

    /**
     * Extract a subset of pages into a new PDF.
     *
     * Handles arbitrary page selections by extracting each page with
     * Ghostscript and merging the results when the selection is not a
     * single contiguous range.
     *
     * @param  array<int, int>  $pages  1-based page numbers in output order
     */
    public function extractPages(
        string $inputPath,
        string $outputPath,
        array $pages,
        int $timeout = 180,
    ): void {
        if ($pages === []) {
            throw new RuntimeException(
                'At least one page must be selected to extract.'
            );
        }

        $pages = array_values(array_unique($pages));

        $this->ensureOutputDirectory($outputPath);

        /*
         * Single contiguous range can be done in one Ghostscript run.
         */
        if (count($pages) === 1) {
            $this->extractContiguousRange(
                $inputPath,
                $outputPath,
                $pages[0],
                $pages[0],
                $timeout
            );

            return;
        }

        $isContiguous = $pages === range($pages[0], $pages[count($pages) - 1]);

        if ($isContiguous) {
            $this->extractContiguousRange(
                $inputPath,
                $outputPath,
                $pages[0],
                $pages[count($pages) - 1],
                $timeout
            );

            return;
        }

        $temporaryFiles = [];

        try {
            foreach ($pages as $page) {
                $temporary = $outputPath.'.page-'.$page.'.tmp.pdf';

                $this->extractContiguousRange(
                    $inputPath,
                    $temporary,
                    $page,
                    $page,
                    $timeout
                );

                $temporaryFiles[] = $temporary;
            }

            $this->merge($temporaryFiles, $outputPath, $timeout);
        } finally {
            foreach ($temporaryFiles as $temporary) {
                if (is_file($temporary)) {
                    @unlink($temporary);
                }
            }
        }
    }

    private function extractContiguousRange(
        string $inputPath,
        string $outputPath,
        int $firstPage,
        int $lastPage,
        int $timeout = 180,
    ): void {
        $binary = $this->binary();

        $command = [
            $binary,
            '-dNOPAUSE',
            '-dBATCH',
            '-dSAFER',
            '-dQUIET',
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.5',
            '-dFirstPage='.$firstPage,
            '-dLastPage='.$lastPage,
            '-sOutputFile='.$outputPath,
            $inputPath,
        ];

        $this->run($command, $timeout, $outputPath, 'Ghostscript page extraction failed');
    }

    private function binary(): string
    {
        $binary = (string) config(
            'pdf-compressor.ghostscript_path'
        );

        if ($binary === '') {
            throw new RuntimeException(
                'Ghostscript executable path is not configured.'
            );
        }

        return $binary;
    }

    private function ensureOutputDirectory(string $outputPath): void
    {
        $outputDirectory = dirname($outputPath);

        if (
            ! is_dir($outputDirectory)
            && ! mkdir($outputDirectory, 0775, true)
            && ! is_dir($outputDirectory)
        ) {
            throw new RuntimeException(
                'Unable to create the PDF output directory.'
            );
        }
    }

    private function run(
        array $command,
        int $timeout,
        string $outputPath,
        string $errorPrefix,
    ): void {
        $process = new Process($command);
        $process->setTimeout($timeout);
        $process->setIdleTimeout(null);

        $process->run();

        if (! $process->isSuccessful()) {
            $message = trim(
                $process->getErrorOutput()
                    ?: $process->getOutput()
            );

            throw new RuntimeException(
                $message !== ''
                    ? $errorPrefix.': '.$message
                    : $errorPrefix.'.'
            );
        }

        clearstatcache(true, $outputPath);

        if (
            ! is_file($outputPath)
            || ! is_readable($outputPath)
            || (int) filesize($outputPath) <= 0
        ) {
            throw new RuntimeException(
                'Ghostscript did not create a valid output PDF.'
            );
        }
    }
}
