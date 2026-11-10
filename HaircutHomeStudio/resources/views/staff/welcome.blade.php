@extends('layouts.app', ['title' => 'Bienvenido - HairCut Home Studio', 'page' => 'bienvenida'])

@section('content')
<style>
.staff-welcome {
  text-align: center;
  padding: clamp(28px, 6vw, 56px) clamp(18px, 4vw, 40px);
  max-width: 640px;
  margin: 0 auto 28px;
}
.staff-welcome-title {
  margin: 8px 0 14px;
  font-family: var(--font-title);
  font-size: clamp(1.8rem, 5vw, 2.6rem);
  color: var(--header);
  line-height: 1.15;
}
.staff-welcome-lead {
  margin: 0 auto 18px;
  max-width: 46ch;
  color: var(--ink-soft);
  line-height: 1.6;
}
.staff-welcome-list {
  list-style: none;
  margin: 0 auto 24px;
  padding: 0;
  max-width: 36ch;
  text-align: left;
  color: var(--ink);
  line-height: 1.55;
}
.staff-welcome-list li {
  position: relative;
  padding: 8px 0 8px 22px;
  border-bottom: 1px solid var(--line);
  font-size: 0.95rem;
}
.staff-welcome-list li:last-child { border-bottom: 0; }
.staff-welcome-list li::before {
  content: "·";
  position: absolute;
  left: 6px;
  color: var(--header);
  font-weight: 700;
}
.staff-welcome-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  justify-content: center;
}
</style>
<section class="card staff-welcome">
    <p class="home-kicker">HairCut Home Studio</p>
    <h1 class="staff-welcome-title">Bienvenido {{ $nombre }}</h1>
    @if($rol === 'admin')
        <p class="staff-welcome-lead">Desde aquí puedes administrar el estudio:</p>
        <ul class="staff-welcome-list">
            <li>Crear y editar servicios y categorías</li>
            <li>Gestionar el catálogo de productos</li>
            <li>Crear personas y asignar roles</li>
        </ul>
    @elseif($rol === 'peluquero')
        <p class="staff-welcome-lead">Desde aquí puedes manejar tu agenda:</p>
        <ul class="staff-welcome-list">
            <li>Ver estadísticas del mes</li>
            <li>Definir disponibilidad y días libres</li>
            <li>Aprobar o rechazar solicitudes</li>
            <li>Revisar horas y exportar reportes</li>
        </ul>
    @else
        <p class="staff-welcome-lead">Usa el menú para continuar.</p>
    @endif
    <div class="staff-welcome-actions">
        @if($rol === 'admin')
            <a href="{{ route('admin.servicios.index') }}" class="btn btn-primary">Ir a servicios</a>
            <a href="{{ route('admin.catalogo.index') }}" class="btn btn-secondary">Ver catálogo</a>
            @if(auth()->user()?->esAdminImagen())
                <a href="{{ route('ai.index') }}" class="btn btn-secondary">Simulador</a>
            @endif
        @elseif($rol === 'peluquero')
            <a href="{{ route('peluquero.dashboard') }}" class="btn btn-primary">Ir al panel</a>
            <a href="{{ route('peluquero.solicitudes.index') }}" class="btn btn-secondary">Ver solicitudes</a>
        @else
            <a href="{{ route('home') }}" class="btn btn-primary">Volver al inicio</a>
        @endif
    </div>
</section>
@endsection
