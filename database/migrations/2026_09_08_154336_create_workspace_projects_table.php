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
        Schema::create('workspace_projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->longText('details')->nullable();
            $table->string('status', 20)->default('backlog');
            $table->string('priority', 20)->default('none');
            $table->string('image_path')->nullable();
            $table->string('icon', 100)->nullable();
            $table->string('icon_color', 30)->nullable();
            $table->date('start_date')->nullable();
            $table->date('target_date')->nullable();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_projects');
    }
};
