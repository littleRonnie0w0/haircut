<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table): void {
            $table->unsignedBigInteger('ai_generation_id')->nullable()->after('foto');
            $table->foreign('ai_generation_id')->references('id')->on('ai_generations')->nullOnDelete();
            $table->index('ai_generation_id', 'reservas_ai_generation_idx');
        });

        DB::table('ai_generations')
            ->whereNotNull('reserva_id')
            ->orderBy('id')
            ->get(['id', 'reserva_id'])
            ->each(function (object $generation): void {
                DB::table('reservas')
                    ->where('id', $generation->reserva_id)
                    ->whereNull('ai_generation_id')
                    ->update(['ai_generation_id' => $generation->id]);
            });

        Schema::table('ai_generations', function (Blueprint $table): void {
            $table->dropForeign(['reserva_id']);
            $table->dropColumn('reserva_id');
        });
    }

    public function down(): void
    {
        Schema::table('ai_generations', function (Blueprint $table): void {
            $table->unsignedInteger('reserva_id')->nullable()->after('id');
            $table->foreign('reserva_id')->references('id')->on('reservas')->nullOnDelete();
            $table->index('reserva_id', 'ai_reserva_idx');
        });

        DB::table('reservas')
            ->whereNotNull('ai_generation_id')
            ->orderBy('id')
            ->get(['id', 'ai_generation_id'])
            ->each(function (object $reservation): void {
                DB::table('ai_generations')
                    ->where('id', $reservation->ai_generation_id)
                    ->whereNull('reserva_id')
                    ->update(['reserva_id' => $reservation->id]);
            });

        Schema::table('reservas', function (Blueprint $table): void {
            $table->dropForeign(['ai_generation_id']);
            $table->dropIndex('reservas_ai_generation_idx');
            $table->dropColumn('ai_generation_id');
        });
    }
};
