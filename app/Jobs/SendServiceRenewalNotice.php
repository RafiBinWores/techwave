<?php

namespace App\Jobs;

use App\Services\ServiceRenewalNotifications;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendServiceRenewalNotice implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public int $uniqueFor = 3600;

    public function __construct(public int $orderId, public string $kind, public string $period)
    {
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return 'service-renewal:'.$this->orderId.':'.$this->kind.':'.$this->period;
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(ServiceRenewalNotifications $notifications): void
    {
        $notifications->deliver($this->orderId, $this->kind, $this->period);
    }
}
