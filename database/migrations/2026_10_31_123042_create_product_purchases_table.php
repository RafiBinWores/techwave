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
        Schema::create('product_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('renewal_of_id')->nullable()->constrained('product_purchases')->nullOnDelete();

            // Preserve the purchased offer even when the catalog changes.
            $table->string('product_name', 160);
            $table->string('plan_name', 160);
            $table->string('billing_cycle', 20);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('BDT');
            $table->json('features');
            $table->json('limits');
            $table->json('customer');
            $table->string('payment_method', 20);
            $table->string('sender_bkash', 20)->nullable();
            $table->string('transaction_id', 100)->nullable()->unique();
            $table->string('status', 20)->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamp('admin_read_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('renewal_reminder_deliveries')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'product_id', 'status']);
            $table->index(['status', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_purchases');
    }
};
