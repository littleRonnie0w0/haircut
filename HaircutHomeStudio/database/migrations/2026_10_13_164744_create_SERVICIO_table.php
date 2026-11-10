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
        Schema::create('SERVICIO', function (Blueprint $table) {
            $table->integer('id_servicio', true);
            $table->string('nombre_servicio', 120);
            $table->string('descripcion', 200);
            $table->string('imagen')->nullable();
            $table->unsignedInteger('duracion_min')->nullable();
            $table->integer('precio');
            $table->boolean('disponibilidad')->nullable()->default(true);
            $table->integer('id_tipo_servicio')->index('fk_servicio_tipo_reserva');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('SERVICIO');
    }
};
