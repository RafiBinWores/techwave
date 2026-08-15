<?php

namespace App\Services\Concerns;

trait ValidatesPdf
{
    public function validatePdf(string $filePath): array
    {
        if (! is_file($filePath)) {
            return [
                'valid' => false,
                'error' => 'The uploaded PDF file could not be found.',
            ];
        }

        if (! is_readable($filePath)) {
            return [
                'valid' => false,
                'error' => 'The uploaded PDF file cannot be read.',
            ];
        }

        $fileSize = @filesize($filePath);

        if ($fileSize === false || $fileSize <= 0) {
            return [
                'valid' => false,
                'error' => 'The uploaded PDF file is empty.',
            ];
        }

        $header = @file_get_contents(
            $filePath,
            false,
            null,
            0,
            5
        );

        if (
            $header === false
            || ! str_starts_with($header, '%PDF-')
        ) {
            return [
                'valid' => false,
                'error' => 'The uploaded file is not a valid PDF.',
            ];
        }

        return ['valid' => true];
    }
}
