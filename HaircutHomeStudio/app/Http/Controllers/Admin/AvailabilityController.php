<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AvailabilityService;
use App\Services\AvailabilityStore;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AvailabilityController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly AvailabilityStore $store,
    ) {}

    public function edit(Request $request): View
    {
        $anio = max(2026, $request->integer('anio', (int) now()->format('Y')));
        $mes = $request->integer('mes', (int) now()->format('n'));
        if ($mes < 1 || $mes > 12) {
            $mes = (int) now()->format('n');
        }

        $visibles = $this->availability->mesesVisiblesMapa();
        $anioTecho = max(
            2035,
            $anio,
            (int) now()->format('Y') + 9,
            collect(array_keys($visibles))->map(fn (string $ym) => (int) substr($ym, 0, 4))->max() ?: 0
        );

        return view('admin.disponibilidad', [
            'anio' => $anio,
            'mes' => $mes,
            'configuracion' => $this->availability->configuracion(),
            'diasAtencion' => $this->availability->diasAtencion(),
            'offs' => $this->availability->diasOffDelMes($anio, $mes),
            'semanas' => $this->availability->calendario($anio, $mes),
            'visibles' => $visibles,
            'anioTecho' => $anioTecho,
        ]);
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $datos = $request->validate([
            'anio' => ['required', 'integer', 'min:2026', 'max:2100'],
            'mes' => ['required', 'integer', 'between:1,12'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'dias' => ['nullable', 'array'],
            'dias.*' => ['integer', 'between:2,6'],
            'off' => ['nullable', 'array'],
            'off.*' => ['integer', 'between:1,31'],
            'meses_visibles' => ['nullable', 'array'],
            'meses_visibles.*' => ['date_format:Y-m'],
        ]);

        $dias = array_values(array_unique(array_map('intval', $datos['dias'] ?? [])));
        sort($dias);

        $inicio = CarbonImmutable::create((int) $datos['anio'], (int) $datos['mes'], 1);
        $fin = $inicio->endOfMonth()->toDateString();
        $inicioStr = $inicio->toDateString();

        $offs = collect($this->store->all()['dias_off'])
            ->reject(fn (string $fecha) => $fecha >= $inicioStr && $fecha <= $fin)
            ->values();

        foreach (array_unique(array_map('intval', $datos['off'] ?? [])) as $numeroDia) {
            if ($numeroDia > $inicio->daysInMonth) {
                continue;
            }
            $fecha = $inicio->setDay($numeroDia);
            if (! in_array($fecha->dayOfWeekIso, [1, 7], true)) {
                $offs->push($fecha->toDateString());
            }
        }

        $meses = collect($datos['meses_visibles'] ?? [])
            ->push(now()->format('Y-m'))
            ->unique()
            ->filter(function (string $valor): bool {
                $fecha = CarbonImmutable::createFromFormat('!Y-m', $valor);

                return $fecha->greaterThanOrEqualTo(now()->startOfMonth());
            })
            ->values()
            ->all();

        $this->availability->guardarConfiguracion(
            $datos['hora_inicio'].':00',
            $datos['hora_fin'].':00',
            $dias ? implode(',', $dias) : '2,3,4,5,6',
            $offs->unique()->values()->all(),
            $meses,
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => 'Días libres actualizados.',
            ]);
        }

        return redirect()->route('peluquero.disponibilidad.edit', ['anio' => $datos['anio'], 'mes' => $datos['mes']])
            ->with('success', 'Disponibilidad guardada.');
    }
}
