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
        Schema::create('USUARIO', function (Blueprint $table) {
            $table->integer('id_usuario', true);
            $table->string('email', 100)->unique('email');
            $table->string('contrasenia', 160);
            $table->string('nombre_usuario', 150)->nullable();
            $table->timestamp('fecha_creacion')->nullable()->useCurrent();
            $table->integer('id_rol')->index('fk_usuario_rol');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('USUARIO');
    }
};
