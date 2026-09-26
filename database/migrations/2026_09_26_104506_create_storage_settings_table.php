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
        Schema::create('storage_settings', function (Blueprint $table) {
            $table->id();
            $table->string('render_source')->default('s3');
            
            $table->string('s3_key')->nullable();
            $table->text('s3_secret')->nullable();
            $table->string('s3_region')->nullable();
            $table->string('s3_bucket')->nullable();
            $table->string('s3_endpoint')->nullable();
            $table->string('s3_url')->nullable();
            $table->boolean('s3_path_style')->default(false);

            $table->string('r2_key')->nullable();
            $table->text('r2_secret')->nullable();
            $table->string('r2_region')->default('auto');
            $table->string('r2_bucket')->nullable();
            $table->string('r2_endpoint')->nullable();
            $table->string('r2_url')->nullable();
            $table->boolean('r2_path_style')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_settings');
    }
};
