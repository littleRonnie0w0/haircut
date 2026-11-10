@extends('layouts.app', ['title' => 'Haircut Studio - Mi perfil', 'page' => 'perfil'])

@section('content')
<section class="auth-card profile-card">
    <div class="auth-head"><span class="auth-dot" aria-hidden="true"></span><h2>Mi perfil</h2></div>
    <p class="auth-sub">
        @if($usuario->esStaff())
            Actualiza tu nombre, correo o contraseña. El rol de la cuenta no se modifica aquí.
        @else
            Actualiza tu nombre, correo o contraseña.
        @endif
    </p>

    <form method="post" action="{{ route('perfil.update') }}" class="auth-form">
        @csrf
        @method('PUT')
        <div class="auth-field">
            <label class="profile-label" for="nombre_usuario">Nombre</label>
            <input id="nombre_usuario" type="text" name="nombre_usuario" required maxlength="150" value="{{ old('nombre_usuario', $usuario->nombre_usuario) }}" autocomplete="name">
        </div>
        <div class="auth-field">
            <label class="profile-label" for="email">Correo</label>
            <input id="email" type="email" name="email" required maxlength="100" value="{{ old('email', $usuario->email) }}" autocomplete="email">
        </div>
        <div class="auth-field">
            <label class="profile-label" for="password">Nueva contraseña</label>
            <input id="password" type="password" name="password" minlength="6" placeholder="Dejar vacío para no cambiar" autocomplete="new-password">
        </div>
        <div class="auth-field">
            <label class="profile-label" for="password_confirmation">Confirmar contraseña</label>
            <input id="password_confirmation" type="password" name="password_confirmation" minlength="6" placeholder="Repite la nueva contraseña" autocomplete="new-password">
        </div>
        @if($usuario->esStaff())
            <p class="hint">Rol actual: <strong>{{ ucfirst($usuario->nombreRol()) }}</strong></p>
        @endif
        <button type="submit" class="btn btn-primary auth-submit">Guardar cambios</button>
    </form>
</section>
@endsection
