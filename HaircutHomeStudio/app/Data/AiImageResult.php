<?php

namespace App\Data;

class AiImageResult
{
    public function __construct(
        public readonly string $bytes,
        public readonly string $mimeType,
        public readonly ?string $requestId = null,
    ) {}
}
