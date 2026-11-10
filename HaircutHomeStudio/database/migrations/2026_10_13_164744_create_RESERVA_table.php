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
        Schema::create('RESERVA', function (Blueprint $table) {
            $table->integer('id_reserva', true);
            $table->time('hora');
            $table->date('fecha');
            $table->string('imagen_simulada', 255)->nullable();
            $table->timestamp('fecha_creacion_reserva')->nullable()->useCurrent();
            $table->string('foto')->nullable();
            $table->integer('id_usuario')->index('fk_reserva_usuario');
            $table->integer('id_estado_reserva')->index('fk_reserva_estado_reserva');
            $table->integer('id_tipo_reserva')->index('fk_reserva_tipo_reserva');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('RESERVA');
    }
};
