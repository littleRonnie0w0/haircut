@extends('layouts.app', ['title' => 'Reserva aquí', 'page' => 'nueva'])

@php
    $media = app(\App\Services\MediaService::class);
    $steps = [
        'servicio' => ['number' => 1, 'label' => 'Servicio'],
        'fecha' => ['number' => 2, 'label' => 'Fecha'],
        'referencia' => ['number' => 3, 'label' => 'Referencia'],
        'lugar' => ['number' => 4, 'label' => 'Lugar'],
        'resumen' => ['number' => 5, 'label' => 'Confirmar'],
    ];
    $initialStep = request()->string('paso')->toString();
    $initialStep = array_key_exists($initialStep, $steps) ? $initialStep : 'servicio';
    $baseQuery = [
        'cat' => $categoria->slug,
        'serv' => $servicio->id,
        'lugar' => $lugar,
        'fecha' => $fecha,
        'hora' => $hora,
        'anio' => $anio,
        'mes' => $mes,
        'ai_generation' => $aiGeneration?->id,
        'paso' => $initialStep,
    ];
    $bookingUrl = function (array $changes = []) use ($baseQuery) {
        $query = array_filter(array_merge($baseQuery, $changes), fn ($value) => $value !== '' && $value !== null);
        return route('reservas.create', $query);
    };
    $monthPosition = collect($mesesVisibles)->search(fn ($item) => (int) $item['anio'] === $anio && (int) $item['mes'] === $mes);
    $previousMonth = $monthPosition !== false ? ($mesesVisibles[$monthPosition - 1] ?? null) : null;
    $nextMonth = $monthPosition !== false ? ($mesesVisibles[$monthPosition + 1] ?? null) : null;
    $slotSet = array_flip($slots);
    $durationHours = max(1, (int) ceil($servicio->duracion_min / 60));
    $range = [];
    if ($hora !== '') {
        $start = \Carbon\CarbonImmutable::createFromFormat('H:i', $hora);
        for ($i = 0; $i < $durationHours; $i++) {
            $range[$start->addHours($i)->format('H:i')] = true;
        }
    }
    $hourLabel = fn (string $value) => \Carbon\CarbonImmutable::createFromFormat('H:i', $value)->format('H:i');
    $longService = $durationHours >= 3;
    $hourGroups = $longService
        ? ['Mañana' => array_values(array_filter($todosSlots, fn ($value) => $value < '13:00')), 'Tarde' => array_values(array_filter($todosSlots, fn ($value) => $value >= '13:00'))]
        : ['' => $todosSlots];
    $endHour = $hora !== '' ? \Carbon\CarbonImmutable::createFromFormat('H:i', $hora)->addHours($durationHours)->format('H:i') : '';
    $fallbackIcons = ['corte' => 'corte.png', 'color' => 'color.png', 'tratamientos' => 'tratamiento.png'];
@endphp

@section('content')
<section class="book" data-book-wizard data-book-initial-step="{{ $initialStep }}">
    <div class="book-head">
        <div><p class="home-kicker">Agenda tu visita</p><h2>Reserva aquí</h2></div>
        <a href="{{ auth()->user()?->esAdminImagen() ? route('ai.index') : route('home') }}" class="book-close" aria-label="Cancelar reserva" title="Cancelar reserva">×</a>
    </div>

    @if($aiGeneration)
        <div class="book-suggest" role="status">
            Servicio preseleccionado desde el simulador: <strong>{{ $servicio->nombre }}</strong>.
            Elige fecha y hora para continuar.
        </div>
    @endif

    <ol class="book-progress" aria-label="Progreso de reserva">
        @foreach($steps as $key => $step)
            <li data-book-progress="{{ $key }}"><span>{{ $step['number'] }}</span><small>{{ $step['label'] }}</small></li>
        @endforeach
    </ol>

    <form method="post" action="{{ route('reservas.store') }}" class="book-reserve book-wizard-form" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="servicio_id" value="{{ $servicio->id }}">
        <input type="hidden" name="fecha" value="{{ $fecha }}">
        <input type="hidden" name="hora" value="{{ $hora }}">
        @if($aiGeneration)<input type="hidden" name="ai_generation_id" value="{{ $aiGeneration->id }}">@endif

        <section class="book-step" data-book-step="servicio" @if($initialStep !== 'servicio') hidden @endif>
            <div class="book-step-heading"><p class="home-kicker">Paso 1</p><h3>Elige el servicio</h3><p>Selecciona una categoría y después el servicio que quieres agendar.</p></div>
            <div class="book-category-tabs" role="tablist" aria-label="Categorías de servicio">
                @foreach($categorias as $item)
                    @php
                        $isActiveCategory = $item->id === $categoria->id;
                        $icon = $media->url($item->imagen) ?? asset('img/iconos/'.($fallbackIcons[$item->slug] ?? 'tratamiento.png'));
                    @endphp
                    <a href="{{ $bookingUrl(['cat' => $item->slug, 'serv' => $item->servicios->first()->id, 'fecha' => '', 'hora' => '', 'paso' => 'servicio']) }}"
                        id="book-category-tab-{{ $item->slug }}"
                        class="book-category-tab {{ $isActiveCategory ? 'is-active' : '' }}"
                        role="tab"
                        aria-selected="{{ $isActiveCategory ? 'true' : 'false' }}"
                        data-book-category-tab="{{ $item->slug }}"
                        data-book-category-service-url="{{ $bookingUrl(['cat' => $item->slug, 'serv' => $item->servicios->first()->id, 'fecha' => '', 'hora' => '', 'paso' => 'fecha']) }}"
                        data-no-loader>
                        <img src="{{ $icon }}" alt=""><span>{{ $item->nombre }}</span>
                    </a>
                @endforeach
            </div>
            @foreach($categorias as $categoryItem)
                <div class="book-service-options" role="tabpanel" aria-labelledby="book-category-tab-{{ $categoryItem->slug }}" data-book-category-panel="{{ $categoryItem->slug }}" @if($categoryItem->id !== $categoria->id) hidden @endif>
                    @foreach($categoryItem->servicios as $item)
                        @php
                            $selected = $item->id === $servicio->id;
                            $icon = $media->url($item->imagen) ?? asset('img/iconos/'.($fallbackIcons[$categoryItem->slug] ?? 'tratamiento.png'));
                        @endphp
                        <a class="book-service-option {{ $selected ? 'is-selected' : '' }}" href="{{ $bookingUrl(['cat' => $categoryItem->slug, 'serv' => $item->id, 'fecha' => '', 'hora' => '', 'paso' => 'servicio']) }}" data-book-service-option data-book-service-id="{{ $item->id }}" data-book-service-url="{{ $bookingUrl(['cat' => $categoryItem->slug, 'serv' => $item->id, 'fecha' => '', 'hora' => '', 'paso' => 'fecha']) }}" data-no-loader><img src="{{ $icon }}" alt=""><div><strong>{{ $item->nombre }}</strong><small>{{ $item->precioFormateado() }}</small><small>{{ $item->descripcion ?: 'Servicio de Haircut Home Studio.' }}</small></div><span>{{ $selected ? 'Seleccionado' : 'Elegir' }}</span></a>
                    @endforeach
                    <p class="hint">Los servicios con precio “desde” pueden variar según el largo, la cantidad de cabello y la técnica requerida.</p>
                </div>
            @endforeach
            <div class="book-step-actions"><a href="{{ route('home') }}" class="book-step-back">← Volver</a><button type="button" class="book-step-next" data-book-next="fecha">Siguiente <span aria-hidden="true">→</span></button></div>
        </section>

        <section class="book-step" data-book-step="fecha" @if($initialStep !== 'fecha') hidden @endif>
            <div class="book-step-heading"><p class="home-kicker">Paso 2</p><h3>Fecha y hora</h3><p>{{ $longService ? 'Para este servicio largo recomendamos horarios de mañana.' : 'Elige una fecha y una hora disponible.' }}</p></div>
            <div class="book-month">
                @if($previousMonth)<a class="book-nav" href="{{ $bookingUrl(['anio' => $previousMonth['anio'], 'mes' => $previousMonth['mes'], 'fecha' => '', 'hora' => '', 'paso' => 'fecha']) }}" aria-label="Mes anterior" data-no-loader>‹</a>@else<span class="book-nav is-off" aria-hidden="true"></span>@endif
                <div class="book-month-pill">{{ \App\Support\Format::MESES[$mes] }} {{ $anio }}</div>
                @if($nextMonth)<a class="book-nav" href="{{ $bookingUrl(['anio' => $nextMonth['anio'], 'mes' => $nextMonth['mes'], 'fecha' => '', 'hora' => '', 'paso' => 'fecha']) }}" aria-label="Mes siguiente" data-no-loader>›</a>@else<span class="book-nav is-off" aria-hidden="true"></span>@endif
            </div>
            <table class="book-cal">
                <tr><th>Lun</th><th>Mar</th><th>Mié</th><th>Jue</th><th>Vie</th><th>Sáb</th><th>Dom</th></tr>
                @foreach($semanas as $semana)
                    <tr>
                    @foreach($semana as $day)
                        <td>
                        @if($day)
                            @php
                                $dayDate = sprintf('%04d-%02d-%02d', $anio, $mes, $day);
                                $state = $estados[$day];
                                $selected = $dayDate === $fecha;
                                $class = 'd st-'.$state.($selected ? ' is-sel' : '');
                                $puedeElegir = in_array($state, ['disponible', 'ocupado'], true);
                            @endphp
                            @if($puedeElegir)
                                <a class="{{ $class }}" href="{{ $bookingUrl(['fecha' => $dayDate, 'hora' => $selected ? $hora : '', 'anio' => $anio, 'mes' => $mes, 'paso' => 'fecha']) }}" data-no-loader>{{ $day }}</a>
                            @else<span class="{{ $class }}">{{ $day }}</span>@endif
                        @endif
                        </td>
                    @endforeach
                    </tr>
                @endforeach
            </table>
            <ul class="book-legend"><li><span class="book-dot is-free"></span> Disponible</li><li><span class="book-dot is-busy"></span> No disponible</li></ul>

            @if($fecha !== '')
                <div class="book-time-picker">
                    <div class="book-time-picker-head"><h4>Hora para {{ \Carbon\CarbonImmutable::parse($fecha)->format('d/m') }}</h4>@if($sugerencias['misma'])<a href="{{ $bookingUrl(['hora' => $sugerencias['misma'], 'paso' => 'fecha']) }}">Más cercana: {{ $hourLabel($sugerencias['misma']) }}</a>@endif</div>
                    <div class="book-hours">
                        @foreach($hourGroups as $group => $hours)
                            @if($group !== '')<p class="book-hours-kicker">{{ $group }}</p>@endif
                            @foreach($hours as $slot)
                                @php
                                    $free = isset($slotSet[$slot]);
                                    $selectedStart = $slot === $hora;
                                    $class = 'hour-pill';
                                    if ($selectedStart) $class .= ' is-on';
                                    elseif (isset($range[$slot])) $class .= ' is-range';
                                    elseif ($free) $class .= ' is-free'.($longService && $slot < '13:00' ? ' is-am' : '');
                                    else $class .= ' is-busy';
                                @endphp
                                @if($free && !$selectedStart)<a class="{{ $class }}" href="{{ $bookingUrl(['hora' => $slot, 'paso' => 'fecha']) }}">{{ $hourLabel($slot) }}</a>@else<span class="{{ $class }}">{{ $hourLabel($slot) }}</span>@endif
                            @endforeach
                        @endforeach
                    </div>
                    <ul class="book-legend"><li><span class="book-dot is-free"></span> Disponible</li><li><span class="book-dot is-range"></span> Tu bloque</li><li><span class="book-dot is-busy"></span> No disponible</li></ul>
                </div>
            @else
                <p class="book-step-empty">Selecciona un día disponible para ver las horas.</p>
            @endif
            <p class="book-step-warning" data-book-step-warning hidden>Selecciona una fecha y hora disponible para continuar.</p>
            <div class="book-step-actions"><button type="button" class="book-step-back" data-book-back="servicio">← Atrás</button><button type="button" class="book-step-next" data-book-next="referencia">Siguiente <span aria-hidden="true">→</span></button></div>
        </section>

        <section class="book-step" data-book-step="referencia" @if($initialStep !== 'referencia') hidden @endif>
            <div class="book-step-heading"><p class="home-kicker">Paso 3</p><h3>Imágenes de referencia</h3><p>Opcional: agrega hasta 2 inspiraciones para que el profesional entienda mejor el resultado que buscas.</p></div>
            @if($aiGeneration)
                <div class="book-ai-reference"><img src="{{ route('ai.image', [$aiGeneration, 'output']) }}" alt="Simulación de {{ $aiGeneration->style_label ?? 'estilo' }}"><div><strong>Simulación IA incluida</strong><p>{{ $aiGeneration->style_label ?? 'Referencia de estilo' }}</p></div></div>
            @endif
            <div class="book-card">
                <div class="book-photo-panel" id="book-photo-panel" data-book-photos data-book-photo-max="2">
                    <div class="book-photo-add" data-book-photo-add>
                        <input class="book-photo-input" type="file" id="book-foto-picker" accept="image/jpeg,image/png,image/webp,image/gif" data-book-photo-picker>
                        <label class="book-photo-drop" for="book-foto-picker" data-book-photo-drop>
                            <span class="book-photo-ico" aria-hidden="true">＋</span>
                            <span class="book-photo-title">Agregar imagen</span>
                            <span class="book-photo-sub">JPG, PNG o WEBP · máx. 3 MB · hasta 2</span>
                        </label>
                    </div>
                    <ul class="book-photo-list" data-book-photo-list hidden></ul>
                </div>
                <p class="book-photo-msg" id="book-photo-msg" hidden></p>
            </div>
            <div class="book-step-actions"><button type="button" class="book-step-back" data-book-back="fecha">← Atrás</button><button type="button" class="book-step-next" data-book-next="lugar">Siguiente <span aria-hidden="true">→</span></button></div>
        </section>

        <section class="book-step" data-book-step="lugar" @if($initialStep !== 'lugar') hidden @endif>
            <div class="book-step-heading"><p class="home-kicker">Paso 4</p><h3>Lugar de atención</h3><p>Elige dónde prefieres realizar tu servicio.</p></div>
            <div class="book-place-options">
                @foreach(['salon' => ['En el salón', 'lugar-salon.png', 'Atención en Haircut Home Studio'], 'domicilio' => ['A domicilio', 'lugar-domicilio.png', 'Atención en la dirección acordada']] as $key => [$label, $icon, $description])
                    <label class="book-place-option"><input type="radio" name="lugar" value="{{ $key }}" @checked($lugar === $key)><span class="book-place-icon"><img class="icon-pack" src="{{ asset('img/iconos/'.$icon) }}" alt=""></span><span><strong>{{ $label }}</strong><small>{{ $description }}</small></span></label>
                @endforeach
            </div>
            <div class="book-step-actions"><button type="button" class="book-step-back" data-book-back="referencia">← Atrás</button><button type="button" class="book-step-next" data-book-next="resumen">Ver resumen <span aria-hidden="true">→</span></button></div>
        </section>

        <section class="book-step" data-book-step="resumen" @if($initialStep !== 'resumen') hidden @endif>
            <div class="book-step-heading"><p class="home-kicker">Paso 5</p><h3>Revisa tu reserva</h3><p>Confirma que los datos estén correctos antes de enviar la solicitud.</p></div>
            <div class="book-summary">
                <div class="book-summary-row"><span>Servicio</span><strong>{{ $servicio->nombre }}</strong></div>
                <div class="book-summary-row"><span>Fecha</span><strong>{{ $fecha !== '' ? \Carbon\CarbonImmutable::parse($fecha)->format('d/m/Y') : 'Pendiente' }}</strong></div>
                <div class="book-summary-row"><span>Hora</span><strong>{{ $hora !== '' ? $hourLabel($hora).' – '.$hourLabel($endHour) : 'Pendiente' }}</strong></div>
                <div class="book-summary-row"><span>Lugar</span><strong data-book-summary-place>{{ \App\Support\Format::lugar($lugar) }}</strong></div>
                <div class="book-summary-row book-summary-total"><span>Valor referencial</span><strong>{{ $servicio->precioFormateado() }}</strong></div>
            </div>
            @if($servicio->precio_desde)<p class="hint">El precio puede variar según el largo, la cantidad de cabello y la técnica requerida.</p>@endif
            <p class="hint">El pago es presencial. Puedes cancelar hasta 10 minutos antes de la hora confirmada.</p>
            <div class="book-step-actions"><button type="button" class="book-step-back" data-book-back="lugar">← Atrás</button><button type="submit" class="book-confirm" @disabled($fecha === '' || $hora === '')>Confirmar reserva <span aria-hidden="true">→</span></button></div>
        </section>
    </form>
</section>
@endsection
