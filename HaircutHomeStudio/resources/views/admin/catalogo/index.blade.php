@extends('layouts.app', ['title' => 'Haircut Studio - Catálogo', 'section' => 'admin', 'page' => 'catalogo'])

@php($media = app(\App\Services\MediaService::class))

@section('content')
<section class="card catalog-admin">
    <div class="catalog-toolbar"><h2>Productos</h2><button type="button" class="btn btn-primary" data-panel-toggle="product-create" aria-expanded="false">Agregar producto +</button></div>
    <div class="catalog-add-panel" id="product-create" hidden>
        <p class="hint">Estos productos aparecen en la página principal. La compra es presencial.</p>
        <form method="post" action="{{ route('admin.catalogo.store') }}" enctype="multipart/form-data" class="catalog-add-form">
            @csrf
            <div class="catalog-form-grid">
                <div class="field"><label for="prod-nombre">Nombre</label><input type="text" id="prod-nombre" name="nombre" required maxlength="50" placeholder="Ej: Shampoo de color"></div>
                <div class="field"><label for="prod-desc">Descripción</label><input type="text" id="prod-desc" name="descripcion" maxlength="200" placeholder="Opcional"></div>
                <div class="field"><label for="prod-cat">Categoría</label><select id="prod-cat" name="categoria_id">@foreach($categorias as $categoria)<option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>@endforeach</select></div>
                <div class="field"><label for="prod-precio">Precio (CLP)</label><input type="number" id="prod-precio" name="precio" value="12990" min="0"></div>
                <div class="field"><label for="prod-img">Foto</label><input type="file" id="prod-img" name="imagen" accept="image/*"></div>
            </div>
            <div class="actions"><button type="submit" class="btn btn-primary">Guardar producto</button><button type="button" class="btn btn-secondary" data-panel-close="product-create">Cancelar</button></div>
        </form>
    </div>
    <div class="catalog-search"><label class="visually-hidden" for="product-search">Buscar producto</label><div class="catalog-search-wrap"><span class="catalog-search-ico" aria-hidden="true">⌕</span><input type="search" id="product-search" data-filter-input="product-row" placeholder="Busca por nombre" autocomplete="off"></div></div>

    @if($productos->isEmpty())
        <p class="hint">No hay productos en el catálogo. Usa “Agregar producto +” para crear el primero.</p>
    @else
        <div class="catalog-table" role="table" aria-label="Catálogo de productos">
            <div class="catalog-table-head" role="row"><span>Producto</span><span>Precio</span><span>Estado</span><span>Categoría</span><span>Opciones</span></div>
            @foreach($productos as $producto)
                @php($imageUrl = $media->urlProducto($producto->imagen, $producto->categoria?->nombre, $producto->nombre))
                <article class="catalog-row" role="row" data-filter-row="product-row" data-filter-text="{{ Str::lower($producto->nombre) }}">
                    <div class="catalog-row-main">
                        <div class="catalog-row-product"><div class="catalog-thumb">@if($imageUrl)<img class="icon-pack" src="{{ $imageUrl }}" alt="">@else<span>Sin foto</span>@endif</div><div><strong class="catalog-row-name">{{ $producto->nombre }}</strong>@if($producto->descripcion)<p class="catalog-row-desc">{{ $producto->descripcion }}</p>@endif</div></div>
                        <div class="catalog-row-price" data-label="Precio">{{ \App\Support\Format::precio($producto->precio) }}</div>
                        <div class="catalog-row-status" data-label="Estado"><span class="catalog-badge {{ $producto->activo ? 'is-on' : 'is-off' }}">{{ $producto->activo ? 'Visible' : 'Oculto' }}</span></div>
                        <div class="catalog-row-orden" data-label="Categoría">{{ $producto->categoria?->nombre ?? '—' }}</div>
                        <div class="catalog-row-actions">
                            <button type="button" class="catalog-icon-btn is-edit" data-panel-toggle="product-edit-{{ $producto->id }}" aria-expanded="false" title="Editar">✎ <span>Editar</span></button>
                            <form method="post" action="{{ route('admin.catalogo.destroy', $producto) }}" data-confirm="¿Seguro que quiere eliminar este producto?" data-confirm-title="Eliminar">@csrf @method('DELETE')<button type="submit" class="catalog-icon-btn is-delete" title="Eliminar">× <span>Eliminar</span></button></form>
                        </div>
                    </div>
                    <div class="catalog-row-edit" id="product-edit-{{ $producto->id }}" hidden>
                        <form method="post" action="{{ route('admin.catalogo.update', $producto) }}" enctype="multipart/form-data" class="catalog-add-form">
                            @csrf @method('PUT')
                            <div class="catalog-form-grid">
                                <div class="field"><label>Nombre</label><input type="text" name="nombre" value="{{ $producto->nombre }}" required maxlength="50"></div>
                                <div class="field"><label>Descripción</label><input type="text" name="descripcion" value="{{ $producto->descripcion }}" maxlength="200"></div>
                                <div class="field"><label>Categoría</label><select name="categoria_id">@foreach($categorias as $categoria)<option value="{{ $categoria->id }}" @selected($producto->categoria_id === $categoria->id)>{{ $categoria->nombre }}</option>@endforeach</select></div>
                                <div class="field"><label>Precio (CLP)</label><input type="number" name="precio" value="{{ (int) $producto->precio }}" min="0"></div>
                                <div class="field"><label>Foto</label><input type="file" name="imagen" accept="image/*"></div>
                                <div class="field catalog-field-check"><label><input type="checkbox" name="activo" @checked($producto->activo)> Visible en el inicio</label></div>
                            </div>
                            <div class="actions"><button type="submit" class="btn btn-primary">Guardar cambios</button><button type="button" class="btn btn-secondary" data-panel-close="product-edit-{{ $producto->id }}">Cancelar</button></div>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>
@endsection