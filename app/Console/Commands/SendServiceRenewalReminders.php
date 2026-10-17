<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\ServiceRenewalNotifications;
use Illuminate\Console\Command;

class SendServiceRenewalReminders extends Command
{
    protected $signature = 'service-orders:send-renewal-notices';

    protected $description = 'Queue service renewal reminders, expiry notices, and unsent verified-payment confirmations';

    public function handle(ServiceRenewalNotifications $notifications): int
    {
        $count = 0;
        Order::query()->where('order_type', 'service')->whereIn('billing_cycle', ['monthly', 'yearly'])
            ->where('payment_status', 'paid')->whereIn('status', ['pending', 'active'])->whereNotNull('end_date')
            ->where(fn ($query) => $query->where('end_date', '<=', today()->addDays(7))->orWhereNotNull('renewal_paid_at'))
            ->chunkById(100, function ($orders) use ($notifications, &$count): void {
                foreach ($orders as $order) {
                    if ($order->renewal_paid_at && $notifications->queue($order, 'confirmed')) {
                        $count++;
                    }
                    $kind = $order->isExpired() ? 'expired' : 'reminder';
                    if ($notifications->queue($order, $kind)) {
                        $count++;
                    }
                }
            });
        $this->info('Prepared '.$count.' service renewal notice(s).');

        return self::SUCCESS;
    }
}
