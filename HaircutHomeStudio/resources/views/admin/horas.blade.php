@extends('layouts.app', ['title' => 'Haircut Studio - Horas', 'section' => 'admin', 'page' => 'horas'])

@php
    $dates = array_keys($porDia);
    $featuredDate = isset($porDia[now()->toDateString()]) ? now()->toDateString() : ($dates[0] ?? null);
    $otherDates = array_values(array_filter($dates, fn ($date) => $date !== $featuredDate));
@endphp

@section('content')
@if(!$featuredDate)
    <section class="card"><h2>Horas</h2><p>No hay horas agendadas hacia adelante. Las de días que ya terminaron se ocultan solas.</p></section>
@else
    @php($featured = $porDia[$featuredDate])
    <section class="card hours-featured" data-hours-day>
        <p class="hours-kicker">{{ $featuredDate === now()->toDateString() ? 'Hoy' : 'Próximo día' }}</p>
        <h2>{{ \App\Support\Format::tituloDia(\Carbon\CarbonImmutable::parse($featuredDate)) }}</h2>
        <p class="hint hours-count">{{ count($featured) }} {{ count($featured) === 1 ? 'persona' : 'personas' }}</p>
        @include('admin.partials.hours-list', ['items' => $featured])
    </section>
    @if($otherDates)
        <section class="card">
            <h3>Otros días</h3>
            @foreach($otherDates as $date)
                <div class="hours-day" data-hours-day>
                    <h3>{{ \App\Support\Format::tituloDia(\Carbon\CarbonImmutable::parse($date)) }}</h3>
                    <p class="hint hours-count">{{ count($porDia[$date]) }} {{ count($porDia[$date]) === 1 ? 'persona' : 'personas' }}</p>
                    @include('admin.partials.hours-list', ['items' => $porDia[$date]])
                </div>
            @endforeach
        </section>
    @endif
@endif
@endsection