<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Producto;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function index(): View
    {
        return view('admin.catalogo.index', [
            'productos' => Producto::query()->with('categoria')->orderBy('id_producto')->get(),
            'categorias' => Categoria::query()->orderBy('id_categoria')->get(),
            'proximoOrden' => ((int) Producto::query()->max('id_producto')) + 1,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);

        Producto::create([
            'producto' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? '',
            'precio' => (int) $datos['precio'],
            'imagen' => $this->media->store($request->file('imagen'), 'prod_'),
            'disponibilidad' => true,
            'id_categoria' => $datos['categoria_id'],
        ]);

        return back()->with('success', 'Producto agregado al catálogo.');
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $datos = $this->validar($request, true);
        $imagenAnterior = $producto->imagen;
        $imagenNueva = $this->media->store($request->file('imagen'), 'prod_');

        $payload = [
            'producto' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? '',
            'precio' => (int) $datos['precio'],
            'disponibilidad' => $request->boolean('activo'),
            'id_categoria' => $datos['categoria_id'],
        ];

        if ($imagenNueva) {
            $payload['imagen'] = $imagenNueva;
        }

        $producto->update($payload);

        if ($imagenNueva) {
            $this->media->delete($imagenAnterior);
        }

        return back()->with('success', 'Producto actualizado.');
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        $imagen = $producto->imagen;
        $producto->delete();
        $this->media->delete($imagen);

        return back()->with('success', 'Producto eliminado.');
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, bool $edicion = false): array
    {
        return $request->validate([
            'categoria_id' => ['required', 'integer', Rule::exists('CATEGORIA', 'id_categoria')],
            'nombre' => ['required', 'string', 'max:50'],
            'descripcion' => ['nullable', 'string', 'max:200'],
            'precio' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:3072'],
        ]);
    }
}
