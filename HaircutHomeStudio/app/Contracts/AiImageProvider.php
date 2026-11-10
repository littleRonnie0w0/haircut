<?php

namespace App\Contracts;

use App\Data\AiImageResult;

interface AiImageProvider
{
    public function edit(string $imageBytes, string $mimeType, string $prompt): AiImageResult;
}
