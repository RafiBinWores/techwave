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
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('offer_price', 10, 2)->nullable()->after('final_price');
            $table->text('negotiation_note')->nullable()->after('offer_price');
            $table->timestamp('client_responded_at')->nullable()->after('negotiation_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
           $table->dropColumn(['offer_price', 'negotiation_note', 'client_responded_at']);
        });
    }
};
