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
        Schema::create('HISTORIAL_MOD', function (Blueprint $table) {
            $table->integer('id_historial_mod', true);
            $table->string('detalle_modificacion', 1000);
            $table->integer('id_usuario');
            $table->string('Nombre_usuario', 150);
            $table->timestamp('fecha')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('HISTORIAL_MOD');
    }
};
