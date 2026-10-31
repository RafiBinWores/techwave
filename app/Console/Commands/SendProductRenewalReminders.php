<?php

namespace App\Console\Commands;

use App\Models\ProductPurchase;
use App\Services\Products\ProductRenewalReminders;
use Illuminate\Console\Command;

class SendProductRenewalReminders extends Command
{
    protected $signature = 'products:send-renewal-reminders';

    protected $description = 'Queue product subscription reminder emails at seven days and 24 hours before expiry';

    public function handle(ProductRenewalReminders $reminders): int
    {
        $count = 0;
        ProductPurchase::with(['user', 'product', 'plan'])->where('status', 'active')
            ->whereIn('billing_cycle', ['monthly', 'yearly'])->whereNotNull('invoice_id')->whereNotNull('verified_at')
            ->where('expires_at', '>', now())->where('expires_at', '<=', now()->addDays(7))
            ->chunkById(100, function ($purchases) use ($reminders, &$count): void {
                foreach ($purchases as $purchase) {
                    if ($reminders->queue($purchase)) {
                        $count++;
                    }
                }
            });
        $this->info('Prepared '.$count.' product renewal reminder(s).');

        return self::SUCCESS;
    }
}
