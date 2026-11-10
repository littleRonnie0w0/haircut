<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Servicio;
use App\Models\TipoServicio;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function index(Request $request): View
    {
        $categoriaId = $request->integer('cat');
        $categorias = TipoServicio::query()->withCount('servicios')->orderBy('id_tipo_servicio')->get();
        $servicios = Servicio::query()
            ->with('categoria')
            ->when($categoriaId > 0, fn ($query) => $query->where('id_tipo_servicio', $categoriaId))
            ->orderBy('id_tipo_servicio')
            ->orderBy('id_servicio')
            ->get();

        return view('admin.servicios.index', compact('categorias', 'servicios', 'categoriaId'));
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:3072'],
        ]);
        $slug = Str::slug($datos['nombre']);

        if ($slug === '' || TipoServicio::query()->where('slug', $slug)->exists()) {
            return back()->withInput()->with('error', 'No se pudo crear la categoría (¿nombre repetido?).');
        }

        TipoServicio::create([
            'tipo_servicio' => trim($datos['nombre']),
            'slug' => $slug,
            'imagen' => $this->media->store($request->file('imagen'), 'cat_'),
        ]);

        return back()->with('success', 'Categoría creada.');
    }

    public function destroyCategory(TipoServicio $categoria): RedirectResponse
    {
        if (TipoServicio::query()->count() <= 1) {
            return back()->with('error', 'Debe quedar al menos una categoría.');
        }

        $tieneReservas = $categoria->servicios()->whereHas('detalleReservas')->exists();
        if ($tieneReservas) {
            return back()->with('error', 'No se puede eliminar: hay reservas en servicios de esa categoría.');
        }

        $imagenes = $categoria->servicios()->pluck('imagen')->filter()->all();
        $imagenCategoria = $categoria->imagen;
        DB::transaction(function () use ($categoria): void {
            $categoria->servicios()->delete();
            $categoria->delete();
        });

        foreach ($imagenes as $imagen) {
            $this->media->delete($imagen);
        }
        $this->media->delete($imagenCategoria);

        return redirect()->route('admin.servicios.index')->with('success', 'Categoría eliminada.');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validarServicio($request);

        Servicio::create([
            'nombre_servicio' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?: 'Servicio de Haircut Home Studio.',
            'duracion_min' => $datos['duracion_min'],
            'precio' => $datos['precio'],
            'id_tipo_servicio' => $datos['categoria_id'],
            'imagen' => $this->media->store($request->file('imagen'), 'serv_'),
            'disponibilidad' => true,
        ]);

        return $this->volver($request)->with('success', 'Servicio agregado.');
    }

    public function update(Request $request, Servicio $servicio): RedirectResponse
    {
        $datos = $this->validarServicio($request);
        $payload = [
            'nombre_servicio' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?: 'Servicio de Haircut Home Studio.',
            'duracion_min' => $datos['duracion_min'],
            'precio' => $datos['precio'],
            'id_tipo_servicio' => $datos['categoria_id'],
            'disponibilidad' => $request->boolean('activo'),
        ];

        $imagenAnterior = $servicio->imagen;
        $imagenNueva = $this->media->store($request->file('imagen'), 'serv_');
        if ($imagenNueva) {
            $payload['imagen'] = $imagenNueva;
        }
        $servicio->update($payload);

        if ($imagenNueva) {
            $this->media->delete($imagenAnterior);
        }

        return $this->volver($request)->with('success', 'Servicio actualizado.');
    }

    public function destroy(Request $request, Servicio $servicio): RedirectResponse
    {
        if ($servicio->detalleReservas()->exists()) {
            return $this->volver($request)->with('error', 'No se puede borrar: hay reservas asociadas. Desactívalo en su lugar.');
        }

        $imagen = $servicio->imagen;
        $servicio->delete();
        $this->media->delete($imagen);

        return $this->volver($request)->with('success', 'Servicio eliminado.');
    }

    /** @return array<string, mixed> */
    private function validarServicio(Request $request): array
    {
        return $request->validate([
            'categoria_id' => ['required', 'integer', Rule::exists('TIPO_SERVICIO', 'id_tipo_servicio')],
            'nombre' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:200'],
            'duracion_min' => ['required', 'integer', 'min:60', 'max:600'],
            'precio' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'precio_desde' => ['sometimes', 'boolean'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:3072'],
        ]);
    }

    private function volver(Request $request): RedirectResponse
    {
        $categoriaId = $request->integer('volver_cat');

        return redirect()->route('admin.servicios.index', $categoriaId > 0 ? ['cat' => $categoriaId] : []);
    }
}
