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
        Schema::create('workspace_project_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_project_id')->constrained('workspace_projects')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('message')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_project_id', 'created_at']);
            $table->index(['workspace_project_id', 'read_at']);
            $table->index('sender_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_project_messages');
    }
};
