<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Reserva extends Model
{
    public const CREATED_AT = 'fecha_creacion_reserva';

    public const UPDATED_AT = null;

    /** @var array<string, string> */
    public const ESTADOS_UI = [
        'En aprovación' => 'pendiente',
        'Agendada' => 'confirmada',
        'Rechazada' => 'rechazada',
        'Cancelada' => 'cancelada',
        'Realizada' => 'realizada',
        'No asistió' => 'no_asistio',
    ];

    protected $primaryKey = 'id_reserva';

    protected $table = 'RESERVA';

    protected $fillable = ['hora', 'fecha', 'foto', 'imagen_simulada', 'id_usuario', 'id_estado_reserva', 'id_tipo_reserva'];

    protected $hidden = ['imagen_simulada'];

    protected $appends = ['estado', 'lugar', 'servicio_id'];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'fecha_creacion_reserva' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function estadoReserva(): BelongsTo
    {
        return $this->belongsTo(EstadoReserva::class, 'id_estado_reserva');
    }

    public function tipoReserva(): BelongsTo
    {
        return $this->belongsTo(TipoReserva::class, 'id_tipo_reserva');
    }

    public function detallesReserva(): HasMany
    {
        return $this->hasMany(DetalleReserva::class, 'id_reserva');
    }

    public function detalleReserva(): HasOne
    {
        return $this->hasOne(DetalleReserva::class, 'id_reserva');
    }

    public function servicio(): HasOneThrough
    {
        return $this->hasOneThrough(
            Servicio::class,
            DetalleReserva::class,
            'id_reserva',
            'id_servicio',
            'id_reserva',
            'id_servicio',
        );
    }

    protected function estado(): Attribute
    {
        return Attribute::get(function (): string {
            $nombre = $this->estadoReserva?->estado_reserva;

            return self::ESTADOS_UI[$nombre] ?? strtolower((string) $nombre);
        });
    }

    protected function lugar(): Attribute
    {
        return Attribute::get(function (): string {
            $tipo = strtolower((string) ($this->tipoReserva?->tipo_reserva ?? ''));

            return str_contains($tipo, 'domicilio') ? 'domicilio' : 'salon';
        });
    }

    protected function servicioId(): Attribute
    {
        return Attribute::get(fn (): ?int => $this->detalleReserva?->id_servicio);
    }

    public function scopeConEstado(Builder $query, string|array $estadosUi): Builder
    {
        $estadosUi = (array) $estadosUi;
        $nombres = array_keys(array_filter(
            self::ESTADOS_UI,
            fn (string $ui) => in_array($ui, $estadosUi, true),
        ));

        return $query->whereHas('estadoReserva', fn (Builder $estado) => $estado->whereIn('estado_reserva', $nombres));
    }

    public static function idEstadoUi(string $estadoUi): ?int
    {
        $nombre = array_search($estadoUi, self::ESTADOS_UI, true);
        if ($nombre === false) {
            return null;
        }

        return EstadoReserva::query()->where('estado_reserva', $nombre)->value('id_estado_reserva');
    }

    public function asignarEstadoUi(string $estadoUi): bool
    {
        $id = self::idEstadoUi($estadoUi);
        if ($id === null) {
            return false;
        }

        return $this->update(['id_estado_reserva' => $id]);
    }

    public function puedeCancelar(): bool
    {
        if (! in_array($this->estadoReserva?->estado_reserva, ['En aprovación', 'Agendada'], true)
            || $this->fecha === null
            || $this->hora === null) {
            return false;
        }

        return $this->fecha->copy()
            ->setTimeFromTimeString($this->hora)
            ->greaterThanOrEqualTo(now()->addMinutes(10));
    }

    /**
     * Rutas de fotos de referencia (1 o 2). Compatible con valor string legado.
     *
     * @return list<string>
     */
    public function rutasFoto(): array
    {
        $raw = trim((string) ($this->attributes['foto'] ?? ''));
        if ($raw === '') {
            return [];
        }

        if (str_starts_with($raw, '[')) {
            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) {
                return [];
            }

            return array_values(array_filter(
                array_map(static fn ($item): string => trim((string) $item), $decoded),
                static fn (string $path): bool => $path !== '',
            ));
        }

        return [$raw];
    }

    public static function codificarFotos(array $rutas): ?string
    {
        $rutas = array_values(array_filter(array_map(
            static fn ($item): string => trim((string) $item),
            $rutas,
        )));

        return match (count($rutas)) {
            0 => null,
            1 => $rutas[0],
            default => json_encode(array_slice($rutas, 0, 2), JSON_UNESCAPED_SLASHES),
        };
    }
}
