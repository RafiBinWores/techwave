<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('bkash_enabled')->default(false)->after('whatsapp_url');
            $table->boolean('sslcommerz_enabled')->default(false)->after('bkash_enabled');
            $table->string('sslcommerz_store_id')->nullable()->after('sslcommerz_enabled');
            $table->text('sslcommerz_store_password')->nullable()->after('sslcommerz_store_id');
            $table->boolean('sslcommerz_sandbox')->default(true)->after('sslcommerz_store_password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['bkash_enabled', 'sslcommerz_enabled', 'sslcommerz_store_id', 'sslcommerz_store_password', 'sslcommerz_sandbox']);
        });
    }
};
