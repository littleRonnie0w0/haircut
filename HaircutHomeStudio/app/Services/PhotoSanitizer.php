<?php

namespace App\Services;

use InvalidArgumentException;

class PhotoSanitizer
{
    /** @return array{bytes: string, mime_type: string, extension: string, hash: string} */
    public function sanitize(string $bytes): array
    {
        $imageInfo = @getimagesizefromstring($bytes);
        $mimeType = $imageInfo['mime'] ?? null;

        $sanitized = match ($mimeType) {
            'image/jpeg' => $this->stripJpegMetadata($bytes),
            'image/png' => $this->stripPngMetadata($bytes),
            'image/webp' => $this->webpToJpeg($bytes),
            default => throw new InvalidArgumentException('Solo se permiten imágenes JPG, PNG o WEBP.'),
        };

        if (@getimagesizefromstring($sanitized) === false) {
            throw new InvalidArgumentException('La imagen no se pudo validar después de limpiarla.');
        }

        $outMime = str_starts_with($sanitized, "\xFF\xD8") ? 'image/jpeg' : $mimeType;

        return [
            'bytes' => $sanitized,
            'mime_type' => $outMime,
            'extension' => $outMime === 'image/png' ? 'png' : 'jpg',
            'hash' => hash('sha256', $sanitized),
        ];
    }

    private function webpToJpeg(string $bytes): string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            throw new InvalidArgumentException('Este servidor no puede procesar WEBP. Usa JPG o PNG.');
        }

        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            throw new InvalidArgumentException('La imagen WEBP no es válida.');
        }

        ob_start();
        $ok = imagejpeg($image, null, 88);
        imagedestroy($image);
        $jpeg = (string) ob_get_clean();

        if (! $ok || $jpeg === '') {
            throw new InvalidArgumentException('No se pudo convertir la imagen WEBP.');
        }

        return $this->stripJpegMetadata($jpeg);
    }

    private function stripJpegMetadata(string $bytes): string
    {
        if (! str_starts_with($bytes, "\xFF\xD8")) {
            throw new InvalidArgumentException('El archivo JPG no es válido.');
        }

        $result = "\xFF\xD8";
        $position = 2;
        $length = strlen($bytes);

        while ($position < $length) {
            if (ord($bytes[$position]) !== 0xFF || $position + 1 >= $length) {
                throw new InvalidArgumentException('El archivo JPG está incompleto.');
            }

            $marker = ord($bytes[$position + 1]);
            if ($marker === 0xDA) {
                return $result.substr($bytes, $position);
            }

            if ($marker === 0xD9) {
                return $result."\xFF\xD9";
            }

            if ($marker === 0x01 || ($marker >= 0xD0 && $marker <= 0xD7)) {
                $result .= substr($bytes, $position, 2);
                $position += 2;

                continue;
            }

            if ($position + 4 > $length) {
                throw new InvalidArgumentException('El archivo JPG está incompleto.');
            }

            $segmentLength = unpack('n', substr($bytes, $position + 2, 2))[1];
            $totalLength = $segmentLength + 2;
            if ($segmentLength < 2 || $position + $totalLength > $length) {
                throw new InvalidArgumentException('El archivo JPG contiene metadatos inválidos.');
            }

            if (! in_array($marker, [0xE1, 0xED, 0xFE], true)) {
                $result .= substr($bytes, $position, $totalLength);
            }
            $position += $totalLength;
        }

        throw new InvalidArgumentException('El archivo JPG no contiene datos de imagen.');
    }

    private function stripPngMetadata(string $bytes): string
    {
        $signature = "\x89PNG\r\n\x1A\n";
        if (! str_starts_with($bytes, $signature)) {
            throw new InvalidArgumentException('El archivo PNG no es válido.');
        }

        $result = $signature;
        $position = 8;
        $length = strlen($bytes);
        $metadataChunks = ['eXIf', 'tEXt', 'zTXt', 'iTXt'];

        while ($position + 12 <= $length) {
            $dataLength = unpack('N', substr($bytes, $position, 4))[1];
            $chunkLength = 12 + $dataLength;
            if ($position + $chunkLength > $length) {
                throw new InvalidArgumentException('El archivo PNG está incompleto.');
            }

            $type = substr($bytes, $position + 4, 4);
            if (! in_array($type, $metadataChunks, true)) {
                $result .= substr($bytes, $position, $chunkLength);
            }
            $position += $chunkLength;

            if ($type === 'IEND') {
                return $result;
            }
        }

        throw new InvalidArgumentException('El archivo PNG no contiene un cierre válido.');
    }
}
