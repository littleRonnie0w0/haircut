<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StaffWelcomeController extends Controller
{
    public function __invoke(Request $request)
    {
        $usuario = $request->user();
        if (! $usuario?->esStaff()) {
            return redirect()->route('home');
        }

        $rol = strtolower((string) ($usuario->rol?->rol ?? ''));

        return view('staff.welcome', [
            'nombre' => $usuario->nombre_usuario ?: 'usuario',
            'rol' => $rol,
        ]);
    }
}
