<?php

namespace App\Jobs;

use App\Contracts\AiImageProvider;
use App\Exceptions\AiProviderException;
use App\Models\AiGeneration;
use App\Services\AiPromptService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class GenerateHairPreview implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 150;

    public function __construct(public readonly int $generationId) {}

    public function handle(AiImageProvider $provider, AiPromptService $prompts): void
    {
        $generation = AiGeneration::query()->find($this->generationId);
        if (! $generation || $generation->status !== 'pending' || ! $generation->input_path) {
            return;
        }

        $generation->update(['status' => 'processing', 'error_code' => null]);

        try {
            $input = Storage::disk('local')->get($generation->input_path);
            $imageInfo = @getimagesizefromstring($input);
            $mimeType = $imageInfo['mime'] ?? null;
            if (! in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
                throw new AiProviderException('invalid_stored_image', 'La imagen almacenada no es válida.');
            }

            $result = $provider->edit($input, $mimeType, $prompts->forGeneration($generation));
            if (strlen($result->bytes) > (int) config('ai.max_output_bytes')) {
                throw new AiProviderException('output_too_large', 'La imagen generada supera el tamaño permitido.');
            }

            $outputInfo = @getimagesizefromstring($result->bytes);
            $outputMime = $outputInfo['mime'] ?? null;
            if (! in_array($outputMime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                throw new AiProviderException('invalid_output_image', 'La respuesta no contiene una imagen válida.');
            }

            $extension = match ($outputMime) {
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };
            $outputPath = 'ai/output/'.Str::uuid().'.'.$extension;
            if (! Storage::disk('local')->put($outputPath, $result->bytes)) {
                throw new AiProviderException('storage_error', 'No fue posible guardar la simulación.');
            }

            $generation->update([
                'status' => 'completed',
                'output_path' => $outputPath,
                'provider_request_id' => $result->requestId,
                'completed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $generation->update([
                'status' => 'failed',
                'error_code' => $exception instanceof AiProviderException ? $exception->errorCode : 'internal_error',
                'completed_at' => now(),
            ]);
            Storage::disk('local')->delete($generation->input_path);

            Log::warning('AI hair preview failed.', [
                'generation_id' => $generation->getKey(),
                'error_code' => $generation->error_code,
            ]);
        }
    }
}
