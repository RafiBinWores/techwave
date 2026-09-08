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
        Schema::create('workspace_label_task', function (Blueprint $table) {
            $table->foreignId('task_id')->constrained('workspace_tasks')->cascadeOnDelete();
            $table->foreignId('label_id')->constrained('workspace_labels')->cascadeOnDelete();

            $table->primary(['task_id', 'label_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_label_task');
    }
};
