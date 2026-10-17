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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('renewal_of_id')->nullable()->constrained('orders')->restrictOnDelete();
            $table->timestamp('renewal_activated_at')->nullable();
            $table->json('renewal_payment_attempts')->nullable();
            $table->string('renewal_payment_validation_id', 100)->nullable()->unique();
            $table->timestamp('renewal_paid_at')->nullable();
            $table->json('renewal_notice_deliveries')->nullable();
            $table->string('renewal_sender_bkash', 20)->nullable();
            $table->string('renewal_manual_transaction_id', 50)->nullable()->unique();
            $table->timestamp('renewal_manual_submitted_at')->nullable();
            $table->timestamp('renewal_manual_verified_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('renewal_of_id');
            $table->dropColumn('renewal_activated_at');
            $table->dropUnique(['renewal_payment_validation_id']);
            $table->dropColumn(['renewal_payment_attempts', 'renewal_payment_validation_id', 'renewal_paid_at']);
            $table->dropColumn('renewal_notice_deliveries');
            $table->dropUnique(['renewal_manual_transaction_id']);
            $table->dropColumn(['renewal_sender_bkash', 'renewal_manual_transaction_id', 'renewal_manual_submitted_at', 'renewal_manual_verified_at']);
        });
    }
};
