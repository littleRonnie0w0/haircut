<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiGeneration;
use App\Models\DetalleReserva;
use App\Models\Reserva;
use App\Models\Rol;
use App\Models\User;
use App\Services\PasswordResetStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Support\CorreoValido;
use App\Support\NombrePersona;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly PasswordResetStore $passwordResets,
    ) {}

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
            'activo' => true,
        ]);

        return back()->with('success', 'Persona creada.');
    }

    public function toggleActivo(Request $request, User $usuario): RedirectResponse
    {
        if ($request->user()?->is($usuario)) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $usuario->activo = ! $usuario->estaActiva();
        $usuario->save();

        if (! $usuario->activo) {
            $this->passwordResets->forget((string) $usuario->email);

            return back()->with('success', "Cuenta de {$usuario->nombre_usuario} desactivada. Solo podrá ver Inicio y Productos.");
        }

        return back()->with('success', "Cuenta de {$usuario->nombre_usuario} reactivada.");
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($request->user()?->is($usuario)) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $nombre = (string) $usuario->nombre_usuario;

        DB::transaction(function () use ($usuario): void {
            $reservas = Reserva::query()
                ->where('id_usuario', $usuario->id_usuario)
                ->get();

            foreach ($reservas as $reserva) {
                foreach ($reserva->rutasFoto() as $ruta) {
                    Storage::disk('public')->delete($ruta);
                }
                if ($reserva->imagen_simulada) {
                    Storage::disk('local')->delete($reserva->imagen_simulada);
                }
            }

            $reservaIds = $reservas->pluck('id_reserva');
            if ($reservaIds->isNotEmpty()) {
                DetalleReserva::query()->whereIn('id_reserva', $reservaIds)->delete();
                Reserva::query()->whereIn('id_reserva', $reservaIds)->delete();
            }

            DB::table('AI_GENERATIONS')
                ->where('ip_hash', AiGeneration::ownerHash($usuario))
                ->delete();

            DB::table('HISTORIAL_MOD')->where('id_usuario', $usuario->id_usuario)->delete();
            DB::table('sessions')->where('user_id', $usuario->id_usuario)->delete();
            $this->passwordResets->forget((string) $usuario->email);

            $usuario->delete();
        });

        return back()->with('success', "{$nombre} y toda su información fueron eliminados de forma permanente.");
    }

    /** @return array<string, mixed> */
    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre_usuario' => NombrePersona::rules(required: true),
            'email' => array_merge(CorreoValido::rules(required: true), [Rule::unique('USUARIO', 'email')]),
            'id_rol' => ['required', 'integer', Rule::exists('ROL', 'id_rol')],
            'password' => [
                'required',
                'confirmed',
                Password::defaults(),
            ],
        ], array_merge(NombrePersona::messages('nombre_usuario'), CorreoValido::messages('email'), [
            'email.unique' => 'Ese correo ya está registrado.',
            'id_rol.required' => 'Selecciona un rol para la persona.',
            'id_rol.exists' => 'El rol seleccionado no es válido.',
            'password.confirmed' => 'Las contraseñas no coinciden. Vuelve a escribirlas para confirmarlas.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres, mayúsculas, minúsculas y un número.',
            'password.mixed' => 'La contraseña debe incluir mayúsculas y minúsculas.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
            'password.required' => 'La contraseña es obligatoria.',
        ]));
    }
}
