<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Corrige imagen_simulada: estaba como DATE y debe guardar la ruta del archivo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('RESERVA', function (Blueprint $table): void {
            $table->string('imagen_simulada', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('RESERVA', function (Blueprint $table): void {
            $table->date('imagen_simulada')->nullable()->change();
        });
    }
};
