<?php

namespace App\Jobs;

use App\Services\Products\ProductRenewalReminders;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendProductRenewalReminder implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $uniqueFor = 3600;

    public function __construct(public int $purchaseId, public string $kind, public string $period) {}

    public function uniqueId(): string
    {
        return $this->purchaseId.':'.$this->kind.':'.$this->period;
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(): void
    {
        app(ProductRenewalReminders::class)->deliver($this->purchaseId, $this->kind, $this->period);
    }
}
