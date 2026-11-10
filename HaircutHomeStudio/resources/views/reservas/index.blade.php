@extends('layouts.app', ['title' => 'Haircut Home Studio - Mis reservas', 'page' => 'mis'])

@section('content')
@php
    $media = app(\App\Services\MediaService::class);
@endphp
<section class="card">
    <h2>Mis Reservas</h2>
    @if($reservas->isEmpty())
        <p>Aún no tienes reservas. <a href="{{ route('reservas.create') }}">Crea la primera</a>.</p>
    @else
        <table class="stack-table">
            <thead><tr><th>Fecha / Hora</th><th>Servicio</th><th>Lugar</th><th>Estado</th><th>Acción</th></tr></thead>
            <tbody>
            @foreach($reservas as $reserva)
                @php
                    $fotoUrls = array_values(array_filter(array_map(
                        fn (string $ruta) => $media->url($ruta),
                        $reserva->rutasFoto(),
                    )));
                    $simUrl = $reserva->imagen_simulada ? route('reservas.simulated-image', $reserva) : null;
                @endphp
                <tr>
                    <td data-label="Fecha / Hora">{{ $reserva->fecha->format('d/m/Y') }} · {{ substr($reserva->hora, 0, 5) }}</td>
                    <td data-label="Servicio">
                        {{ $reserva->servicio->nombre }}
                        @if($fotoUrls !== [] || $simUrl)
                            <br>
                            @foreach($fotoUrls as $i => $fotoUrl)
                                <button type="button" class="linkish" data-gallery-open data-gallery-src="{{ $fotoUrl }}" data-gallery-alt="Tu foto de referencia {{ $i + 1 }}" data-no-loader>Ver referencia {{ count($fotoUrls) > 1 ? $i + 1 : '' }}</button>
                            @endforeach
                            @if($simUrl)
                                <button type="button" class="linkish" data-gallery-open data-gallery-src="{{ $simUrl }}" data-gallery-alt="Simulación adjunta" data-no-loader>Ver simulación adjunta</button>
                            @endif
                        @endif
                    </td>
                    <td data-label="Lugar">{{ \App\Support\Format::lugar($reserva->lugar) }}</td>
                    <td data-label="Estado"><span class="status {{ \App\Support\Format::estadoClase($reserva->estado) }}">{{ \App\Support\Format::estado($reserva->estado) }}</span></td>
                    <td data-label="Acción">
                        @if($reserva->puedeCancelar())
                            <form method="post" action="{{ route('reservas.cancel', $reserva) }}" data-confirm="¿Seguro que quiere cancelar esta reserva?" data-confirm-title="Cancelar">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn-secondary">Cancelar</button>
                            </form>
                        @elseif(in_array($reserva->estado, ['pendiente', 'confirmada'], true))
                            <span class="hint">Ya no se puede cancelar (faltan menos de 10 min)</span>
                        @else
                            <span class="hint">(sin acciones)</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p class="hint">El pago es presencial. Puedes cancelar hasta 10 minutos antes de la hora.</p>
    @endif
</section>
@endsection
