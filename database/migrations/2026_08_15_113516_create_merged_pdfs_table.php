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
        Schema::create('merged_pdfs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->string('output_name');
            $table->string('output_path')->nullable();
            $table->unsignedBigInteger('output_size')->nullable();
            $table->json('source_names')->nullable();
            $table->json('source_paths')->nullable();
            $table->boolean('is_backup_enabled')->default(false);
            $table->string('status', 30)->default('pending');
            $table->text('error_message')->nullable();
            $table->string('job_id')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('backup_expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['session_id', 'status']);
            $table->index(['is_backup_enabled', 'backup_expires_at']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merged_pdfs');
    }
};
