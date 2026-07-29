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
        Schema::create('about_page_settings', function (Blueprint $table) {
            $table->id();
            $table->json('hero')->nullable();
            $table->json('stats')->nullable();
            $table->json('who_we_are')->nullable();
            $table->json('mission_vision')->nullable();
            $table->json('why_choose_us')->nullable();
            $table->json('expertise')->nullable();
            $table->json('timeline')->nullable();
            $table->json('leadership')->nullable();
            $table->json('experts')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('about_page_settings');
    }
};
