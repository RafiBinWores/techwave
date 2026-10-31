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
        Schema::create('products', function (Blueprint $table) {
           $table->id();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->string('summary', 500)->default('');
            $table->text('description')->nullable();

            // Product presentation and optional plan limit definitions.
            $table->string('cover_path')->nullable();
            $table->string('demo_url', 2048)->nullable();
            $table->json('gallery')->nullable();
            $table->json('tutorials')->nullable();
            $table->json('installation_steps')->nullable();
            $table->json('limit_definitions')->nullable();
            $table->boolean('pricing_enabled')->default(false);
            $table->boolean('is_published')->default(false);
            $table->boolean('show_in_navbar')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            // Installer metadata and the retained legacy tool association.
            $table->string('download_access')->default('public');
            $table->foreignId('tool_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('release_path')->nullable();
            $table->string('release_name')->nullable();
            $table->string('release_version', 80)->nullable();
            $table->unsignedBigInteger('release_size')->nullable();
            $table->string('release_sha256', 64)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_published', 'show_in_navbar', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
