<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:purge-scheduled-accounts')]
#[Description('Command description')]
class PurgeScheduledAccounts extends Command
{
    protected $signature = 'accounts:purge-scheduled';

    protected $description = 'Permanently delete accounts whose scheduled_deletion_at has passed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
         $users = User::query()
            ->whereNotNull('scheduled_deletion_at')
            ->where('scheduled_deletion_at', '<=', now())
            ->get();

        $count = 0;

        foreach ($users as $user) {
            $user->whmcsAccount?->delete();
            $user->toolSubscriptions()->delete();
            $user->savedInvoiceProducts()->delete();
            $user->customers()->delete();
            $user->delete();

            $count++;
        }

        $this->info("Purged {$count} scheduled account(s).");

        return Command::SUCCESS;
    }
}
