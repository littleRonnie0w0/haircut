@extends('layouts.app', ['title' => 'Haircut Home Studio - Entrar', 'page' => 'login'])

@section('content')
<section class="auth-card">
    <div class="auth-head"><span class="auth-dot" aria-hidden="true"></span><h2>{{ $registro ? 'Crear cuenta' : 'Entrar' }}</h2></div>
    <p class="auth-sub">{{ $registro ? 'Regístrate y reserva con atención personalizada en Melipilla.' : 'Ingresa con tu usuario o correo para ver tus reservas y agendar.' }}</p>

    @if($registro)
        <form method="post" action="{{ route('register.store') }}" class="auth-form">
            @csrf
            <div class="auth-field"><input type="text" name="nombre" value="{{ old('nombre', $nombreSugerido) }}" placeholder="Nombre completo" required maxlength="120" autocomplete="name"></div>
            <div class="auth-field"><input type="email" name="email" required value="{{ old('email') }}" placeholder="Correo electrónico" autocomplete="email"></div>
            <div class="auth-field"><input type="password" name="password" required minlength="8" placeholder="Contraseña" autocomplete="new-password"></div>
            <div class="auth-field"><input type="password" name="password_confirmation" required minlength="8" placeholder="Confirmar contraseña" autocomplete="new-password"></div>
            <button type="submit" class="btn btn-primary auth-submit">Crear cuenta</button>
            <p class="auth-foot auth-foot-single">¿Ya tienes cuenta? <a href="{{ route('login') }}">Entrar</a></p>
        </form>
    @else
        <form method="post" action="{{ route('login.store') }}" class="auth-form">
            @csrf
            <div class="auth-field"><input type="text" name="email" required value="{{ old('email') }}" placeholder="Usuario o correo" autocomplete="username"></div>
            <div class="auth-field"><input type="password" name="password" required placeholder="Contraseña" autocomplete="current-password"></div>
            <button type="submit" class="btn btn-primary auth-submit">Entrar</button>

            <div class="auth-links">
                <a href="{{ route('password.request') }}">Restablecer contraseña</a>
                <p class="auth-signup">¿No tienes cuenta? <a href="{{ route('login', ['modo' => 'registro']) }}">Crear cuenta</a></p>
            </div>
        </form>
    @endif
</section>
@endsection
