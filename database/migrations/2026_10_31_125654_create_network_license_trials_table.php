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
        Schema::create('network_license_trials', function (Blueprint $table) {
            $table->id();
            // Redemption belongs to the customer, so reinstalling or replacing a product cannot restart it.
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('network_license_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('expires_at');
            $table->json('limits');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_license_trials');
    }
};
