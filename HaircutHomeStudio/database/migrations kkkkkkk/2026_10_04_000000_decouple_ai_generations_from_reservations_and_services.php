<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_generations', function (Blueprint $table): void {
            $table->string('style_label', 120)->nullable();
            $table->string('style_category', 80)->nullable();
        });

        DB::table('ai_generations')
            ->whereNotNull('servicio_id')
            ->orderBy('id')
            ->get(['id', 'servicio_id'])
            ->each(function (object $generation): void {
                $service = DB::table('servicios')
                    ->join('categorias', 'categorias.id', '=', 'servicios.categoria_id')
                    ->where('servicios.id', $generation->servicio_id)
                    ->first(['servicios.nombre', 'categorias.slug']);

                if ($service) {
                    DB::table('ai_generations')->where('id', $generation->id)->update([
                        'style_label' => $service->nombre,
                        'style_category' => $service->slug,
                    ]);
                }
            });

        Schema::table('reservas', function (Blueprint $table): void {
            $table->string('imagen_simulada')->nullable()->after('foto');
        });

        DB::table('reservas')
            ->whereNotNull('ai_generation_id')
            ->orderBy('id')
            ->get(['id', 'ai_generation_id'])
            ->each(function (object $reservation): void {
                $source = DB::table('ai_generations')
                    ->where('id', $reservation->ai_generation_id)
                    ->value('output_path');

                if (! $source || ! Storage::disk('local')->exists($source)) {
                    return;
                }

                $extension = pathinfo($source, PATHINFO_EXTENSION);
                $destination = 'reservas/simulaciones/'.Str::uuid().'.'.($extension ?: 'jpg');
                if (! Storage::disk('local')->copy($source, $destination)) {
                    throw new RuntimeException('No fue posible conservar una simulación asociada a una reserva.');
                }

                DB::table('reservas')->where('id', $reservation->id)->update([
                    'imagen_simulada' => $destination,
                ]);
            });

        Schema::table('reservas', function (Blueprint $table): void {
            $table->dropForeign(['ai_generation_id']);
            $table->dropIndex('reservas_ai_generation_idx');
            $table->dropColumn('ai_generation_id');
        });

        Schema::table('ai_generations', function (Blueprint $table): void {
            $table->dropForeign(['servicio_id']);
            $table->dropIndex('ai_service_created_idx');
            $table->dropColumn('servicio_id');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Esta migración conserva imágenes de reservas de forma independiente y no admite reversión automática.');
    }
};
