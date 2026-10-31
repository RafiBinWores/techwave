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
        Schema::create('product_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tool_plan_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name', 160);
            $table->string('badge', 80)->nullable();
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->json('limits')->nullable();
            $table->string('pricing_mode')->default('quotation');
            $table->string('currency', 3)->default('BDT');
            $table->decimal('monthly_price', 12, 2)->nullable();
            $table->decimal('yearly_price', 12, 2)->nullable();
            $table->decimal('one_time_price', 12, 2)->nullable();
            $table->string('monthly_checkout_url', 2048)->nullable();
            $table->string('yearly_checkout_url', 2048)->nullable();
            $table->string('one_time_checkout_url', 2048)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_plans');
    }
};
