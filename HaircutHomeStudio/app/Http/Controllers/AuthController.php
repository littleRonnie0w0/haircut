<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\MessagingService;
use App\Support\CorreoValido;
use App\Support\NombrePersona;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private readonly MessagingService $messaging) {}

    public function show(Request $request): View
    {
        return view('auth.login', [
            'registro' => $request->string('modo')->toString() === 'registro',
            'nombreSugerido' => old('nombre', ''),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'email' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Ingresa tu usuario o correo electrónico.',
            'password.required' => 'Ingresa tu contraseña.',
        ]);

        $email = $this->resolverEmailLogin($credenciales['email']);
        if ($email === null) {
            return back()
                ->withErrors(['email' => 'No encontramos una cuenta con ese usuario o correo.'])
                ->onlyInput('email');
        }

        if (! Auth::attempt(['email' => $email, 'password' => $credenciales['password']])) {
            return back()
                ->withErrors(['email' => 'La contraseña no es correcta. Inténtalo de nuevo.'])
                ->onlyInput('email');
        }

        $usuario = Auth::user();
        $request->session()->regenerate();

        if (! $usuario?->estaActiva()) {
            $request->session()->forget('url.intended');

            return redirect()
                ->route('home')
                ->with('success', 'Sesión iniciada. Tu cuenta está desactivada: solo puedes ver Inicio y Productos.');
        }

        $destino = $this->inicioPara($usuario);

        if ($usuario->esStaff()) {
            $request->session()->forget('url.intended');

            return redirect()->to($destino)->with('success', 'Sesión iniciada.');
        }

        return redirect()->intended($destino)->with('success', 'Sesión iniciada.');
    }

    public function register(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => NombrePersona::rules(required: true),
            'email' => array_merge(CorreoValido::rules(required: true), [Rule::unique('USUARIO', 'email')]),
            'password' => ['required', 'confirmed', Password::defaults()],
        ], array_merge(NombrePersona::messages('nombre'), CorreoValido::messages('email'), [
            'email.unique' => 'Ese correo ya está registrado. Inicia sesión con tu contraseña.',
            'password.required' => 'Ingresa una contraseña.',
            'password.confirmed' => 'Las contraseñas no coinciden. Vuelve a escribirlas para confirmarlas.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres, mayúsculas, minúsculas y un número.',
            'password.mixed' => 'La contraseña debe incluir mayúsculas y minúsculas.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
            'password.letters' => 'La contraseña debe incluir letras.',
        ]));

        $usuario = User::create([
            'nombre_usuario' => trim($datos['nombre']),
            'email' => strtolower($datos['email']),
            'contrasenia' => $datos['password'],
            'id_rol' => 3,
        ]);

        $loginUrl = route('login');
        $this->messaging->sendBrand(
            $usuario->email,
            'Bienvenida a HairCut Home Studio',
            "Hola {$usuario->nombre_usuario},",
            [
                'Gracias por crear tu cuenta en HairCut Home Studio.',
                'Tu cuenta ya está disponible. Puedes ingresar con el correo y la contraseña que registraste.',
            ],
            $loginUrl,
            'Iniciar sesión',
            'Para más información visita nuestra página <a href="'.e(config('messaging.site_url')).'" style="color:#5C293C;">HairCut Home Studio</a>.',
            ['tipo' => 'welcome', 'usuario_id' => $usuario->id_usuario],
        );

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->intended(route('home'))
            ->with('success', 'Listo. Ya puedes reservar.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => 'Sat, 01 Jan 2000 00:00:00 GMT',
            ]);
    }

    /**
     * Acepta correo completo o nombre de usuario.
     * Atajos de desarrollo: admin, ricardo.
     */
    private function resolverEmailLogin(string $valor): ?string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }

        $lower = mb_strtolower($valor);

        if ($lower === 'admin') {
            return 'admin@admin.cl';
        }
        if ($lower === 'ricardo') {
            return 'ricardo@me.com';
        }

        if (str_contains($valor, '@')) {
            $email = $lower;
            if (! User::query()->where('email', $email)->exists()) {
                return null;
            }

            return $email;
        }

        $porNombre = User::query()
            ->whereRaw('LOWER(nombre_usuario) = ?', [$lower])
            ->value('email');

        if (is_string($porNombre) && $porNombre !== '') {
            return strtolower($porNombre);
        }

        $candidato = $lower.'@haircut.cl';
        if (User::query()->where('email', $candidato)->exists()) {
            return $candidato;
        }

        return null;
    }

    private function inicioPara(User $usuario): string
    {
        return $usuario->rutaInicio();
    }
}
