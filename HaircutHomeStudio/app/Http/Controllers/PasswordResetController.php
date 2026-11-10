<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\MessagingService;
use App\Services\PasswordResetStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function __construct(
        private readonly PasswordResetStore $tokens,
        private readonly MessagingService $messaging,
    ) {}

    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'email', 'max:150'],
        ], [
            'email.required' => 'Ingresa tu correo.',
            'email.email' => 'El correo no es válido.',
        ]);

        $email = strtolower(trim($datos['email']));
        $usuario = User::query()->where('email', $email)->first();

        // Misma respuesta si el correo no existe (no filtrar cuentas).
        if ($usuario) {
            $token = Str::random(64);
            $this->tokens->put($email, $token, 60);
            $url = route('password.reset', ['token' => $token, 'email' => $email]);

            $ok = $this->messaging->sendBrand(
                $email,
                'Restablecer contraseña — HairCut Home Studio',
                "Hola {$usuario->nombre_usuario},",
                [
                    'Recibimos una solicitud para restablecer tu contraseña.',
                    'El enlace es válido por 60 minutos. Si no fuiste tú, puedes ignorar este correo.',
                ],
                $url,
                'Restablecer contraseña',
                'Para más información visita nuestra página <a href="'.e(config('messaging.site_url')).'" style="color:#5C293C;">HairCut Home Studio</a>.',
                ['tipo' => 'password_reset', 'usuario_id' => $usuario->id_usuario],
            );

            if (! $ok) {
                return back()->with(
                    'error',
                    'No se pudo enviar el correo ahora. Revisa la configuración de Resend o inténtalo más tarde.',
                );
            }
        }

        return back()->with(
            'success',
            'Si el correo está registrado, te enviamos un enlace para restablecer la contraseña.',
        );
    }

    public function resetForm(Request $request, string $token): View|RedirectResponse
    {
        $email = strtolower(trim((string) $request->query('email', '')));
        if ($email === '' || $token === '') {
            return redirect()->route('password.request')
                ->with('error', 'El enlace de restablecimiento no es válido.');
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        $email = strtolower(trim($datos['email']));

        if (! $this->tokens->consume($email, $datos['token'])) {
            return redirect()->route('password.request')
                ->with('error', 'El enlace expiró o no es válido. Solicita uno nuevo.');
        }

        $usuario = User::query()->where('email', $email)->first();
        if (! $usuario) {
            return redirect()->route('password.request')
                ->with('error', 'No encontramos una cuenta con ese correo.');
        }

        $usuario->contrasenia = $datos['password'];
        $usuario->save();

        return redirect()->route('login')
            ->with('success', 'Contraseña actualizada. Ya puedes iniciar sesión.');
    }
}
