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
        Schema::create('DETALLE_RESERVA', function (Blueprint $table) {
            $table->integer('id_detalle_reserva', true);
            $table->integer('monto_total');
            $table->integer('id_servicio')->index('fk_detalle_reserva_servicio');
            $table->integer('id_reserva')->index('fk_detalle_reserva_reserva');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('DETALLE_RESERVA');
    }
};
