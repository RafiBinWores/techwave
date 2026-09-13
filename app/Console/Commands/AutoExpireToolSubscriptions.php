<?php

namespace App\Console\Commands;

use App\Events\ToolSubscriptionUpdated;
use App\Models\ToolSubscription;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('subscriptions:auto-expire')]
#[Description('Automatically expire active tool subscriptions whose period has passed')]
class AutoExpireToolSubscriptions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $subscriptions = ToolSubscription::query()
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        $count = 0;

        foreach ($subscriptions as $subscription) {
            $subscription->update(['status' => 'expired']);

            ToolSubscriptionUpdated::dispatch($subscription->fresh(), 'system');

            $count++;
        }

        $this->info("Expired {$count} subscription(s).");

        return Command::SUCCESS;
    }
}
