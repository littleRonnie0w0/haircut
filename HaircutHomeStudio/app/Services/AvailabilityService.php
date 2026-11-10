<?php

namespace App\Services;

use App\Models\Reserva;
use App\Models\Servicio;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AvailabilityService
{
    public function __construct(private readonly AvailabilityStore $store) {}

    public function configuracion(): object
    {
        $data = $this->store->all();

        return (object) [
            'hora_inicio' => $data['hora_inicio'],
            'hora_fin' => $data['hora_fin'],
            'dias_atencion' => $data['dias_atencion'],
        ];
    }

    /** @param list<string> $fechasYmd */
    public function guardarConfiguracion(string $horaInicio, string $horaFin, string $diasAtencion, array $fechasYmd, array $mesesYm): void
    {
        $this->store->save([
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'dias_atencion' => $diasAtencion,
            'dias_off' => array_values(array_unique($fechasYmd)),
            'meses_visibles' => array_values(array_unique($mesesYm)),
        ]);
    }

    /** @return list<int> */
    public function diasOffDelMes(int $anio, int $mes): array
    {
        $inicio = CarbonImmutable::create($anio, $mes, 1)->toDateString();
        $fin = CarbonImmutable::create($anio, $mes, 1)->endOfMonth()->toDateString();

        return collect($this->store->all()['dias_off'])
            ->filter(fn (string $fecha) => $fecha >= $inicio && $fecha <= $fin)
            ->map(fn (string $fecha) => (int) CarbonImmutable::parse($fecha)->day)
            ->values()
            ->all();
    }

    /** @return array<string, true> */
    public function mesesVisiblesMapa(): array
    {
        return collect($this->store->all()['meses_visibles'])
            ->mapWithKeys(fn (string $ym) => [$ym => true])
            ->all();
    }

    public function duracionHoras(int $minutos): int
    {
        return max(1, (int) ceil($minutos / 60));
    }

    /** @return list<int> */
    public function diasAtencion(): array
    {
        $dias = array_map('intval', explode(',', $this->configuracion()->dias_atencion));
        $dias = array_values(array_unique(array_filter($dias, fn (int $dia) => $dia >= 2 && $dia <= 6)));
        sort($dias);

        return $dias ?: [2, 3, 4, 5, 6];
    }

    public function esDiaAtencion(CarbonInterface|string $fecha): bool
    {
        $dia = $this->fecha($fecha);

        return ! in_array($dia->dayOfWeekIso, [1, 7], true)
            && in_array($dia->dayOfWeekIso, $this->diasAtencion(), true);
    }

    public function esDiaOff(CarbonInterface|string $fecha): bool
    {
        return in_array($this->fecha($fecha)->toDateString(), $this->store->all()['dias_off'], true);
    }

    public function mesEsVisible(int $anio, int $mes): bool
    {
        if ($mes < 1 || $mes > 12 || $anio < 2000) {
            return false;
        }

        $solicitado = ($anio * 12) + $mes;
        $actual = ((int) now()->format('Y') * 12) + (int) now()->format('n');

        if ($solicitado < $actual) {
            return false;
        }

        if ($solicitado === $actual) {
            return true;
        }

        return isset($this->mesesVisiblesMapa()[sprintf('%04d-%02d', $anio, $mes)]);
    }

    /** @return list<array{anio: int, mes: int}> */
    public function mesesVisiblesCliente(): array
    {
        $actual = now()->startOfMonth();

        return collect([['anio' => $actual->year, 'mes' => $actual->month]])
            ->concat(
                collect($this->store->all()['meses_visibles'])->map(function (string $ym): array {
                    [$anio, $mes] = array_map('intval', explode('-', $ym));

                    return ['anio' => $anio, 'mes' => $mes];
                })
            )
            ->filter(fn (array $mes) => CarbonImmutable::create($mes['anio'], $mes['mes'], 1)->greaterThanOrEqualTo($actual))
            ->unique(fn (array $mes) => sprintf('%04d-%02d', $mes['anio'], $mes['mes']))
            ->sortBy(fn (array $mes) => ($mes['anio'] * 12) + $mes['mes'])
            ->values()
            ->all();
    }

    // Esta función genera los slots de atención para un día especifico
    // probablemente no tenga que modificarla.
    /** @return list<string> */
    public function generarSlots(int $duracionMinutos, CarbonInterface|string $fecha): array
    {
        $dia = $this->fecha($fecha);
        $configuracion = $this->configuracion();
        $inicio = $dia->setTimeFromTimeString(substr($configuracion->hora_inicio, 0, 5));
        $horaCierre = $dia->dayOfWeekIso === 6 ? '14:30' : substr($configuracion->hora_fin, 0, 5);
        $cierre = $dia->setTimeFromTimeString($horaCierre);
        $duracion = max(60, $duracionMinutos);
        $slots = [];

        while ($inicio->addMinutes($duracion)->lessThanOrEqualTo($cierre)) {
            $slots[] = $inicio->format('H:i');
            $inicio = $inicio->addHour();
        }

        return $slots;
    }

    // esta funcion nos muestra los slots disponibles en un día especifico.
    /** @return list<string> */
    public function slotsDisponibles(CarbonInterface|string $fecha, Servicio $servicio, ?int $exceptoId = null): array
    {
        $dia = $this->fecha($fecha);

        if (! $servicio->activo
            || $dia->startOfDay()->lessThan(now()->startOfDay())
            || ! $this->esDiaAtencion($dia)
            || $this->esDiaOff($dia)
            || ! $this->mesEsVisible($dia->year, $dia->month)) {
            return [];
        }

        return array_values(array_filter(
            $this->generarSlots($servicio->duracion_min, $dia),
            function (string $hora) use ($dia, $servicio, $exceptoId): bool {
                if ($dia->isToday() && $hora <= now()->format('H:i')) {
                    return false;
                }

                return $this->bloqueDisponible($dia, $servicio, $hora, $exceptoId);
            }
        ));
    }

    // Un bloque solo está libre si ninguna reserva pendiente/confirmada ocupa esas horas.
    public function bloqueDisponible(CarbonInterface|string $fecha, Servicio $servicio, string $horaInicio, ?int $exceptoId = null): bool
    {
        $reservas = $this->reservasDelDia($fecha, $exceptoId);
        $nuevo = $this->descriptorServicio($servicio, $horaInicio);
        $horasNuevas = $this->horasDescriptor($nuevo);

        foreach ($reservas as $reserva) {
            $horasOcupadas = $this->horasDescriptor($reserva);
            if (array_intersect($horasNuevas, $horasOcupadas) !== []) {
                return false;
            }
        }

        return true;
    }

    /** @return array{misma: ?string, otra: ?array{fecha: string, hora: string}, largo: bool} */
    public function sugerencias(Servicio $servicio, CarbonInterface|string $fecha): array
    {
        $dia = $this->fecha($fecha);
        $largo = $this->duracionHoras($servicio->duracion_min) >= 3;
        $elegir = static function (array $slots) use ($largo): ?string {
            if ($largo) {
                foreach ($slots as $hora) {
                    if ($hora < '13:00') {
                        return $hora;
                    }
                }
            }

            return $slots[0] ?? null;
        };

        $misma = $elegir($this->slotsDisponibles($dia, $servicio));
        $otra = null;

        for ($indice = 1; $indice <= 31 && $otra === null; $indice++) {
            $otraFecha = $dia->addDays($indice);
            $hora = $elegir($this->slotsDisponibles($otraFecha, $servicio));
            if ($hora !== null) {
                $otra = ['fecha' => $otraFecha->toDateString(), 'hora' => $hora];
            }
        }

        return compact('misma', 'otra', 'largo');
    }

    public function estadoDia(CarbonInterface|string $fecha, Servicio $servicio): string
    {
        $dia = $this->fecha($fecha);
        if ($dia->startOfDay()->lessThan(now()->startOfDay())) {
            return 'pasado';
        }

        return $this->esDiaAtencion($dia)
            && ! $this->esDiaOff($dia)
            && $this->mesEsVisible($dia->year, $dia->month)
            && $this->slotsDisponibles($dia, $servicio) !== []
                ? 'disponible'
                : 'ocupado';
    }

    /** @return list<list<?int>> */
    public function calendario(int $anio, int $mes): array
    {
        $primero = CarbonImmutable::create($anio, $mes, 1);
        $celdas = array_fill(0, $primero->dayOfWeekIso - 1, null);

        for ($dia = 1; $dia <= $primero->daysInMonth; $dia++) {
            $celdas[] = $dia;
        }

        while (count($celdas) % 7 !== 0) {
            $celdas[] = null;
        }

        return array_chunk($celdas, 7);
    }

    private function reservasDelDia(CarbonInterface|string $fecha, ?int $exceptoId): Collection
    {
        return Reserva::query()
            ->with(['servicio.tipoServicio'])
            ->whereDate('fecha', $this->fecha($fecha)->toDateString())
            ->conEstado(['pendiente', 'confirmada'])
            ->when($exceptoId, fn ($consulta) => $consulta->whereKeyNot($exceptoId))
            ->get()
            ->filter(fn (Reserva $reserva) => $reserva->servicio !== null)
            ->map(fn (Reserva $reserva) => $this->descriptorServicio($reserva->servicio, substr((string) $reserva->hora, 0, 5)));
    }

    /** @return array{hora: string, duracion_min: int, categoria_slug: string} */
    private function descriptorServicio(Servicio $servicio, string $hora): array
    {
        $servicio->loadMissing('tipoServicio');

        return [
            'hora' => substr($hora, 0, 5),
            'duracion_min' => (int) $servicio->duracion_min,
            'categoria_slug' => strtolower((string) ($servicio->tipoServicio?->slug ?? '')),
        ];
    }

    /** @param array{hora: string, duracion_min: int, categoria_slug: string} $descriptor
     * @return list<string>
     */
    private function horasDescriptor(array $descriptor): array
    {
        $inicio = CarbonImmutable::createFromFormat('H:i', $descriptor['hora']);
        $horas = [];

        for ($indice = 0; $indice < $this->duracionHoras($descriptor['duracion_min']); $indice++) {
            $horas[] = $inicio->addHours($indice)->format('H:i');
        }

        return $horas;
    }

    private function fecha(CarbonInterface|string $fecha): CarbonImmutable
    {
        return $fecha instanceof CarbonInterface
            ? CarbonImmutable::instance($fecha)
            : CarbonImmutable::createFromFormat('Y-m-d', $fecha)->startOfDay();
    }
}
