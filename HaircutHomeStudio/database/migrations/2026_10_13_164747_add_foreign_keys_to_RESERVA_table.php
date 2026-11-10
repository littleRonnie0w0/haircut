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
        Schema::table('RESERVA', function (Blueprint $table) {
            $table->foreign(['id_estado_reserva'], 'fk_reserva_estado_reserva')->references(['id_estado_reserva'])->on('ESTADO_RESERVA')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['id_tipo_reserva'], 'fk_reserva_tipo_reserva')->references(['id_tipo_reserva'])->on('TIPO_RESERVA')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['id_usuario'], 'fk_reserva_usuario')->references(['id_usuario'])->on('USUARIO')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('RESERVA', function (Blueprint $table) {
            $table->dropForeign('fk_reserva_estado_reserva');
            $table->dropForeign('fk_reserva_tipo_reserva');
            $table->dropForeign('fk_reserva_usuario');
        });
    }
};
