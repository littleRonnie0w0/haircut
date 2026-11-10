<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateHairPreview;
use App\Models\AiGeneration;
use App\Models\Servicio;
use App\Models\TipoServicio;
use App\Models\User;
use App\Services\AiPromptService;
use App\Services\AiQuotaService;
use App\Services\PhotoSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AiStudioController extends Controller
{
    public function __construct(
        private readonly AiQuotaService $quota,
        private readonly PhotoSanitizer $sanitizer,
        private readonly AiPromptService $prompts,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        if ($this->adminSinAccesoAi($request->user())) {
            return redirect()->route('staff.welcome');
        }

        return $this->view($request);
    }

    public function show(Request $request, AiGeneration $generation): View|RedirectResponse
    {
        if ($this->adminSinAccesoAi($request->user())) {
            return redirect()->route('staff.welcome');
        }

        $this->ensureOwner($request, $generation);

        return $this->view($request, $generation);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->adminSinAccesoAi($request->user())) {
            return redirect()->route('staff.welcome');
        }

        if (! $this->available()) {
            return back()->with('error', 'El simulador todavía no está habilitado.');
        }

        $data = $request->validate([
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:8192',
                'dimensions:max_width=8192,max_height=8192',
            ],
            'servicio_id' => [
                'required',
                'integer',
                Rule::exists('SERVICIO', 'id_servicio')->where('disponibilidad', true),
            ],
            'consent' => ['accepted'],
        ], [
            'photo.required' => 'Toma una foto o elige una de la galería.',
            'photo.image' => 'El archivo debe ser una imagen válida.',
            'photo.mimes' => 'La fotografía debe estar en formato JPG, PNG o WEBP.',
            'photo.max' => 'La fotografía no puede superar 8 MB.',
            'photo.dimensions' => 'La fotografía es demasiado grande. Prueba otra o usa la cámara de la app.',
            'servicio_id.required' => 'Selecciona un servicio.',
            'servicio_id.exists' => 'El servicio seleccionado no está disponible.',
            'consent.accepted' => 'Debes aceptar el procesamiento temporal de la fotografía.',
        ]);

        $servicio = Servicio::query()
            ->with('tipoServicio')
            ->whereKey($data['servicio_id'])
            ->simulables()
            ->first();
        if (! $servicio) {
            throw ValidationException::withMessages([
                'servicio_id' => 'El servicio seleccionado no está disponible para simulación.',
            ]);
        }

        $sanitized = $this->sanitizer->sanitize($request->file('photo')->get());
        $styleCategory = (string) ($servicio->tipoServicio?->slug ?? '');
        $styleLabel = $servicio->nombre;
        $styleKey = 'style:'.substr(hash('sha256', $styleCategory.'|'.$styleLabel.'|'.$this->prompts->forService($servicio)), 0, 32);
        $ownerHash = AiGeneration::ownerHash($request->user());
        $existing = AiGeneration::query()
            ->delUsuario($request->user())
            ->where('style_category', $styleCategory)
            ->where('style_label', $styleLabel)
            ->where('preset', $styleKey)
            ->where('input_hash', $sanitized['hash'])
            ->where('status', 'completed')
            ->where('expires_at', '>', now())
            ->latest('id_ai_gen')
            ->first();

        if ($existing?->output_path && Storage::disk('local')->exists($existing->output_path)) {
            return redirect()->route('ai.show', $existing)
                ->with('success', 'Ya tenías esta simulación; reutilizamos el resultado sin consumir otro cupo.');
        }

        $inputPath = null;
        try {
            $generation = DB::transaction(function () use ($request, $styleCategory, $styleLabel, $styleKey, $sanitized, $ownerHash, &$inputPath): AiGeneration {
                $usuario = User::query()->lockForUpdate()->findOrFail($request->user()->getKey());
                $quota = $this->quota->status($usuario);
                if (! $quota['allowed']) {
                    throw ValidationException::withMessages(['quota' => $quota['reason']]);
                }

                $inputPath = 'ai/input/'.Str::uuid().'.'.$sanitized['extension'];
                if (! Storage::disk('local')->put($inputPath, $sanitized['bytes'])) {
                    throw ValidationException::withMessages(['photo' => 'No fue posible guardar la fotografía.']);
                }

                return AiGeneration::create([
                    'style_category' => $styleCategory,
                    'style_label' => $styleLabel,
                    'preset' => $styleKey,
                    'input_path' => $inputPath,
                    'input_hash' => $sanitized['hash'],
                    'status' => 'pending',
                    'ip_hash' => $ownerHash,
                    'expires_at' => now()->addHours(max(1, (int) config('ai.retention_hours'))),
                ]);
            });
        } catch (\Throwable $exception) {
            if ($inputPath) {
                Storage::disk('local')->delete($inputPath);
            }
            throw $exception;
        }

        if ((bool) config('ai.process_sync')) {
            GenerateHairPreview::dispatchSync($generation->getKey());
        } else {
            GenerateHairPreview::dispatch($generation->getKey())->onQueue('ai');
        }

        return redirect()->route('ai.show', $generation);
    }

    public function status(Request $request, AiGeneration $generation): JsonResponse
    {
        $this->ensureOwner($request, $generation);

        return response()->json([
            'status' => $generation->status,
            'ready' => $generation->estaLista() && $generation->expires_at->isFuture(),
            'show_url' => route('ai.show', $generation),
        ]);
    }

    public function image(Request $request, AiGeneration $generation, string $kind): Response
    {
        $this->ensureImageAccess($request, $generation);
        abort_if($generation->expires_at->isPast(), 410);

        $path = $kind === 'input' ? $generation->input_path : $generation->output_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $bytes = Storage::disk('local')->get($path);
        $mimeType = @getimagesizefromstring($bytes)['mime'] ?? 'application/octet-stream';
        $extension = match ($mimeType) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
        $download = $request->boolean('download');
        $filename = Str::slug($generation->style_label ?: 'simulacion').'-'.$kind.'.'.$extension;

        return response($bytes, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Disposition' => ($download ? 'attachment' : 'inline').'; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
        ]);
    }

    public function destroyMedia(Request $request, AiGeneration $generation): RedirectResponse
    {
        $this->ensureOwner($request, $generation);
        Storage::disk('local')->delete(array_filter([$generation->input_path, $generation->output_path]));
        $generation->update([
            'input_path' => null,
            'output_path' => null,
            'status' => 'purged',
        ]);

        return redirect()->route('ai.index')->with('success', 'Las imágenes se eliminaron. El registro de uso se conserva para aplicar la cuota.');
    }

    private function view(Request $request, ?AiGeneration $selected = null): View
    {
        $generations = AiGeneration::query()
            ->delUsuario($request->user())
            ->latest('id_ai_gen')
            ->limit(12)
            ->get();
        $serviceCategories = TipoServicio::query()
            ->whereIn('slug', ['corte', 'color'])
            ->whereHas('servicios', fn ($query) => $query->simulables())
            ->with(['servicios' => fn ($query) => $query->simulables()->orderBy('id_servicio')])
            ->orderBy('id_tipo_servicio')
            ->get();

        return view('ai.index', [
            'available' => $this->available(),
            'demoMode' => config('ai.provider') === 'fake',
            'presets' => (array) config('ai.presets'),
            'serviceCategories' => $serviceCategories,
            'serviceCount' => $serviceCategories->sum(fn (TipoServicio $category) => $category->servicios->count()),
            'quota' => $this->quota->status($request->user()),
            'generations' => $generations,
            'selected' => $selected,
        ]);
    }

    private function available(): bool
    {
        return (bool) config('ai.enabled')
            && (config('ai.provider') === 'fake' || filled(config('ai.api_key')));
    }

    private function adminSinAccesoAi(?User $usuario): bool
    {
        return (bool) ($usuario?->esAdmin() && ! $usuario->esAdminImagen());
    }

    private function ensureOwner(Request $request, AiGeneration $generation): void
    {
        abort_unless($generation->perteneceA($request->user()), 404);
    }

    private function ensureImageAccess(Request $request, AiGeneration $generation): void
    {
        abort_unless($generation->perteneceA($request->user()), 404);
    }
}
