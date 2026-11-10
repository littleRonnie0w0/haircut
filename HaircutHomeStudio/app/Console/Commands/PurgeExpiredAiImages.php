<?php

namespace App\Console\Commands;

use App\Models\AiGeneration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeExpiredAiImages extends Command
{
    protected $signature = 'ai:purge';

    protected $description = 'Elimina fotografías y simulaciones de IA vencidas';

    public function handle(): int
    {
        $stale = AiGeneration::query()
            ->whereIn('status', ['pending', 'processing'])
            ->where('created_at', '<', now()->subMinutes(10))
            ->get();

        foreach ($stale as $generation) {
            Storage::disk('local')->delete(array_filter([$generation->input_path, $generation->output_path]));
            $generation->update([
                'input_path' => null,
                'output_path' => null,
                'status' => 'failed',
                'error_code' => 'stale_job',
                'completed_at' => now(),
            ]);
        }

        $expiredCount = 0;
        AiGeneration::query()
            ->where('expires_at', '<=', now())
            ->where(function ($query): void {
                $query->whereNotNull('input_path')->orWhereNotNull('output_path');
            })
            ->chunkById(100, function ($generations) use (&$expiredCount): void {
                foreach ($generations as $generation) {
                    Storage::disk('local')->delete(array_filter([$generation->input_path, $generation->output_path]));
                    $generation->update([
                        'input_path' => null,
                        'output_path' => null,
                        'status' => 'expired',
                    ]);
                    $expiredCount++;
                }
            });

        $this->info("Simulaciones vencidas: {$expiredCount}. Trabajos atascados: {$stale->count()}.");

        return self::SUCCESS;
    }
}
