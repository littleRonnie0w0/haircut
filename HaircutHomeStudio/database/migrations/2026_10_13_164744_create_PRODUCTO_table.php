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
        Schema::create('PRODUCTO', function (Blueprint $table) {
            $table->integer('id_producto', true);
            $table->string('producto', 50);
            $table->string('descripcion', 200);
            $table->integer('precio');
            $table->string('imagen')->nullable();
            $table->boolean('disponibilidad')->nullable()->default(true);
            $table->integer('id_categoria')->index('fk_producto_categoria');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('PRODUCTO');
    }
};
