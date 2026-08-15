<?php

namespace App\Jobs;

use App\Models\MergedPdf;
use App\Services\PdfMergeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class MergePdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 210;

    public int $tries = 1;

    public bool $failOnTimeout = true;

    public function __construct(
        public int $mergedPdfId
    ) {
        $this->onQueue('pdf-compression');
    }

    public function handle(
        PdfMergeService $service
    ): void {
        $mergedPdf = MergedPdf::find($this->mergedPdfId);

        if (! $mergedPdf) {
            return;
        }

        if ($mergedPdf->isExpired()) {
            $mergedPdf->deleteOutputFile();
            $mergedPdf->delete();

            return;
        }

        if ($mergedPdf->isCompleted()) {
            return;
        }

        $mergedPdf->update([
            'status' => 'processing',
            'error_message' => null,
        ]);

        $service->merge(
            $mergedPdf,
            $mergedPdf->sourceFilePaths()
        );
    }

    public function failed(?Throwable $exception): void
    {
        $mergedPdf = MergedPdf::find($this->mergedPdfId);

        if (! $mergedPdf) {
            return;
        }

        $mergedPdf->update([
            'status' => 'failed',
            'error_message' => $exception?->getMessage()
                ?? 'PDF merge job failed.',
            'processed_at' => now(),
        ]);
    }
}
