<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    /** Indica que esta clase hereda de la clase User base de Laravel **/
    use HasFactory, Notifiable;

    protected $table = 'USUARIO';

    protected $primaryKey = 'id_usuario';

    /** Estas le indican al modelo que dichas columnas son timestamps y las debe gestionar **/
    public const CREATED_AT = 'fecha_creacion';

    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'contrasenia',
        'nombre_usuario',
        'id_rol',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'contrasenia'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contrasenia' => 'hashed'
        ];
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'id_usuario');
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol');
    }

    public function getAuthPasswordName(): string
    {
        return 'contrasenia';
    }

    protected function nombre(): Attribute
    {
        return Attribute::get(fn (): string => (string) $this->nombre_usuario);
    }

    public function nombreRol(): string
    {
        return strtolower((string) ($this->rol?->rol ?? ''));
    }

    public function esAdmin(): bool
    {
        return $this->nombreRol() === 'admin';
    }

    public function esPeluquero(): bool
    {
        return $this->nombreRol() === 'peluquero';
    }

    public function esCliente(): bool
    {
        return in_array($this->nombreRol(), ['cliente', 'cliadmin'], true);
    }

    public function esStaff(): bool
    {
        return in_array($this->nombreRol(), ['admin', 'peluquero'], true);
    }

    public function esAdminImagen(): bool
    {
        return strcasecmp((string) $this->email, 'admin.imagen@haircut.cl') === 0;
    }

    public function puedeUsarSimulador(): bool
    {
        return $this->esCliente() || $this->esAdminImagen();
    }

    public function rutaInicio(): string
    {
        return match (true) {
            $this->esAdmin(), $this->esPeluquero() => route('staff.welcome'),
            default => route('home'),
        };
    }

    /** Destino del panel operativo (después de la bienvenida). */
    public function rutaPanel(): string
    {
        return match (true) {
            $this->esAdmin() => route('admin.servicios.index'),
            $this->esPeluquero() => route('peluquero.dashboard'),
            default => route('home'),
        };
    }

    /**
     * ¿Puede entrar a rutas protegidas con role:X?
     *
     * @param  string  ...$roles
     */
    public function puedeAccederComo(string ...$roles): bool
    {
        $roles = array_map('strtolower', $roles);
        $mio = $this->nombreRol();

        if (in_array($mio, $roles, true)) {
            return true;
        }

        if (in_array('cliente', $roles, true) && ($mio === 'cliadmin' || $this->esAdminImagen())) {
            return true;
        }

        return false;
    }

    /**
     * Prefijos de ruta válidos para “volver atrás” según el rol.
     *
     * @return list<string>
     */
    public function prefijosRutasPermitidas(): array
    {
        if ($this->esAdmin()) {
            $prefijos = [
                '/admin/servicios',
                '/admin/catalogo',
                '/admin/usuarios',
                '/mi-perfil',
                '/bienvenida',
            ];
            if ($this->esAdminImagen()) {
                array_push($prefijos, '/simulador', '/reservar', '/mis-reservas');
            }

            return $prefijos;
        }

        if ($this->esPeluquero()) {
            return [
                '/admin/dashboard',
                '/admin/disponibilidad',
                '/admin/solicitudes',
                '/admin/horas',
                '/admin/reportes',
                '/mi-perfil',
                '/bienvenida',
            ];
        }

        return [
            '/',
            '/productos',
            '/reservar',
            '/mis-reservas',
            '/simulador',
            '/mi-perfil',
            '/contrasena',
        ];
    }

    /**
     * El esquema actual no tiene la columna ai_sin_limite.
     * Solo Admin Test Imagen tiene cupo ilimitado (sin tocar la BD).
     */
    protected function aiSinLimite(): Attribute
    {
        return Attribute::get(fn (): bool => $this->esAdminImagen());
    }
}

