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
        Schema::create('TIPO_SERVICIO', function (Blueprint $table) {
            $table->integer('id_tipo_servicio', true);
            $table->string('tipo_servicio', 100);
            $table->string('slug', 80)->nullable();
            $table->string('imagen', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('TIPO_SERVICIO');
    }
};
