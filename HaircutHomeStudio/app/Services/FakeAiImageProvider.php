<?php

namespace App\Services;

use App\Contracts\AiImageProvider;
use App\Data\AiImageResult;
use Illuminate\Support\Str;

class FakeAiImageProvider implements AiImageProvider
{
    public function edit(string $imageBytes, string $mimeType, string $prompt): AiImageResult
    {
        return new AiImageResult($imageBytes, $mimeType, 'demo-'.Str::uuid());
    }
}
