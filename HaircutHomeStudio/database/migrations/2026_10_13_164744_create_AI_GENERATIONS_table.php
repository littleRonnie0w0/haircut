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
        Schema::create('AI_GENERATIONS', function (Blueprint $table) {
            $table->bigIncrements('id_ai_gen');
            $table->string('preset', 60);
            $table->string('input_path')->nullable();
            $table->string('output_path')->nullable();
            $table->char('input_hash', 64);
            $table->string('status', 20)->default('Pendiente');
            $table->string('provider_request_id')->nullable();
            $table->string('error_code', 80)->nullable();
            $table->char('ip_hash', 64);
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('expires_at');
            $table->string('style_label', 120)->nullable();
            $table->string('style_category', 80)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('AI_GENERATIONS');
    }
};
