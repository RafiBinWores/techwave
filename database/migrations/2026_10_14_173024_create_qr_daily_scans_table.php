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
        Schema::create('qr_daily_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracked_qr_code_id')->constrained('tracked_qr_codes')->cascadeOnDelete();
            $table->date('scan_date');
            $table->unsignedInteger('scan_count')->default(0);
            $table->unique(['tracked_qr_code_id', 'scan_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_daily_scans');
    }
};
