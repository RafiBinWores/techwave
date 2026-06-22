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
        Schema::table('invoice_themes', function (Blueprint $table) {
            $table->string('pdf_background_image')->nullable()->after('preview_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_themes', function (Blueprint $table) {
            $table->dropColumn('pdf_background_image');
        });
    }
};
