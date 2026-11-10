<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $rolId = $request->integer('rol') ?: null;

        $usuarios = User::query()
            ->with('rol')
            ->when($rolId, fn ($query) => $query->where('id_rol', $rolId))
            ->orderBy('nombre_usuario')
            ->orderBy('id_usuario')
            ->get();

        return view('admin.usuarios.index', [
            'usuarios' => $usuarios,
            'roles' => Rol::query()->orderBy('id_rol')->get(),
            'rolId' => $rolId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);

        User::create([
            'nombre_usuario' => $datos['nombre_usuario'],
            'email' => strtolower($datos['email']),
            'contrasenia' => $datos['password'],
            'id_rol' => $datos['id_rol'],
        ]);

        return back()->with('success', 'Persona creada.');
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $datos = $this->validar($request, $usuario);

        $usuario->nombre_usuario = $datos['nombre_usuario'];
        $usuario->email = strtolower($datos['email']);
        $usuario->id_rol = $datos['id_rol'];

        if (! empty($datos['password'])) {
            $usuario->contrasenia = $datos['password'];
        }

        $usuario->save();

        return back()->with('success', 'Persona actualizada.');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($request->user()?->is($usuario)) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        if ($usuario->reservas()->exists()) {
            return back()->with('error', 'No se puede eliminar: la persona tiene reservas asociadas.');
        }

        $usuario->delete();

        return back()->with('success', 'Persona eliminada.');
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, ?User $usuario = null): array
    {
        $edicion = $usuario !== null;
        $request->merge([
            'password' => $request->filled('password') ? $request->input('password') : null,
        ]);

        return $request->validate([
            'nombre_usuario' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('USUARIO', 'email')->ignore($usuario?->id_usuario, 'id_usuario'),
            ],
            'id_rol' => ['required', 'integer', Rule::exists('ROL', 'id_rol')],
            'password' => [
                $edicion ? 'nullable' : 'required',
                'confirmed',
                Password::min(6),
            ],
        ], [
            'email.unique' => 'Ese correo ya está registrado.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);
    }
}
