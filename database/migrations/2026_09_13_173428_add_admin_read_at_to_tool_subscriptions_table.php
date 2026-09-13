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
        Schema::table('tool_subscriptions', function (Blueprint $table) {
            $table->timestamp('admin_read_at')->nullable()->after('admin_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tool_subscriptions', function (Blueprint $table) {
            $table->dropColumn('admin_read_at');
        });
    }
};
