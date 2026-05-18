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
        Schema::table('service_plans', function (Blueprint $table) {
            $table->boolean('has_monthly_price')->default(false)->after('discount_price');
            $table->decimal('monthly_price', 10, 2)->nullable()->after('has_monthly_price');
            $table->decimal('monthly_discount_price', 10, 2)->nullable()->after('monthly_price');

            $table->boolean('has_yearly_price')->default(false)->after('monthly_discount_price');
            $table->decimal('yearly_price', 10, 2)->nullable()->after('has_yearly_price');
            $table->decimal('yearly_discount_price', 10, 2)->nullable()->after('yearly_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_plans', function (Blueprint $table) {
            $table->dropColumn([
                'has_monthly_price',
                'monthly_price',
                'monthly_discount_price',
                'has_yearly_price',
                'yearly_price',
                'yearly_discount_price',
            ]);
        });
    }
};
