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
        Schema::table('proposal_items', function (Blueprint $table) {
            $table->enum('item_type', ['service', 'service_plan', 'service_option', 'pricing_plan', 'custom'])->default('custom')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposal_items', function (Blueprint $table) {
            $table->enum('item_type', ['service', 'service_plan', 'pricing_plan', 'custom'])->default('custom')->change();
        });
    }
};
