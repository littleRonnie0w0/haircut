<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 80);
            $table->string('slug', 80)->default('');
            $table->string('imagen')->nullable();
        });

        Schema::create('servicios', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('categoria_id');
            $table->string('nombre', 120);
            $table->text('descripcion')->nullable();
            $table->string('imagen')->nullable();
            $table->unsignedInteger('duracion_min')->default(60);
            $table->decimal('precio', 10, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->foreign('categoria_id')->references('id')->on('categorias')->cascadeOnDelete();
        });

        Schema::create('configuracion', function (Blueprint $table) {
            $table->increments('id');
            $table->time('hora_inicio')->default('09:00:00');
            $table->time('hora_fin')->default('18:00:00');
            $table->string('dias_atencion', 20)->default('2,3,4,5,6');
        });

        Schema::create('dias_off', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('configuracion_id');
            $table->date('fecha')->unique();
            $table->foreign('configuracion_id')->references('id')->on('configuracion')->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::create('meses_visibles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('configuracion_id');
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');
            $table->unique(['anio', 'mes'], 'uq_mes');
            $table->foreign('configuracion_id')->references('id')->on('configuracion')->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::create('reservas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('usuario_id');
            $table->unsignedInteger('servicio_id');
            $table->date('fecha');
            $table->time('hora');
            $table->string('foto')->nullable();
            $table->enum('lugar', ['salon', 'domicilio'])->default('salon');
            $table->enum('estado', ['pendiente', 'confirmada', 'rechazada', 'cancelada', 'realizada', 'no_asistio'])->default('pendiente');
            $table->timestamp('creado_en')->useCurrent();
            $table->foreign('usuario_id')->references('id')->on('usuarios')->cascadeOnDelete();
            $table->foreign('servicio_id')->references('id')->on('servicios')->restrictOnDelete();
            $table->index(['fecha', 'hora'], 'idx_fecha_hora');
            $table->index('estado', 'idx_estado');
        });

        Schema::create('productos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('categoria_id');
            $table->string('nombre', 120);
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 10, 2)->default(0);
            $table->string('imagen')->nullable();
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->foreign('categoria_id')->references('id')->on('categorias')->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
        Schema::dropIfExists('reservas');
        Schema::dropIfExists('meses_visibles');
        Schema::dropIfExists('dias_off');
        Schema::dropIfExists('configuracion');
        Schema::dropIfExists('servicios');
        Schema::dropIfExists('categorias');
    }
};
