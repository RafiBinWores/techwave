<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Invoices\OrderInvoiceSync;
use Illuminate\Console\Command;

class BackfillOrderInvoices extends Command
{
    protected $signature = 'invoices:backfill-orders';

    protected $description = 'Save missing order invoices without sending email';

    public function handle(OrderInvoiceSync $sync): int
    {
        $count = 0;
        Order::query()->whereDoesntHave('invoice')->chunkById(100, function ($orders) use ($sync, &$count) {
            foreach ($orders as $order) {
                $sync->sync($order, onlyMissing: true);
                $count++;
            }
        });

        $this->info("Saved {$count} missing order invoices.");

        return self::SUCCESS;
    }
}
