<?php

namespace App\Http\Controllers;

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
            'nombre_usuario' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('USUARIO', 'email')->ignore($usuario->id_usuario, 'id_usuario'),
            ],
            'password' => ['nullable', 'confirmed', Password::min(6)],
        ], [
            'email.unique' => 'Ese correo ya está registrado.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        $usuario->nombre_usuario = $datos['nombre_usuario'];
        $usuario->email = strtolower($datos['email']);

        if (! empty($datos['password'])) {
            $usuario->contrasenia = $datos['password'];
        }

        $usuario->save();

        return redirect()->route('perfil.edit')->with('success', 'Tus datos quedaron actualizados.');
    }
}
