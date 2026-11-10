<?php

namespace App\Http\Controllers;

use App\Models\AiGeneration;
use App\Models\DetalleReserva;
use App\Models\Reserva;
use App\Models\Servicio;
use App\Models\TipoServicio;
use App\Services\AvailabilityService;
use App\Services\ReservationWorkflowService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ReservationController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly ReservationWorkflowService $workflow,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        if ($this->bloquearStaff($request)) {
            return redirect()->route('staff.welcome');
        }

        $this->workflow->actualizarRealizadas();

        $reservas = Reserva::query()
            ->with(['servicio', 'estadoReserva', 'tipoReserva'])
            ->where('id_usuario', $request->user()->id_usuario)
            ->whereHas('estadoReserva', function ($query) {
                $query->whereNotIn('estado_reserva', ['Cancelada', 'No asistió']);
            })
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->get();

        return view('reservas.index', compact('reservas'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        if ($this->bloquearStaff($request)) {
            return redirect()->route('staff.welcome');
        }

        $aiGeneration = $this->selectedAiGeneration($request);
        $servicioDesdeAi = $this->servicioDesdeAi($aiGeneration);

        $categorias = TipoServicio::query()
            ->with(['servicios' => fn ($query) => $query->where('disponibilidad', true)->orderBy('id_servicio')])
            ->orderBy('id_tipo_servicio')
            ->get()
            ->filter(fn (TipoServicio $tipo) => $tipo->servicios->isNotEmpty())
            ->values();

        $servicioId = $request->integer('serv') > 0
            ? $request->integer('serv')
            : (int) ($servicioDesdeAi?->id ?? 0);

        $servicio = Servicio::query()
            ->with('tipoServicio')
            ->where('disponibilidad', true)
            ->find($servicioId) ?? $servicioDesdeAi;

        $catSlug = $request->string('cat')->toString()
            ?: (string) ($aiGeneration?->style_category ?? '')
            ?: (string) ($servicio?->tipoServicio?->slug ?? '');

        $categoria = $categorias->firstWhere('slug', $catSlug)
            ?? ($servicio ? $categorias->firstWhere('id_tipo_servicio', $servicio->id_tipo_servicio) : null)
            ?? $categorias->first();

        $servicio = $servicio && $servicio->id_tipo_servicio === $categoria?->id_tipo_servicio
            ? $servicio
            : ($servicioDesdeAi && $servicioDesdeAi->id_tipo_servicio === $categoria?->id_tipo_servicio
                ? $servicioDesdeAi
                : $categoria?->servicios->first());

        if (! $categoria || ! $servicio) {
            return redirect()->route('home')->with('error', 'Elige un servicio para continuar.');
        }

        if ($aiGeneration && ! $request->filled('paso')) {
            $request->merge(['paso' => 'fecha']);
        }

        $anio = $request->integer('anio', (int) now()->format('Y'));
        $mes = $request->integer('mes', (int) now()->format('n'));
        if (! $this->availability->mesEsVisible($anio, $mes)) {
            $anio = (int) now()->format('Y');
            $mes = (int) now()->format('n');
        }

        // La fecha elegida se conserva al cambiar de mes; el calendario sigue el mes pedido.
        $fecha = $this->fechaSeleccionada($request->string('fecha')->toString(), $anio, $mes);

        $slots = $fecha ? $this->availability->slotsDisponibles($fecha, $servicio) : [];
        $hora = in_array($request->string('hora')->toString(), $slots, true)
            ? $request->string('hora')->toString()
            : '';
        $todosSlots = $fecha ? $this->availability->generarSlots(60, $fecha) : [];
        $sugerencias = $fecha
            ? $this->availability->sugerencias($servicio, $fecha)
            : ['misma' => null, 'otra' => null, 'largo' => $this->availability->duracionHoras($servicio->duracion_min) >= 3];
        $semanas = $this->availability->calendario($anio, $mes);
        $estados = [];
        foreach ($semanas as $semana) {
            foreach (array_filter($semana) as $dia) {
                $fechaDia = CarbonImmutable::create($anio, $mes, $dia);
                $estados[$dia] = $this->availability->estadoDia($fechaDia, $servicio);
            }
        }
        return view('reservas.create', [
            'categorias' => $categorias,
            'categoria' => $categoria,
            'servicio' => $servicio,
            'anio' => $anio,
            'mes' => $mes,
            'fecha' => $fecha?->toDateString() ?? '',
            'hora' => $hora,
            'lugar' => $request->string('lugar')->toString() === 'domicilio' ? 'domicilio' : 'salon',
            'slots' => $slots,
            'todosSlots' => $todosSlots,
            'sugerencias' => $sugerencias,
            'semanas' => $semanas,
            'estados' => $estados,
            'mesesVisibles' => $this->availability->mesesVisiblesCliente(),
            'aiGeneration' => $aiGeneration,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->bloquearStaff($request)) {
            return redirect()->route('staff.welcome');
        }

        $datos = $request->validate([
            'servicio_id' => ['required', 'integer', Rule::exists('SERVICIO', 'id_servicio')->where('disponibilidad', true)],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'hora' => ['required', 'date_format:H:i'],
            'lugar' => ['required', Rule::in(['salon', 'domicilio'])],
            'fotos' => ['nullable', 'array', 'max:2'],
            'fotos.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:3072'],
            'ai_generation_id' => ['nullable', 'integer'],
        ], [
            'fotos.max' => 'Puedes adjuntar como máximo 2 imágenes.',
            'fotos.*.image' => 'Solo se permiten imágenes (JPG, PNG, WEBP o GIF). No PDF.',
            'fotos.*.mimes' => 'La foto debe estar en formato JPG, PNG, WEBP o GIF.',
            'fotos.*.max' => 'Cada imagen no puede superar 3 MB.',
        ]);

        $servicio = Servicio::query()->with('tipoServicio')->findOrFail($datos['servicio_id']);
        $foto = null;
        $rutasFoto = [];
        $imagenSimulada = null;

        try {
            DB::transaction(function () use ($request, $datos, $servicio, &$foto, &$rutasFoto, &$imagenSimulada): void {
                Reserva::query()->whereDate('fecha', $datos['fecha'])->lockForUpdate()->get();
                if (! in_array($datos['hora'], $this->availability->slotsDisponibles($datos['fecha'], $servicio), true)) {
                    throw new DomainException('La hora seleccionada ya no está disponible.');
                }

                $aiGeneration = null;
                if (! empty($datos['ai_generation_id'])) {
                    $aiGeneration = AiGeneration::query()
                        ->whereKey($datos['ai_generation_id'])
                        ->delUsuario($request->user())
                        ->where('status', 'completed')
                        ->where('expires_at', '>', now())
                        ->lockForUpdate()
                        ->first();
                    if (! $aiGeneration?->output_path || ! Storage::disk('local')->exists($aiGeneration->output_path)) {
                        throw ValidationException::withMessages([
                            'ai_generation_id' => 'La simulación seleccionada ya no está disponible.',
                        ]);
                    }
                }

                if ($aiGeneration) {
                    $extension = pathinfo($aiGeneration->output_path, PATHINFO_EXTENSION);
                    $imagenSimulada = 'reservas/simulaciones/'.Str::uuid().'.'.($extension ?: 'jpg');
                    if (! Storage::disk('local')->copy($aiGeneration->output_path, $imagenSimulada)) {
                        throw ValidationException::withMessages([
                            'ai_generation_id' => 'No fue posible adjuntar la simulación a la reserva.',
                        ]);
                    }
                }

                foreach (array_slice($request->file('fotos', []) ?: [], 0, 2) as $archivo) {
                    if (! $archivo) {
                        continue;
                    }
                    $guardada = $archivo->store('fotos', 'public');
                    if ($guardada === false) {
                        throw ValidationException::withMessages([
                            'fotos' => 'No fue posible guardar una de las fotos. Intenta nuevamente.',
                        ]);
                    }
                    $rutasFoto[] = $guardada;
                }

                $foto = Reserva::codificarFotos($rutasFoto);

                $reserva = Reserva::create([
                    'id_usuario' => $request->user()->id_usuario,
                    'fecha' => $datos['fecha'],
                    'hora' => $datos['hora'].':00',
                    'foto' => $foto,
                    'imagen_simulada' => $imagenSimulada,
                    'id_estado_reserva' => 1,
                    'id_tipo_reserva' => $datos['lugar'] === 'domicilio' ? 2 : 1,
                ]);

                DetalleReserva::create([
                    'id_reserva' => $reserva->id_reserva,
                    'id_servicio' => $servicio->id_servicio,
                    'monto_total' => $servicio->precio,
                ]);
            });
        } catch (DomainException) {
            $this->removeUncommittedImages($rutasFoto, $imagenSimulada);

            return back()->withInput()->with('error', 'Esa hora ya no está disponible. Elige otra.');
        } catch (Throwable $exception) {
            $this->removeUncommittedImages($rutasFoto, $imagenSimulada);
            throw $exception;
        }

        return redirect()->route('reservas.index')->with('success', 'Reserva enviada. El pago es presencial.');
    }

    public function cancel(Request $request, Reserva $reserva): RedirectResponse
    {
        if ($reserva->id_usuario !== $request->user()->id_usuario) {
            abort(404);
        }

        if (! $reserva->puedeCancelar()) {
            return back()->with('error', 'Ya no se puede cancelar.');
        }

        $reserva->update(['id_estado_reserva' => 4]);

        return back()->with('success', 'Reserva cancelada. El cupo quedó libre.');
    }

    public function simulatedImage(Request $request, Reserva $reserva): Response
    {
        $esDueno = $reserva->id_usuario === $request->user()->id_usuario;
        $esStaff = in_array(strtolower((string) $request->user()->rol?->rol), ['peluquero', 'admin'], true);
        abort_unless($esDueno || $esStaff, 404);
        abort_unless($reserva->imagen_simulada && Storage::disk('local')->exists($reserva->imagen_simulada), 404);

        $bytes = Storage::disk('local')->get($reserva->imagen_simulada);
        $mimeType = @getimagesizefromstring($bytes)['mime'] ?? 'application/octet-stream';

        return response($bytes, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
        ]);
    }

    private function fechaSeleccionada(string $valor, int $anio, int $mes): ?CarbonImmutable
    {
        if ($valor === '') {
            return null;
        }

        try {
            $fecha = CarbonImmutable::createFromFormat('!Y-m-d', $valor);
        } catch (Throwable) {
            return null;
        }

        if ($fecha->format('Y-m-d') !== $valor || ! $this->availability->mesEsVisible($fecha->year, $fecha->month)) {
            return null;
        }

        // Si el calendario muestra otro mes, se descarta el día previo.
        if ((int) $fecha->year !== $anio || (int) $fecha->month !== $mes) {
            return null;
        }

        return $fecha;
    }

    private function selectedAiGeneration(Request $request): ?AiGeneration
    {
        if ($request->integer('ai_generation') < 1) {
            return null;
        }

        $generation = AiGeneration::query()
            ->whereKey($request->integer('ai_generation'))
            ->delUsuario($request->user())
            ->where('status', 'completed')
            ->where('expires_at', '>', now())
            ->first();

        return $generation?->output_path && Storage::disk('local')->exists($generation->output_path)
            ? $generation
            : null;
    }

    private function servicioDesdeAi(?AiGeneration $generation): ?Servicio
    {
        if (! $generation?->style_label) {
            return null;
        }

        $query = Servicio::query()
            ->with('tipoServicio')
            ->where('disponibilidad', true)
            ->where('nombre_servicio', $generation->style_label);

        if ($generation->style_category) {
            $query->whereHas(
                'tipoServicio',
                fn ($tipo) => $tipo->where('slug', $generation->style_category),
            );
        }

        return $query->first()
            ?? Servicio::query()
                ->with('tipoServicio')
                ->where('disponibilidad', true)
                ->where('nombre_servicio', $generation->style_label)
                ->first();
    }

    private function bloquearStaff(Request $request): bool
    {
        $usuario = $request->user();

        return (bool) ($usuario?->esStaff() && ! $usuario->esAdminImagen());
    }

    /** @param  list<string>|string|null  $fotos */
    private function removeUncommittedImages(array|string|null $fotos, ?string $imagenSimulada): void
    {
        $rutas = is_array($fotos) ? $fotos : (is_string($fotos) && $fotos !== '' ? [$fotos] : []);
        foreach ($rutas as $ruta) {
            // Valor JSON legado o path simple.
            if (str_starts_with($ruta, '[')) {
                foreach (json_decode($ruta, true) ?: [] as $item) {
                    if (is_string($item) && $item !== '') {
                        Storage::disk('public')->delete($item);
                    }
                }
                continue;
            }
            Storage::disk('public')->delete($ruta);
        }
        if ($imagenSimulada) {
            Storage::disk('local')->delete($imagenSimulada);
        }
    }
}
