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
        Schema::create('checkout_payments', function (Blueprint $table) {
            $table->id();
            $table->morphs('payable');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_id', 64)->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('BDT');
            $table->boolean('sandbox');
            $table->string('status')->default('pending');
            $table->boolean('needs_review')->default(false);
            $table->string('session_key')->nullable();
            $table->text('gateway_url')->nullable();
            $table->string('validation_id')->nullable()->unique();
            $table->json('customer');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkout_payments');
    }
};
