@extends('layouts.app', ['title' => 'Restablecer contraseña - Haircut Home Studio', 'page' => 'login'])

@section('content')
<section class="auth-card">
    <div class="auth-head"><span class="auth-dot" aria-hidden="true"></span><h2>Restablecer contraseña</h2></div>
    <p class="auth-sub">Ingresa el correo de tu cuenta. Te enviaremos un enlace para crear una nueva contraseña.</p>

    <form method="post" action="{{ route('password.email') }}" class="auth-form">
        @csrf
        <div class="auth-field">
            <input type="email" name="email" required value="{{ old('email') }}" placeholder="Correo electrónico" autocomplete="email">
        </div>
        <button type="submit" class="btn btn-primary auth-submit">Enviar enlace</button>
        <p class="auth-foot"><a href="{{ route('login') }}">Volver a iniciar sesión</a></p>
    </form>
</section>
@endsection
