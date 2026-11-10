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
        Schema::table('DETALLE_RESERVA', function (Blueprint $table) {
            $table->foreign(['id_reserva'], 'fk_detalle_reserva_reserva')->references(['id_reserva'])->on('RESERVA')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['id_servicio'], 'fk_detalle_reserva_servicio')->references(['id_servicio'])->on('SERVICIO')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('DETALLE_RESERVA', function (Blueprint $table) {
            $table->dropForeign('fk_detalle_reserva_reserva');
            $table->dropForeign('fk_detalle_reserva_servicio');
        });
    }
};
