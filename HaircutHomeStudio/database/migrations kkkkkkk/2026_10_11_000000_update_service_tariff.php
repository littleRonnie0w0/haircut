<?php

use Database\Seeders\CatalogoServiciosSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicios', function (Blueprint $table): void {
            $table->boolean('precio_desde')->default(false);
        });

        if (DB::table('servicios')->exists()) {
            (new CatalogoServiciosSeeder)->run();
        }
    }

    public function down(): void
    {
        Schema::table('servicios', function (Blueprint $table): void {
            $table->dropColumn('precio_desde');
        });
    }
};
