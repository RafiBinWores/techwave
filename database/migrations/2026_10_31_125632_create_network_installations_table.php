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
        Schema::create('network_installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_license_id')->constrained()->cascadeOnDelete();
            $table->uuid('installation_id');
            $table->string('credential_hash', 64)->nullable()->unique();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();
            $table->unique(['network_license_id', 'installation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_installations');
    }
};
