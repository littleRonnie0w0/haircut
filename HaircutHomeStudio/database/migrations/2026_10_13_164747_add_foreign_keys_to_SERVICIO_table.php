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
        Schema::table('SERVICIO', function (Blueprint $table) {
            $table->foreign(['id_tipo_servicio'], 'fk_servicio_tipo_reserva')->references(['id_tipo_servicio'])->on('TIPO_SERVICIO')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('SERVICIO', function (Blueprint $table) {
            $table->dropForeign('fk_servicio_tipo_reserva');
        });
    }
};
