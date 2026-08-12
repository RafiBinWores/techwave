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
        Schema::table('services', function (Blueprint $table) {
            $table->string('media_type')->default('image')->after('image');
            $table->string('media_color')->nullable()->after('media_type');
            $table->string('media_gradient_from')->nullable()->after('media_color');
            $table->string('media_gradient_to')->nullable()->after('media_gradient_from');
            $table->integer('media_gradient_angle')->nullable()->default(135)->after('media_gradient_to');

            $table->boolean('show_short_description')->default(true)->after('short_description');
            $table->boolean('show_benefits')->default(true)->after('show_short_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['media_type', 'media_color', 'media_gradient_from', 'media_gradient_to', 'media_gradient_angle', 'show_short_description', 'show_benefits']);
        });
    }
};
