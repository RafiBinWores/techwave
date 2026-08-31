<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneVisits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'visits:prune {--days= : Number of days of visit history to keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete raw visit records older than the configured retention window';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days');

        if (! is_numeric($days) || (int) $days < 1) {
            $days = (int) config('visits.retention_days', 180);
        }

        if ($days < 1) {
            $this->info('Visit pruning is disabled (VISITS_RETENTION_DAYS is 0).');

            return Command::SUCCESS;
        }

        $cutoff = now()->subDays($days);

        $this->info("Pruning visits older than {$days} day(s) (before {$cutoff}).");

        $deleted = DB::table('visits')
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Deleted {$deleted} visit record(s).");

        return Command::SUCCESS;
    }
}
