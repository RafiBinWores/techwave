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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->enum('item_type', ['service', 'service_plan', 'service_option', 'pricing_plan', 'custom'])->default('custom');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
