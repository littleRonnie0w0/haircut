@extends('layouts.app', ['title' => 'Haircut Studio - Personas', 'section' => 'admin', 'page' => 'usuarios'])

@section('content')
<section class="card catalog-admin">
    <div class="catalog-toolbar">
        <h2>Personas</h2>
        <button type="button" class="btn btn-primary" data-panel-toggle="user-create" aria-expanded="false">Agregar persona +</button>
    </div>

    <div class="catalog-add-panel" id="user-create" hidden>
        <p class="hint">Crea cuentas con rol de cliente, peluquero o administrador. La contraseña se pide al entrar.</p>
        <form method="post" action="{{ route('admin.usuarios.store') }}" class="catalog-add-form">
            @csrf
            <div class="catalog-form-grid">
                <div class="field"><label for="user-nombre">Nombre</label><input type="text" id="user-nombre" name="nombre_usuario" value="{{ old('nombre_usuario') }}" required maxlength="150" placeholder="Ej: Ana Rojas"></div>
                <div class="field"><label for="user-email">Correo</label><input type="email" id="user-email" name="email" value="{{ old('email') }}" required maxlength="100" placeholder="correo@ejemplo.cl" autocomplete="off"></div>
                <div class="field">
                    <label for="user-rol">Rol</label>
                    <select id="user-rol" name="id_rol" required>
                        @foreach($roles as $rol)
                            <option value="{{ $rol->id_rol }}" @selected((int) old('id_rol', 3) === (int) $rol->id_rol)>{{ $rol->rol }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label for="user-pass">Contraseña</label><input type="password" id="user-pass" name="password" required minlength="6" autocomplete="new-password" placeholder="Mínimo 6 caracteres"></div>
                <div class="field"><label for="user-pass2">Confirmar contraseña</label><input type="password" id="user-pass2" name="password_confirmation" required minlength="6" autocomplete="new-password"></div>
            </div>
            <div class="actions"><button type="submit" class="btn btn-primary">Guardar persona</button><button type="button" class="btn btn-secondary" data-panel-close="user-create">Cancelar</button></div>
        </form>
    </div>

    <div class="catalog-search">
        <label class="visually-hidden" for="user-search">Buscar persona</label>
        <div class="catalog-search-wrap">
            <span class="catalog-search-ico" aria-hidden="true">⌕</span>
            <input type="search" id="user-search" data-filter-input="user-row" placeholder="Busca por nombre, correo o rol" autocomplete="off">
        </div>
    </div>

    <div class="cat-filters" role="tablist" aria-label="Filtrar por rol">
        <a class="book-chip {{ $rolId === null ? 'is-on' : '' }}" href="{{ route('admin.usuarios.index') }}">Todas</a>
        @foreach($roles as $rol)
            <a class="book-chip {{ $rolId === (int) $rol->id_rol ? 'is-on' : '' }}" href="{{ route('admin.usuarios.index', ['rol' => $rol->id_rol]) }}">{{ $rol->rol }}</a>
        @endforeach
    </div>

    @if($usuarios->isEmpty())
        <p class="hint">No hay personas con ese filtro. Usa “Agregar persona +” para crear la primera.</p>
    @else
        <div class="catalog-table usuarios-table" role="table" aria-label="Personas del sistema">
            <div class="catalog-table-head" role="row"><span>Persona</span><span>Rol</span><span>Correo</span><span>Alta</span><span>Opciones</span></div>
            @foreach($usuarios as $persona)
                @php
                    $inicial = mb_strtoupper(mb_substr((string) $persona->nombre_usuario, 0, 1));
                    $rolNombre = $persona->rol?->rol ?? 'Sin rol';
                    $fechaAlta = $persona->fecha_creacion
                        ? \Carbon\CarbonImmutable::parse($persona->fecha_creacion)->format('d/m/Y')
                        : '—';
                    $esYo = auth()->id() === $persona->id_usuario;
                @endphp
                <article class="catalog-row" role="row" data-filter-row="user-row" data-filter-text="{{ Str::lower($persona->nombre_usuario.' '.$persona->email.' '.$rolNombre) }}">
                    <div class="catalog-row-main">
                        <div class="catalog-row-product">
                            <div class="catalog-thumb" aria-hidden="true">{{ $inicial }}</div>
                            <div>
                                <strong class="catalog-row-name">{{ $persona->nombre_usuario }}</strong>
                                @if($esYo)<p class="catalog-row-desc">Tu cuenta</p>@endif
                            </div>
                        </div>
                        <div class="catalog-row-status" data-label="Rol"><span class="catalog-badge is-on">{{ $rolNombre }}</span></div>
                        <div class="catalog-row-price" data-label="Correo">{{ $persona->email }}</div>
                        <div class="catalog-row-orden" data-label="Alta">{{ $fechaAlta }}</div>
                        <div class="catalog-row-actions">
                            <button type="button" class="catalog-icon-btn is-edit" data-panel-toggle="user-edit-{{ $persona->id_usuario }}" aria-expanded="false" title="Editar">✎ <span>Editar</span></button>
                            @unless($esYo)
                                <form method="post" action="{{ route('admin.usuarios.destroy', $persona) }}" data-confirm="¿Seguro que quiere eliminar a {{ $persona->nombre_usuario }}?" data-confirm-title="Eliminar">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="catalog-icon-btn is-delete" title="Eliminar">× <span>Eliminar</span></button>
                                </form>
                            @endunless
                        </div>
                    </div>
                    <div class="catalog-row-edit" id="user-edit-{{ $persona->id_usuario }}" hidden>
                        <form method="post" action="{{ route('admin.usuarios.update', $persona) }}" class="catalog-add-form">
                            @csrf @method('PUT')
                            <div class="catalog-form-grid">
                                <div class="field"><label>Nombre</label><input type="text" name="nombre_usuario" value="{{ old('nombre_usuario', $persona->nombre_usuario) }}" required maxlength="150"></div>
                                <div class="field"><label>Correo</label><input type="email" name="email" value="{{ old('email', $persona->email) }}" required maxlength="100"></div>
                                <div class="field">
                                    <label>Rol</label>
                                    <select name="id_rol" required>
                                        @foreach($roles as $rol)
                                            <option value="{{ $rol->id_rol }}" @selected((int) old('id_rol', $persona->id_rol) === (int) $rol->id_rol)>{{ $rol->rol }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="field"><label>Nueva contraseña</label><input type="password" name="password" minlength="6" autocomplete="new-password" placeholder="Déjala vacía para no cambiarla"></div>
                                <div class="field"><label>Confirmar contraseña</label><input type="password" name="password_confirmation" minlength="6" autocomplete="new-password"></div>
                            </div>
                            <div class="actions"><button type="submit" class="btn btn-primary">Guardar cambios</button><button type="button" class="btn btn-secondary" data-panel-close="user-edit-{{ $persona->id_usuario }}">Cancelar</button></div>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>
@endsection
