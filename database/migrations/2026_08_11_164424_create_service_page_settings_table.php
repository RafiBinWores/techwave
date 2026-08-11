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
        Schema::create('service_page_settings', function (Blueprint $table) {
            $table->id();
            $table->json('layout')->nullable();
            $table->json('hero')->nullable();
            $table->json('process')->nullable();
            $table->json('why_choose_us')->nullable();
            $table->json('cta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_page_settings');
    }
};
