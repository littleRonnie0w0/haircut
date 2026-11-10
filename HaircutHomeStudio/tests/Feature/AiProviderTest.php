<?php

namespace Tests\Feature;

use App\Contracts\AiImageProvider;
use App\Exceptions\AiProviderException;
use App\Jobs\GenerateHairPreview;
use App\Models\AiGeneration;
use App\Models\Categoria;
use App\Models\Servicio;
use App\Models\User;
use App\Services\AiPromptService;
use App\Services\GeminiImageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_gemini_provider_sends_a_closed_prompt_and_extracts_the_image(): void
    {
        config()->set('ai.api_key', 'test-key');
        config()->set('ai.endpoint', 'https://generativelanguage.googleapis.com/v1beta');
        config()->set('ai.model', 'gemini-3.1-flash-image');
        $image = $this->png();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'responseId' => 'response-123',
                'candidates' => [[
                    'content' => ['parts' => [[
                        'inlineData' => ['mimeType' => 'image/png', 'data' => base64_encode($image)],
                    ]]],
                    'finishReason' => 'STOP',
                ]],
            ]),
        ]);

        $result = app(GeminiImageProvider::class)->edit($image, 'image/png', 'closed internal prompt');

        $this->assertSame($image, $result->bytes);
        $this->assertSame('image/png', $result->mimeType);
        $this->assertSame('response-123', $result->requestId);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-image:generateContent'
                && $request->hasHeader('x-goog-api-key', 'test-key')
                && data_get($request->data(), 'contents.0.parts.0.text') === 'closed internal prompt'
                && data_get($request->data(), 'generationConfig.responseModalities.0') === 'IMAGE'
                && data_get($request->data(), 'store') === false;
        });
    }

    public function test_generation_job_stores_a_private_result_with_fake_provider(): void
    {
        Storage::fake('local');
        config()->set('ai.provider', 'fake');
        $usuario = User::factory()->create();
        $image = $this->png();
        Storage::disk('local')->put('ai/input/test.png', $image);
        $generation = AiGeneration::create([
            'usuario_id' => $usuario->id,
            'preset' => 'balayage_miel',
            'input_path' => 'ai/input/test.png',
            'input_hash' => hash('sha256', $image),
            'status' => 'pending',
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'expires_at' => now()->addHours(72),
        ]);

        app(GenerateHairPreview::class, ['generationId' => $generation->id])
            ->handle(app(AiImageProvider::class), app(AiPromptService::class));

        $generation->refresh();
        $this->assertSame('completed', $generation->status);
        $this->assertNotNull($generation->output_path);
        $this->assertStringStartsWith('demo-', $generation->provider_request_id);
        Storage::disk('local')->assertExists($generation->output_path);
    }

    public function test_prompt_uses_the_exact_catalog_service_definition(): void
    {
        $categoria = Categoria::create(['nombre' => 'Color', 'slug' => 'color']);
        $servicio = Servicio::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Balayage',
            'duracion_min' => 300,
            'precio' => 45000,
            'activo' => true,
        ]);

        $prompt = app(AiPromptService::class)->forService($servicio);

        $this->assertStringContainsString('honey highlights', $prompt);
        $this->assertStringContainsString('Preserve identity', $prompt);
        $this->assertStringNotContainsString('Botox', $prompt);
    }

    public function test_gemini_provider_distinguishes_model_access_denial_from_invalid_key(): void
    {
        config()->set('ai.api_key', 'valid-but-restricted-key');
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 403,
                    'status' => 'PERMISSION_DENIED',
                    'message' => 'Billing or model access is required.',
                ],
            ], 403),
        ]);

        try {
            app(GeminiImageProvider::class)->edit($this->png(), 'image/png', 'closed prompt');
            $this->fail('Expected provider access denial.');
        } catch (AiProviderException $exception) {
            $this->assertSame('provider_access_denied', $exception->errorCode);
        }
    }

    private function png(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
    }
}
