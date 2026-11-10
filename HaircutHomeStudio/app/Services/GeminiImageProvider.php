<?php

namespace App\Services;

use App\Contracts\AiImageProvider;
use App\Data\AiImageResult;
use App\Exceptions\AiProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GeminiImageProvider implements AiImageProvider
{
    public function edit(string $imageBytes, string $mimeType, string $prompt): AiImageResult
    {
        $apiKey = (string) config('ai.api_key');
        if ($apiKey === '') {
            throw new AiProviderException('missing_api_key', 'La API de Gemini no está configurada.');
        }

        $model = (string) config('ai.model');
        $endpoint = rtrim((string) config('ai.endpoint'), '/').'/models/'.rawurlencode($model).':generateContent';

        try {
            $response = Http::acceptJson()
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->timeout((int) config('ai.timeout'))
                ->post($endpoint, [
                    'contents' => [[
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt],
                            ['inlineData' => ['mimeType' => $mimeType, 'data' => base64_encode($imageBytes)]],
                        ],
                    ]],
                    'generationConfig' => [
                        'responseModalities' => ['IMAGE'],
                        'imageConfig' => ['aspectRatio' => '4:5', 'imageSize' => '1K'],
                        'thinkingConfig' => ['thinkingLevel' => 'MINIMAL'],
                    ],
                    'store' => false,
                ]);
        } catch (ConnectionException $exception) {
            throw new AiProviderException('connection_error', 'No fue posible conectar con Gemini.');
        }

        if (! $response->successful()) {
            $errorStatus = strtoupper((string) $response->json('error.status'));
            $errorMessage = strtolower((string) $response->json('error.message'));
            $code = match (true) {
                $response->status() === 401,
                $errorStatus === 'UNAUTHENTICATED',
                str_contains($errorMessage, 'api key not valid') => 'invalid_api_key',
                $response->status() === 403 => 'provider_access_denied',
                $response->status() === 429 => 'provider_quota',
                $response->status() === 400 => 'invalid_provider_request',
                default => 'provider_error_'.$response->status(),
            };
            throw new AiProviderException($code, 'Gemini no pudo procesar la simulación.');
        }

        $json = $response->json();
        $part = collect(data_get($json, 'candidates.0.content.parts', []))
            ->first(fn (array $part) => isset($part['inlineData']['data']));

        if (! is_array($part)) {
            $finishReason = (string) data_get($json, 'candidates.0.finishReason', 'NO_IMAGE');
            throw new AiProviderException('no_image_'.strtolower($finishReason), 'Gemini no devolvió una imagen.');
        }

        $decoded = base64_decode((string) data_get($part, 'inlineData.data'), true);
        if ($decoded === false || $decoded === '') {
            throw new AiProviderException('invalid_image_response', 'Gemini devolvió una imagen inválida.');
        }

        return new AiImageResult(
            $decoded,
            (string) data_get($part, 'inlineData.mimeType', 'image/jpeg'),
            $response->header('x-request-id') ?: data_get($json, 'responseId'),
        );
    }
}
