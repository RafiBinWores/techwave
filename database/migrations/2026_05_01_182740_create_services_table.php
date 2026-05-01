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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            
            $table->string('card_title');
            $table->string('detail_title');
            $table->string('slug')->unique();

            $table->string('icon')->nullable();
            $table->string('image')->nullable();

            $table->text('short_description');
            $table->longText('overview');

            $table->json('benefits');
            $table->json('included_items');
            $table->json('tags')->nullable();

            $table->string('audience_title')->nullable();
            $table->text('audience_detail')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
