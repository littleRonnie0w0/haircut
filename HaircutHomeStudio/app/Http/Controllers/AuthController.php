<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\MessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function __construct(private readonly MessagingService $messaging) {}

    public function show(Request $request): View
    {
        return view('auth.login', [
            'registro' => $request->string('modo')->toString() === 'registro',
            'nombreSugerido' => 'Clienta',
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        // se almacena los datos del login
        $credenciales = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Resuelve usuario/correo (ej. "rosa" → email real de la cuenta).
        $credenciales['email'] = $this->resolverEmailLogin($credenciales['email']);

        // Se realiza la autenticación del usuario.
        if (! Auth::attempt($credenciales)) {
            return back()
                ->withErrors(['email' => 'Usuario o contraseña incorrectos.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $usuario = Auth::user();
        $destino = $this->inicioPara($usuario);

        // Staff siempre a su bienvenida (evita “intended” a rutas de otro rol).
        if ($usuario->esStaff()) {
            $request->session()->forget('url.intended');

            return redirect()->to($destino)->with('success', 'Sesión iniciada.');
        }

        return redirect()->intended($destino)->with('success', 'Sesión iniciada.');
    }

    public function register(Request $request): RedirectResponse
    {
        // guardamos en un array los datos del registro.
        $datos = $request->validate([
            'nombre' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('USUARIO', 'email')],
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'email.unique' => 'Ese correo ya está registrado. Entra con tu contraseña.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        // Creamos el usuario en la bbdd.

        $usuario = User::create([
            'nombre_usuario' => trim($datos['nombre'] ?? '') ?: 'Clienta',
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

        // dejamos su sesión iniciada.
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

        return redirect()->route('home');
    }

    /**
     * Acepta correo completo o nombre de usuario.
     * Atajos de desarrollo: admin, ricardo.
     */
    private function resolverEmailLogin(string $valor): string
    {
        $valor = trim($valor);
        $lower = strtolower($valor);

        if ($lower === 'admin') {
            return 'admin@admin.cl';
        }
        if ($lower === 'ricardo') {
            return 'ricardo@me.com';
        }

        if (str_contains($valor, '@')) {
            return $lower;
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

        return $candidato;
    }

    private function inicioPara(User $usuario): string
    {
        return $usuario->rutaInicio();
    }
}
