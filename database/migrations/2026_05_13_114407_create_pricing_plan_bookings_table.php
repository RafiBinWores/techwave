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
        Schema::create('pricing_plan_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('pricing_plan_id')->constrained('pricing_plans')->cascadeOnDelete();
            $table->string('booking_no')->unique();
            $table->string('billing_cycle')->default('yearly');
            $table->string('company_name')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('company_email')->nullable();
            $table->decimal('plan_price', 12, 2)->nullable();
            $table->decimal('requested_price', 12, 2)->nullable();
            $table->decimal('quoted_price', 12, 2)->nullable();
            $table->text('user_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('pricing_order_id')->nullable()->constrained('pricing_orders')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_plan_bookings');
    }
};
