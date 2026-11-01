<?php

namespace App\Console\Commands;

use App\Models\SeoReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneSeoReports extends Command
{
    protected $signature = 'seo-reports:prune';

    protected $description = 'Remove expired SEO reports and stale daily counters';

    public function handle(): int
    {
        $deleted = SeoReport::where('created_at', '<', now()->subDays(max(1, (int) config('seo.retention_days', 90))))->delete();
        DB::table('seo_daily_usages')->where('usage_date', '<', now()->timezone(config('app.timezone'))->subDays(7)->toDateString())->delete();
        $this->info('Removed '.$deleted.' expired SEO reports.');

        return self::SUCCESS;
    }
}
