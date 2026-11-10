<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('usuario_id');
            $table->unsignedInteger('reserva_id')->nullable();
            $table->string('preset', 60);
            $table->string('input_path')->nullable();
            $table->string('output_path')->nullable();
            $table->char('input_hash', 64);
            $table->string('status', 20)->default('pending');
            $table->string('provider_request_id')->nullable();
            $table->string('error_code', 80)->nullable();
            $table->string('ip_hash', 64);
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('expires_at');
            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('usuarios')->cascadeOnDelete();
            $table->foreign('reserva_id')->references('id')->on('reservas')->nullOnDelete();
            $table->index(['usuario_id', 'created_at'], 'ai_user_created_idx');
            $table->index(['status', 'created_at'], 'ai_status_created_idx');
            $table->index('expires_at', 'ai_expires_idx');
            $table->index(['input_hash', 'preset'], 'ai_dedup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
    }
};
