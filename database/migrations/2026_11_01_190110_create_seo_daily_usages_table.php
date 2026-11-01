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
        Schema::create('seo_daily_usages', function (Blueprint $table) {
            $table->id();
            $table->string('actor_key', 80);
            $table->date('usage_date')->index();
            $table->unsignedInteger('checks')->default(0);
            $table->unique(['actor_key', 'usage_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_daily_usages');
    }
};
