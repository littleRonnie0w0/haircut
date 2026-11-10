<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $usuario = auth()->user();
        if ($usuario?->esStaff() && ! $usuario->esAdminImagen()) {
            return redirect()->to($usuario->rutaInicio());
        }

        return view('productos.index', [
            'productos' => Producto::query()->where('disponibilidad', true)->orderBy('id_producto')->get(),
        ]);
    }
}
