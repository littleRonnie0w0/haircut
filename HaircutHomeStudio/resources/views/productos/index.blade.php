@extends('layouts.app', ['title' => 'Productos — Haircut Home Studio', 'page' => 'productos'])

@section('content')
<section class="productos-page">
    <div class="productos-head">
        <p class="home-kicker">Catálogo completo</p>
        <h1>Productos del estudio</h1>
        <p class="productos-lead">Conoce los productos que tenemos disponibles en Haircut Home Studio. Puedes verlos y consultarlos con nosotras durante tu visita.</p>
        <div class="productos-alert" role="note"><strong>Compra presencial.</strong> Los productos solo se pueden adquirir de forma presencial en el estudio; no realizamos ventas online.</div>
        <div class="productos-foot actions"><a href="{{ route('home') }}" class="btn btn-secondary">Volver al inicio</a><a href="{{ route('reservas.create') }}" class="btn btn-primary">Reservar hora</a></div>
    </div>
    @if($productos->isNotEmpty())
        <div class="productos-grid">@foreach($productos as $producto)<x-product-card :product="$producto" :lightbox="true" class="product-card-full" />@endforeach</div>
    @else
        <p class="home-empty">Aún no hay productos publicados en el catálogo.</p>
    @endif
</section>
@endsection