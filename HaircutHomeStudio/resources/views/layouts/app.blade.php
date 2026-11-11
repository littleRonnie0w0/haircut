@php
    $title = $title ?? 'Haircut Home Studio';
    $page = $page ?? '';
    $section = $section ?? 'public';
    $isHome = ($layout ?? 'app') === 'home';
    $classes = ['has-home-header', $isHome ? 'home-page' : 'app-page'];
    if ($page === 'login') $classes[] = 'login-page';
    if ($page === 'nueva') $classes[] = 'book-page';
    if ($page === 'ai') $classes[] = 'ai-page';
    if ($page === 'disponibilidad') $classes[] = 'avail-page';
    if ($section === 'admin') $classes[] = 'admin-page';
    if ($isHome) $classes[] = 'user-motion';
    $usuario = auth()->user();
    $rolNombre = strtolower((string) ($usuario?->rol?->rol ?? ''));
    $homeRoute = $usuario ? $usuario->rutaInicio() : route('home');
    $backRoute = $homeRoute;
    $backAllowed = $usuario ? $usuario->prefijosRutasPermitidas() : ['/', '/productos', '/login', '/contrasena'];
    $horaInicio = substr((string) $layoutConfig->hora_inicio, 0, 5);
    $horaFin = substr((string) $layoutConfig->hora_fin, 0, 5);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap">
    <script>document.documentElement.classList.add('js-reveal');</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="{{ implode(' ', $classes) }}">
<div id="app-loader" class="app-loader" hidden>
    <div class="app-loader-spin" aria-hidden="true">
        @for ($i = 0; $i < 12; $i++)<span></span>@endfor
    </div>
    <p>Cargando...</p>
</div>

<header class="home-top">
    <div class="container container-fluid">
        <div class="home-top-left">
            <a href="{{ $homeRoute }}" class="home-brand-link" aria-label="HairCut Home Studio — Inicio">
                <span class="home-brand-mark-wrap"><img class="home-brand-logo" src="{{ asset('img/logo.png') }}" alt=""></span>
                <span class="home-brand-name">HairCut Home Studio</span>
            </a>
        </div>
        <div class="header-actions">
            @guest
                <a href="{{ route('login') }}" class="btn-nav btn-nav-outline">Iniciar sesión</a>
                <a href="{{ route('login', ['modo' => 'registro']) }}" class="btn-nav btn-nav-fill">Registrarse</a>
            @else
                <a href="{{ route('perfil.edit') }}" class="nav-user nav-pill" title="Editar mi información">{{ $usuario->nombre_usuario }}</a>
            @endguest
            <a href="{{ $backRoute }}" class="header-back" id="header-back" aria-label="Volver atrás" title="Volver atrás" data-role-home="{{ $backRoute }}" data-allowed-prefixes="{{ e(json_encode($backAllowed)) }}">
                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M14.5 5.5 8 12l6.5 6.5 1.4-1.4L10.8 12l5.1-5.1z"/></svg>
            </a>
            <button type="button" class="nav-toggle" aria-expanded="false" aria-controls="nav-acc" aria-label="Abrir menú">
                <span></span><span></span><span></span>
            </button>
        </div>
        <nav class="home-top-right side-nav" id="nav-acc">
            <div class="side-nav-head">
                <a href="{{ $homeRoute }}" class="side-nav-brand"><img src="{{ asset('img/logo.png') }}" alt=""><span>HairCut Home Studio</span></a>
                <button type="button" class="side-nav-close" aria-label="Cerrar menú">&times;</button>
            </div>
            <p class="side-nav-title">Menú</p>
            @guest
                <div class="side-nav-auth">
                    <a href="{{ route('login') }}" class="btn-nav btn-nav-outline">Iniciar sesión</a>
                    <a href="{{ route('login', ['modo' => 'registro']) }}" class="btn-nav btn-nav-fill">Registrarse</a>
                </div>
            @else
                <div class="side-nav-auth">
                    <a href="{{ route('perfil.edit') }}" class="btn-nav btn-nav-outline {{ $page === 'perfil' ? 'is-current' : '' }}" title="Editar mi información">{{ $usuario->nombre_usuario }}</a>
                    <form method="post" action="{{ route('logout') }}" class="side-nav-logout-form">
                        @csrf
                        <button type="submit" class="btn-nav btn-nav-fill">Cerrar sesión</button>
                    </form>
                </div>
            @endguest

            @if($rolNombre === 'peluquero')
                <a href="{{ route('peluquero.dashboard') }}" class="nav-link {{ $page === 'panel' ? 'is-current' : '' }}"><span class="nav-ico" aria-hidden="true">▣</span> Estadísticas</a>
                <a href="{{ route('peluquero.disponibilidad.edit') }}" class="nav-link {{ $page === 'disponibilidad' ? 'is-current' : '' }}"><span class="nav-ico" aria-hidden="true">▦</span> Disponibilidad</a>
                <a href="{{ route('peluquero.solicitudes.index') }}" class="nav-link {{ $page === 'solicitudes' ? 'is-current' : '' }}"><span class="nav-ico" aria-hidden="true">✉</span> Solicitudes</a>
                <a href="{{ route('peluquero.horas.index') }}" class="nav-link {{ $page === 'horas' ? 'is-current' : '' }}"><span class="nav-ico" aria-hidden="true">◷</span> Horas</a>
            @elseif($rolNombre === 'admin')
                <a href="{{ route('admin.servicios.index') }}" class="nav-link {{ $page === 'servicios' ? 'is-current' : '' }}"><span class="nav-ico" aria-hidden="true">✂</span> Servicios</a>
                <a href="{{ route('admin.catalogo.index') }}" class="nav-link {{ $page === 'catalogo' ? 'is-current' : '' }}"><span class="nav-ico" aria-hidden="true">▣</span> Catálogo</a>
                <a href="{{ route('admin.usuarios.index') }}" class="nav-link {{ $page === 'usuarios' ? 'is-current' : '' }}"><span class="nav-ico" aria-hidden="true">☺</span> Personas</a>
                @if($usuario->esAdminImagen())
                    <a href="{{ route('ai.index') }}" class="nav-link {{ $page === 'ai' ? 'is-current' : '' }}"><span class="nav-ico" aria-hidden="true">✦</span> Simulador</a>
                    <a href="{{ route('reservas.create') }}" class="nav-link {{ $page === 'nueva' ? 'is-current' : '' }}"><span class="nav-ico" aria-hidden="true">✎</span> Reservar</a>
                    <a href="{{ route('reservas.index') }}" class="nav-link {{ $page === 'mis' ? 'is-current' : '' }}"><span class="nav-ico" aria-hidden="true">◷</span> Mis reservas</a>
                @endif
            @else
                <a href="{{ route('home') }}" class="nav-link {{ $page === 'inicio' ? 'is-current' : '' }}"><span class="nav-ico" aria-hidden="true">⌂</span> Inicio</a>
                <a href="{{ route('productos.index') }}" class="nav-link {{ $page === 'productos' ? 'is-current' : '' }}"><span class="nav-ico" aria-hidden="true">◈</span> Productos</a>
                @auth
                    <a href="{{ route('ai.index') }}" class="nav-link {{ $page === 'ai' ? 'is-current' : '' }}"><span class="nav-ico" aria-hidden="true">✦</span> Simulador</a>
                @endauth
                <a href="{{ route('reservas.create') }}" class="nav-link {{ $page === 'nueva' ? 'is-current' : '' }}"><span class="nav-ico" aria-hidden="true">✎</span> Reservar</a>
                @auth
                    <a href="{{ route('reservas.index') }}" class="nav-link {{ $page === 'mis' ? 'is-current' : '' }}"><span class="nav-ico" aria-hidden="true">◷</span> Mis reservas</a>
                @endauth
            @endif
        </nav>
        <div class="side-nav-overlay" hidden></div>
    </div>
</header>

<main>
    <div class="container container-fluid">
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif
        @yield('content')
    </div>
</main>

<footer class="site">
    <div class="container container-fluid">
        <div class="footer-top">
            <div class="footer-map-wrap">
                <iframe class="footer-map" title="Mapa de HairCut Home Studio en Melipilla"
                    data-src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d500!2d-71.1894721!3d-33.6828171!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9662ff607ff20d81%3A0xafcf5d95bed90e7c!2sHairCut%20Home%20Studio!5e0!3m2!1ses!2scl!4v1"
                    src="about:blank" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
            </div>
            <div class="footer-info">
                <div class="footer-hours">
                    <h3>Cuándo encontrarnos</h3>
                    <ul>
                        <li><span>Martes a viernes</span><span class="footer-time">{{ $horaInicio }} – {{ $horaFin }}</span></li>
                        <li><span>Sábado</span><span class="footer-time">{{ $horaInicio }} – 14:30</span></li>
                        <li><span>Domingo y lunes</span><span class="footer-time">Cerrado</span></li>
                    </ul>
                </div>
                <div class="footer-contact">
                    <div>
                        <h3>Escríbenos</h3>
                        <div class="footer-write">
                            <a href="https://wa.me/56954182516" target="_blank" rel="noopener">WhatsApp</a>
                            <a href="https://www.instagram.com/haircut.homestudio/" target="_blank" rel="noopener">Instagram</a>
                            <a href="https://www.facebook.com/haircuthomestudio/" target="_blank" rel="noopener">Facebook</a>
                        </div>
                    </div>
                    <div class="footer-place">
                        <h3>Ubicación</h3>
                        <p>Prof. Ricardo Luengo Mardones</p>
                        <a class="footer-pin" href="https://www.google.com/maps/place/HairCut+Home+Studio/@-33.6828171,-71.1894721,17z" target="_blank" rel="noopener">Melipilla, Región Metropolitana</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom"><div class="container container-fluid"><p class="footer-copy">&copy; 2026 Haircut Home Studio</p></div></div>
</footer>
@if(session('success'))
<style>
.notice-dialog { overflow: hidden; padding-top: 0; text-align: center; }
.notice-dialog-bar { height: 14px; margin: 0 -22px 16px; background: var(--header); }
.notice-dialog-mark { display: flex; justify-content: center; margin: 2px 0 8px; }
.notice-dialog-mark svg { display: block; }
.notice-dialog .confirm-modal-title,
.notice-dialog .confirm-modal-message { text-align: center; }
.notice-dialog .confirm-modal-actions { justify-content: center; }
.notice-dialog .confirm-modal-actions .btn { min-width: 132px; }
</style>
<div id="notice-modal" class="confirm-modal">
    <div class="confirm-modal-backdrop" data-notice-dismiss></div>
    <div class="confirm-modal-dialog notice-dialog" role="dialog" aria-modal="true" aria-labelledby="notice-modal-title">
        <div class="notice-dialog-bar" aria-hidden="true"></div>
        <div class="notice-dialog-mark" aria-hidden="true">
            <svg viewBox="0 0 64 64" width="68" height="68">
                <circle cx="32" cy="32" r="27" fill="#EBE0E4" stroke="#5C293C" stroke-width="2.5"/>
                <path d="M20 33.5 28.2 42 45 23.5" fill="none" stroke="#5C293C" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h2 id="notice-modal-title" class="confirm-modal-title">Listo</h2>
        <p class="confirm-modal-message">{{ session('success') }}</p>
        <div class="confirm-modal-actions">
            <button type="button" class="btn btn-primary" data-notice-dismiss>Aceptar</button>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('notice-modal');
    if (!modal || modal.dataset.bound === '1') return;
    modal.dataset.bound = '1';
    document.body.classList.add('confirm-open');
    function close() {
        modal.hidden = true;
        document.body.classList.remove('confirm-open');
    }
    modal.querySelectorAll('[data-notice-dismiss]').forEach(function (el) {
        el.addEventListener('click', close);
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) close();
    });
    var button = modal.querySelector('[data-notice-dismiss].btn');
    if (button) button.focus();
});
</script>
@endif
<div id="confirm-modal" class="confirm-modal" hidden>
    <div class="confirm-modal-backdrop" data-confirm-dismiss></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title">
        <h2 id="confirm-modal-title" class="confirm-modal-title">Confirmar</h2>
        <p id="confirm-modal-message" class="confirm-modal-message"></p>
        <div class="confirm-modal-actions">
            <button type="button" class="btn btn-secondary" data-confirm-dismiss>Volver</button>
            <button type="button" class="btn btn-primary" id="confirm-modal-ok">Confirmar</button>
        </div>
    </div>
</div>
<div class="gallery-modal" id="gallery-modal" hidden data-no-loader>
    <div class="gallery-modal-backdrop" data-gallery-close></div>
    <div class="gallery-modal-dialog" role="dialog" aria-modal="true" aria-label="Vista ampliada de la imagen">
        <button type="button" class="gallery-modal-close" data-gallery-close aria-label="Cerrar">×</button>
        <button type="button" class="gallery-modal-nav is-prev" data-gallery-prev aria-label="Imagen anterior">‹</button>
        <figure class="gallery-modal-figure" data-gallery-swipe>
            <div class="gallery-modal-stage">
                <img src="" alt="" id="gallery-modal-image">
            </div>
            <figcaption id="gallery-modal-caption"></figcaption>
        </figure>
        <button type="button" class="gallery-modal-nav is-next" data-gallery-next aria-label="Imagen siguiente">›</button>
    </div>
</div>
@stack('scripts')
</body>
</html>
