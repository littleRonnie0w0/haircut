<?php

namespace App\Http\Controllers;

use App\Support\CorreoValido;
use App\Support\NombrePersona;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'usuario' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $usuario = $request->user();
        $request->merge([
            'password' => $request->filled('password') ? $request->input('password') : null,
        ]);

        $datos = $request->validate([
            'nombre_usuario' => NombrePersona::rules(required: true),
            'email' => array_merge(
                CorreoValido::rules(required: true),
                [Rule::unique('USUARIO', 'email')->ignore($usuario->id_usuario, 'id_usuario')]
            ),
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ], array_merge(NombrePersona::messages('nombre_usuario'), CorreoValido::messages('email'), [
            'email.unique' => 'Ese correo ya está registrado en otra cuenta.',
            'password.confirmed' => 'Las contraseñas no coinciden. Vuelve a escribirlas para confirmarlas.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres, mayúsculas, minúsculas y un número.',
            'password.mixed' => 'La contraseña debe incluir mayúsculas y minúsculas.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
        ]));

        $usuario->nombre_usuario = $datos['nombre_usuario'];
        $usuario->email = strtolower($datos['email']);

        if (! empty($datos['password'])) {
            $usuario->contrasenia = $datos['password'];
        }

        $usuario->save();

        return redirect()->route('perfil.edit')->with('success', 'Tus datos quedaron actualizados.');
    }
}
