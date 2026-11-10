@extends('layouts.app', ['title' => 'Haircut Studio - Solicitudes', 'section' => 'admin', 'page' => 'solicitudes'])

@section('content')
@php
    $media = app(\App\Services\MediaService::class);
@endphp
<section class="card">
    <h2>Solicitudes Pendientes</h2>
    @if($solicitudes->isEmpty())
        <p>No hay solicitudes pendientes.</p>
    @else
        <table class="stack-table">
            <thead><tr><th>Cliente</th><th>Servicio</th><th>Lugar</th><th>Fecha / Hora</th><th>Foto</th><th>Acción</th></tr></thead>
            <tbody>
            @foreach($solicitudes as $solicitud)
                @php
                    $fotoUrls = array_values(array_filter(array_map(
                        fn (string $ruta) => $media->url($ruta),
                        $solicitud->rutasFoto(),
                    )));
                    $simUrl = $solicitud->imagen_simulada ? route('reservas.simulated-image', $solicitud) : null;
                    $cliente = $solicitud->usuario->nombre;
                @endphp
                <tr>
                    <td data-label="Cliente">{{ $cliente }}</td>
                    <td data-label="Servicio">{{ $solicitud->servicio->nombre }}</td>
                    <td data-label="Lugar">{{ \App\Support\Format::lugar($solicitud->lugar) }}</td>
                    <td data-label="Fecha / Hora">{{ $solicitud->fecha->format('d/m') }} · {{ substr($solicitud->hora, 0, 5) }}</td>
                    <td data-label="Foto">
                        @foreach($fotoUrls as $i => $fotoUrl)
                            <button type="button" class="linkish" data-gallery-open data-gallery-src="{{ $fotoUrl }}" data-gallery-alt="Referencia {{ $i + 1 }} de {{ $cliente }}" data-no-loader>Referencia {{ count($fotoUrls) > 1 ? $i + 1 : '' }}</button>
                        @endforeach
                        @if($simUrl)
                            <button type="button" class="linkish" data-gallery-open data-gallery-src="{{ $simUrl }}" data-gallery-alt="Simulación IA de {{ $cliente }}" data-no-loader>Simulación IA</button>
                        @endif
                        @if($fotoUrls === [] && ! $simUrl)
                            <span class="hint">(sin foto)</span>
                        @endif
                    </td>
                    <td data-label="Acción">
                        <div class="actions">
                            <form method="post" action="{{ route('peluquero.solicitudes.update', $solicitud) }}">@csrf @method('PATCH')<input type="hidden" name="accion" value="confirmar"><button type="submit" class="btn-primary">Confirmar</button></form>
                            <form method="post" action="{{ route('peluquero.solicitudes.update', $solicitud) }}" data-confirm="¿Seguro que quiere cancelar esta solicitud?" data-confirm-title="Cancelar">@csrf @method('PATCH')<input type="hidden" name="accion" value="rechazar"><button type="submit" class="btn-secondary">Rechazar</button></form>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p class="hint">Al confirmar, la hora queda reservada de forma definitiva. Al rechazar, el cupo se libera de inmediato.</p>
    @endif
</section>
@endsection
