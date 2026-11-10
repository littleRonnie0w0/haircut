<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $usuario = auth()->user();
        if ($usuario?->esStaff() && ! $usuario->esAdminImagen()) {
            return redirect()->to($usuario->rutaInicio());
        }

        return view('home', [
            'PRODUCTO' => Producto::query()->where('disponibilidad', true)->orderBy('id_producto')->limit(3)->get(),
            'collage' => [
                ['src' => 'img/collage/03.jpg', 'alt' => 'Balayage liso'],
                ['src' => 'img/collage/05.jpg', 'alt' => 'Balayage platino'],
                ['src' => 'img/collage/08.jpg', 'alt' => 'Corte undercut rubio'],
                ['src' => 'img/collage/01.png', 'alt' => 'Ondas cobrizas'],
                ['src' => 'img/collage/07.jpg', 'alt' => 'Balayage miel'],
                ['src' => 'img/collage/04.jpg', 'alt' => 'Mechas y rulos'],
                ['src' => 'img/collage/06.png', 'alt' => 'Color fantasía azul'],
                ['src' => 'img/collage/10.jpg', 'alt' => 'Corte y color rojo'],
                ['src' => 'img/collage/02.jpg', 'alt' => 'Mechas y ondas'],
                ['src' => 'img/collage/09.jpg', 'alt' => 'Color violeta y rosa'],
            ],
            'resenas' => [
                ['texto' => 'Maravillosa la atención y por supuesto el profesionalismo de Ricardo. Top top.', 'nombre' => 'Carolina Escalante', 'inicial' => 'C', 'tiempo' => 'Hace 2 años', 'estrellas' => 5],
                ['texto' => 'Excelente, 100% recomendable.', 'nombre' => 'Cliente verificada', 'inicial' => 'N', 'tiempo' => 'Google', 'estrellas' => 5],
                ['texto' => 'Excelente atención. Buenos precios. Ricos productos.', 'nombre' => 'M. M.', 'inicial' => 'M', 'tiempo' => 'Google', 'estrellas' => 5],
                ['texto' => 'Muy buena experiencia, atención cercana y resultados impecables.', 'nombre' => 'Cliente local', 'inicial' => 'A', 'tiempo' => 'Google', 'estrellas' => 5],
                ['texto' => 'Ricardo es muy profesional, siempre quedo feliz con mi cabello.', 'nombre' => 'Visitante frecuente', 'inicial' => 'V', 'tiempo' => 'Google', 'estrellas' => 5],
                ['texto' => 'Ambiente acogedor y personalizado, ideal para relajarse mientras te cuidan.', 'nombre' => 'Clienta Melipilla', 'inicial' => 'K', 'tiempo' => 'Google', 'estrellas' => 5],
                ['texto' => 'Buenos precios y productos de calidad. Volveré sin duda.', 'nombre' => 'J. A. S. F.', 'inicial' => 'J', 'tiempo' => 'Google', 'estrellas' => 4],
                ['texto' => 'Atención excelente y resultados hermosos en coloración.', 'nombre' => 'M. Q. C.', 'inicial' => 'M', 'tiempo' => 'Google', 'estrellas' => 5],
            ],
        ]);
    }
}
