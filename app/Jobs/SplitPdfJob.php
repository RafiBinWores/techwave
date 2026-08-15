<?php

namespace App\Jobs;

use App\Models\SplitPdf;
use App\Services\PdfSplitService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SplitPdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 210;

    public int $tries = 1;

    public bool $failOnTimeout = true;

    public function __construct(
        public int $splitPdfId
    ) {
        $this->onQueue('pdf-compression');
    }

    public function handle(
        PdfSplitService $service
    ): void {
        $splitPdf = SplitPdf::find($this->splitPdfId);

        if (! $splitPdf) {
            return;
        }

        if ($splitPdf->isExpired()) {
            $splitPdf->deleteOriginalFile();
            $splitPdf->deleteOutputFile();
            $splitPdf->delete();

            return;
        }

        if ($splitPdf->isCompleted()) {
            return;
        }

        $splitPdf->update([
            'status' => 'processing',
            'error_message' => null,
        ]);

        $service->split($splitPdf);
    }

    public function failed(?Throwable $exception): void
    {
        $splitPdf = SplitPdf::find($this->splitPdfId);

        if (! $splitPdf) {
            return;
        }

        $splitPdf->update([
            'status' => 'failed',
            'error_message' => $exception?->getMessage()
                ?? 'PDF split job failed.',
            'processed_at' => now(),
        ]);
    }
}
