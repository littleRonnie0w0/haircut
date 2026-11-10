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
        Schema::table('PRODUCTO', function (Blueprint $table) {
            $table->foreign(['id_categoria'], 'fk_producto_categoria')->references(['id_categoria'])->on('CATEGORIA')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('PRODUCTO', function (Blueprint $table) {
            $table->dropForeign('fk_producto_categoria');
        });
    }
};
