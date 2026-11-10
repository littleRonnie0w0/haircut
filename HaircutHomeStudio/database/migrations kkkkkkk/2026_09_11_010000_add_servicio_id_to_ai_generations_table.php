<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_generations', function (Blueprint $table) {
            $table->unsignedInteger('servicio_id')->nullable()->after('reserva_id');
            $table->foreign('servicio_id')->references('id')->on('servicios')->nullOnDelete();
            $table->index(['servicio_id', 'created_at'], 'ai_service_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ai_generations', function (Blueprint $table) {
            $table->dropForeign(['servicio_id']);
            $table->dropIndex('ai_service_created_idx');
            $table->dropColumn('servicio_id');
        });
    }
};
