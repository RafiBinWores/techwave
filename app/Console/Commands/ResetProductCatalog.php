<?php

namespace App\Console\Commands;

use App\Models\NetworkLicense;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ResetProductCatalog extends Command
{
    protected $signature = 'products:reset-catalog {--force : Confirm clearing the product catalog}';

    protected $description = 'Clear the product catalog and quotes while retaining billing records';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->error('Use --force to confirm clearing products, product plans and quote requests.');

            return self::FAILURE;
        }

        if (NetworkLicense::exists()) {
            $this->error('This catalog has issued network licenses. Archive products instead of clearing the catalog.');

            return self::FAILURE;
        }

        $backupPath = 'product-catalog-backups/'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json';
        DB::transaction(function () use ($backupPath): void {
            $protectedTables = ['tool_plans', 'tool_subscriptions', 'invoices', 'checkout_payments'];
            $protectedHashes = [];
            foreach ($protectedTables as $table) {
                $protectedHashes[$table] = hash('sha256', DB::table($table)->orderBy('id')->get()->toJson());
            }
            $snapshot = [];
            foreach (['products', 'product_plans', 'product_quote_requests'] as $table) {
                $snapshot[$table] = DB::table($table)->orderBy('id')->lockForUpdate()->get()->toArray();
            }
            if (! Storage::disk('local')->put($backupPath, json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT))) {
                throw new RuntimeException('Catalog backup failed. No records were cleared.');
            }
            DB::table('product_quote_requests')->delete();
            DB::table('product_plans')->delete();
            DB::table('products')->delete();
            foreach ($protectedHashes as $table => $expectedHash) {
                if (! hash_equals($expectedHash, hash('sha256', DB::table($table)->orderBy('id')->get()->toJson()))) {
                    throw new RuntimeException('Protected billing data changed. Catalog reset rolled back.');
                }
            }
        });
        $this->info('Products, product plans and quote requests cleared.');
        $this->info('Billing plans, subscriptions and payments were not changed.');
        $this->line('Catalog backup: '.Storage::disk('local')->path($backupPath));

        return self::SUCCESS;
    }
}
