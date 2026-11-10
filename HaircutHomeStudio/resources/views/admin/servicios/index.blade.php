@extends('layouts.app', ['title' => 'Haircut Studio - Servicios', 'section' => 'admin', 'page' => 'servicios'])

@php($media = app(\App\Services\MediaService::class))

@section('content')
<section class="card catalog-admin">
    <div class="catalog-toolbar">
        <h2>Servicios</h2>
        <div class="catalog-toolbar-actions">
            <button type="button" class="btn btn-secondary" data-panel-toggle="service-categories" aria-expanded="false">Categorías</button>
            <button type="button" class="btn btn-primary" data-panel-toggle="service-create" aria-expanded="false">Agregar servicio +</button>
        </div>
    </div>

    <div class="catalog-add-panel" id="service-categories" hidden>
        <p class="hint">Las categorías agrupan los tipos de servicio que ve la clienta al reservar.</p>
        <div class="catalog-form-grid">
            <form method="post" action="{{ route('admin.categorias.store') }}" class="catalog-add-form" enctype="multipart/form-data">
                @csrf
                <div class="field"><label for="cat-nombre">Nueva categoría</label><input type="text" id="cat-nombre" name="nombre" required placeholder="Ej: Tratamientos"></div>
                <div class="field"><label for="cat-img">Imagen / icono</label><input type="file" id="cat-img" name="imagen" accept="image/*"></div>
                <div class="actions"><button type="submit" class="btn btn-primary">Agregar categoría</button><button type="button" class="btn btn-secondary" data-panel-close="service-categories">Cancelar</button></div>
            </form>
            <div class="catalog-add-form">
                <p class="avail-label">Eliminar categoría</p>
                @foreach($categorias as $categoria)
                    <form method="post" action="{{ route('admin.categorias.destroy', $categoria) }}" class="actions" data-confirm="¿Seguro que quiere eliminar esta categoría y sus tipos de servicio?" data-confirm-title="Eliminar">
                        @csrf @method('DELETE')
                        <span>{{ $categoria->nombre }} ({{ $categoria->servicios_count }})</span>
                        <button type="submit" class="btn btn-secondary">Eliminar</button>
                    </form>
                @endforeach
            </div>
        </div>
    </div>

    <div class="catalog-add-panel" id="service-create" hidden>
        <p class="hint">Estos servicios aparecen al reservar hora.</p>
        <form method="post" action="{{ route('admin.servicios.store') }}" class="catalog-add-form" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="volver_cat" value="{{ $categoriaId }}">
            <div class="catalog-form-grid">
                <div class="field"><label for="serv-cat">Categoría</label><select id="serv-cat" name="categoria_id" required>@foreach($categorias as $categoria)<option value="{{ $categoria->id }}" @selected($categoriaId === $categoria->id)>{{ $categoria->nombre }}</option>@endforeach</select></div>
                <div class="field"><label for="serv-nombre">Nombre</label><input type="text" id="serv-nombre" name="nombre" required placeholder="Ej: Color global"></div>
                <div class="field"><label for="serv-desc">Descripción</label><input type="text" id="serv-desc" name="descripcion" placeholder="Opcional"></div>
                <div class="field"><label for="serv-dur">Duración (minutos)</label><input type="number" id="serv-dur" name="duracion_min" value="60" min="60" step="30"></div>
                <div class="field"><label for="serv-precio">Precio (CLP)</label><input type="number" id="serv-precio" name="precio" value="15000" min="0"></div>
                <div class="field"><label><input type="checkbox" name="precio_desde" value="1"> Precio desde (variable según cabello y técnica)</label></div>
                <div class="field"><label for="serv-img">Imagen / icono</label><input type="file" id="serv-img" name="imagen" accept="image/*"></div>
            </div>
            <div class="actions"><button type="submit" class="btn btn-primary">Guardar servicio</button><button type="button" class="btn btn-secondary" data-panel-close="service-create">Cancelar</button></div>
        </form>
    </div>

    <div class="catalog-search"><label class="visually-hidden" for="service-search">Buscar servicio</label><div class="catalog-search-wrap"><span class="catalog-search-ico" aria-hidden="true">⌕</span><input type="search" id="service-search" data-filter-input="service-row" placeholder="Busca por nombre o categoría" autocomplete="off"></div></div>
    <div class="cat-filters" role="tablist" aria-label="Filtrar por categoría">
        <a class="book-chip {{ $categoriaId < 1 ? 'is-on' : '' }}" href="{{ route('admin.servicios.index') }}">Todas</a>
        @foreach($categorias as $categoria)<a class="book-chip {{ $categoriaId === $categoria->id ? 'is-on' : '' }}" href="{{ route('admin.servicios.index', ['cat' => $categoria->id]) }}">{{ $categoria->nombre }}</a>@endforeach
    </div>

    @if($servicios->isEmpty())
        <p class="hint">No hay servicios{{ $categoriaId ? ' en esta categoría' : '' }}.</p>
    @else
        <div class="catalog-table servicios-table" role="table" aria-label="Servicios">
            <div class="catalog-table-head" role="row"><span>Servicio</span><span>Categoría</span><span>Duración</span><span>Precio</span><span>Estado</span><span>Opciones</span></div>
            @foreach($servicios as $servicio)
                @php($imageUrl = $media->url($servicio->imagen))
                <article class="catalog-row" role="row" data-filter-row="service-row" data-filter-text="{{ Str::lower($servicio->nombre.' '.$servicio->categoria->nombre) }}">
                    <div class="catalog-row-main">
                        <div class="catalog-row-product"><div class="catalog-thumb">@if($imageUrl)<img src="{{ $imageUrl }}" alt="">@else<span>Sin foto</span>@endif</div><div><strong class="catalog-row-name">{{ $servicio->nombre }}</strong>@if($servicio->descripcion)<p class="catalog-row-desc">{{ $servicio->descripcion }}</p>@endif</div></div>
                        <div class="catalog-row-cat" data-label="Categoría">{{ $servicio->categoria->nombre }}</div>
                        <div class="catalog-row-dur" data-label="Duración">{{ \App\Support\Format::duracion($servicio->duracion_min) }}</div>
                        <div class="catalog-row-price" data-label="Precio">{{ $servicio->precioFormateado() }}</div>
                        <div class="catalog-row-status" data-label="Estado"><span class="catalog-badge {{ $servicio->activo ? 'is-on' : 'is-off' }}">{{ $servicio->activo ? 'Visible' : 'Oculto' }}</span></div>
                        <div class="catalog-row-actions">
                            <button type="button" class="catalog-icon-btn is-edit" data-panel-toggle="service-edit-{{ $servicio->id }}" aria-expanded="false" title="Editar">✎ <span>Editar</span></button>
                            <form method="post" action="{{ route('admin.servicios.destroy', $servicio) }}" data-confirm="¿Seguro que quiere eliminar este servicio?" data-confirm-title="Eliminar">@csrf @method('DELETE')<input type="hidden" name="volver_cat" value="{{ $categoriaId }}"><button type="submit" class="catalog-icon-btn is-delete" title="Eliminar">× <span>Eliminar</span></button></form>
                        </div>
                    </div>
                    <div class="catalog-row-edit" id="service-edit-{{ $servicio->id }}" hidden>
                        <form method="post" action="{{ route('admin.servicios.update', $servicio) }}" class="catalog-add-form" enctype="multipart/form-data">
                            @csrf @method('PUT')<input type="hidden" name="volver_cat" value="{{ $categoriaId }}">
                            <div class="catalog-form-grid">
                                <div class="field"><label>Categoría</label><select name="categoria_id" required>@foreach($categorias as $categoria)<option value="{{ $categoria->id }}" @selected($servicio->categoria_id === $categoria->id)>{{ $categoria->nombre }}</option>@endforeach</select></div>
                                <div class="field"><label>Nombre</label><input type="text" name="nombre" value="{{ $servicio->nombre }}" required></div>
                                <div class="field"><label>Descripción</label><input type="text" name="descripcion" value="{{ $servicio->descripcion }}"></div>
                                <div class="field"><label>Duración (min)</label><input type="number" name="duracion_min" value="{{ $servicio->duracion_min }}" min="60" step="30"></div>
                                <div class="field"><label>Precio (CLP)</label><input type="number" name="precio" value="{{ (int) $servicio->precio }}" min="0"></div>
                                <div class="field"><label><input type="checkbox" name="precio_desde" value="1" @checked($servicio->precio_desde)> Precio desde (variable según cabello y técnica)</label></div>
                                <div class="field"><label>Imagen / icono</label><input type="file" name="imagen" accept="image/*"></div>
                                <div class="field catalog-field-check"><label><input type="checkbox" name="activo" @checked($servicio->activo)> Visible al reservar</label></div>
                            </div>
                            <div class="actions"><button type="submit" class="btn btn-primary">Guardar cambios</button><button type="button" class="btn btn-secondary" data-panel-close="service-edit-{{ $servicio->id }}">Cancelar</button></div>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>
@endsection