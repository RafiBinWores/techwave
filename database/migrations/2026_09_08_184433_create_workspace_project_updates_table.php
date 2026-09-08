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
        Schema::create('workspace_project_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_project_id')->constrained('workspace_projects')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('workspace_project_updates')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('health', 20)->nullable()->default('on_track');
            $table->string('type', 20)->default('update');
            $table->text('body');
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->index(['workspace_project_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_project_updates');
    }
};
