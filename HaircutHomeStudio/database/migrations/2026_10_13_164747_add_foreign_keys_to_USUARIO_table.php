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
        Schema::table('USUARIO', function (Blueprint $table) {
            $table->foreign(['id_rol'], 'fk_usuario_rol')->references(['id_rol'])->on('ROL')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('USUARIO', function (Blueprint $table) {
            $table->dropForeign('fk_usuario_rol');
        });
    }
};
