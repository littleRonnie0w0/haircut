<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * Persistencia de disponibilidad fuera de la BD del esquema nuevo.
 */
class AvailabilityStore
{
    private string $path;

    public function __construct()
    {
        $this->path = storage_path('app/disponibilidad.json');
    }

    /** @return array{hora_inicio: string, hora_fin: string, dias_atencion: string, dias_off: list<string>, meses_visibles: list<string>} */
    public function all(): array
    {
        $defaults = [
            'hora_inicio' => (string) config('reglasnegocio.hora_inicio', '10:30:00'),
            'hora_fin' => (string) config('reglasnegocio.hora_fin', '19:30:00'),
            'dias_atencion' => (string) config('reglasnegocio.dias_atencion', '2,3,4,5,6'),
            'dias_off' => [],
            'meses_visibles' => [
                now()->format('Y-m'),
                now()->addMonth()->format('Y-m'),
            ],
        ];

        if (! File::exists($this->path)) {
            return $defaults;
        }

        $decoded = json_decode((string) File::get($this->path), true);
        if (! is_array($decoded)) {
            return $defaults;
        }

        return [
            'hora_inicio' => (string) ($decoded['hora_inicio'] ?? $defaults['hora_inicio']),
            'hora_fin' => (string) ($decoded['hora_fin'] ?? $defaults['hora_fin']),
            'dias_atencion' => (string) ($decoded['dias_atencion'] ?? $defaults['dias_atencion']),
            'dias_off' => array_values(array_unique(array_map('strval', $decoded['dias_off'] ?? []))),
            'meses_visibles' => array_values(array_unique(array_map('strval', $decoded['meses_visibles'] ?? $defaults['meses_visibles']))),
        ];
    }

    /** @param array{hora_inicio?: string, hora_fin?: string, dias_atencion?: string, dias_off?: list<string>, meses_visibles?: list<string>} $patch */
    public function save(array $patch): void
    {
        $data = array_merge($this->all(), $patch);
        File::ensureDirectoryExists(dirname($this->path));
        File::put($this->path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
