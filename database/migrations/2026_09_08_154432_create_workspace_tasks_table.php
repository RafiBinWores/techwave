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
        Schema::create('workspace_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('workspace_projects')->cascadeOnDelete();
            $table->foreignId('parent_task_id')->nullable()->constrained('workspace_tasks')->cascadeOnDelete();
            $table->string('identifier')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('attachments')->nullable();
            $table->string('status', 20)->default('todo');
            $table->string('priority', 20)->default('none');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->date('due_date')->nullable();
            $table->integer('estimated_hours')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_tasks');
    }
};
