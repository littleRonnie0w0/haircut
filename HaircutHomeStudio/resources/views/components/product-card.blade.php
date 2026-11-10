@props([
    'product',
    'lightbox' => false,
])
@php($imageUrl = app(\App\Services\MediaService::class)->urlProducto($product->imagen, $product->categoria?->nombre, $product->producto))
<article {{ $attributes->class(['product-card']) }}>
    <div class="product-photo">
        @if($imageUrl && $lightbox)
            <button
                type="button"
                class="product-photo-trigger"
                data-gallery-open
                data-gallery-group="catalogo"
                data-gallery-mode="catalogo"
                data-gallery-src="{{ $imageUrl }}"
                data-gallery-alt="{{ $product->producto }}"
                data-no-loader
                aria-label="Ver {{ $product->producto }} en grande"
            >
                <img src="{{ $imageUrl }}" alt="{{ $product->producto }}" loading="lazy">
            </button>
        @elseif($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $product->producto }}" loading="lazy">
        @else
            <span class="product-placeholder">Sin imagen</span>
        @endif
    </div>
    <h3>{{ $product->producto }}</h3>
    <p class="product-desc">{{ $product->descripcion }}</p>
    <p class="product-price">{{ \App\Support\Format::precio($product->precio) }}</p>
</article>
