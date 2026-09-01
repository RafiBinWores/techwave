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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('invoice_no')->unique();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('company_name')->nullable();
            $table->string('subject');
            $table->text('note')->nullable();
            $table->text('terms')->nullable();
            $table->enum('discount_type', ['none', 'fixed', 'percentage'])->default('none');
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->enum('status', ['draft', 'sent', 'paid', 'partially_paid', 'overdue', 'cancelled'])->default('draft');
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
