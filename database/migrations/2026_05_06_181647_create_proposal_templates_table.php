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
        Schema::create('proposal_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Default Template');
            $table->string('subject_prefix')->default('Proposal');
            $table->string('title')->default('Service Proposal');
            $table->string('greeting')->default('Dear valued customer,');
            $table->text('intro_text')->nullable();
            $table->text('footer_text')->nullable();
            $table->text('terms_text')->nullable();
            $table->string('brand_color')->default('#0F52BA');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposal_templates');
    }
};
