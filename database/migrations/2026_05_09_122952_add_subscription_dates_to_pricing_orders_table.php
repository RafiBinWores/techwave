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
        Schema::table('pricing_orders', function (Blueprint $table) {
            $table->timestamp('starts_at')->nullable()->after('paid_at');
            $table->timestamp('expires_at')->nullable()->after('starts_at');

            $table->index('starts_at');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricing_orders', function (Blueprint $table) {
            $table->dropIndex(['starts_at']);
            $table->dropIndex(['expires_at']);

            $table->dropColumn([
                'starts_at',
                'expires_at',
            ]);
        });
    }
};
