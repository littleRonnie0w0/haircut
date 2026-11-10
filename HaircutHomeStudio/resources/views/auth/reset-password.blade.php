@extends('layouts.app', ['title' => 'Nueva contraseña - Haircut Home Studio', 'page' => 'login'])

@section('content')
<section class="auth-card">
    <div class="auth-head"><span class="auth-dot" aria-hidden="true"></span><h2>Nueva contraseña</h2></div>
    <p class="auth-sub">Elige una contraseña nueva para <strong>{{ $email }}</strong>.</p>

    <form method="post" action="{{ route('password.update') }}" class="auth-form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">
        <div class="auth-field">
            <input type="password" name="password" required minlength="6" placeholder="Nueva contraseña" autocomplete="new-password">
        </div>
        <div class="auth-field">
            <input type="password" name="password_confirmation" required minlength="6" placeholder="Confirmar contraseña" autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary auth-submit">Guardar contraseña</button>
        <p class="auth-foot"><a href="{{ route('login') }}">Volver a iniciar sesión</a></p>
    </form>
</section>
@endsection
