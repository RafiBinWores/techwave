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
        Schema::create('product_quote_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name', 160);
            $table->string('plan_name', 160);
            $table->string('name', 160);
            $table->string('email');
            $table->string('phone', 60)->nullable();
            $table->string('company', 160)->nullable();
            $table->text('requirements');
            $table->string('status')->default('new')->index();
            $table->unsignedInteger('requested_devices')->nullable();
            $table->unsignedInteger('requested_sensors')->nullable();
            $table->decimal('proposed_budget', 12, 2)->nullable();
            $table->string('billing_period', 10)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_quote_requests');
    }
};
