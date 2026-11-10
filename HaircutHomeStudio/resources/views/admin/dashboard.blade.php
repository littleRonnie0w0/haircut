@extends('layouts.app', ['title' => 'Haircut Studio - Panel', 'section' => 'admin', 'page' => 'panel'])

@section('content')
<section class="dash-head">
    <div>
        <p class="dash-kicker">Panel</p>
        <h2>Resumen del studio</h2>
        <p class="hint">{{ \App\Support\Format::MESES[now()->month] }} {{ now()->year }} · Haircut Home Studio</p>
    </div>
    <section class="dash-export-mini" aria-label="Descargar reporte mensual">
        <h3>Descargar reporte mensual</h3>
        <form method="get" action="{{ route('peluquero.reportes.export') }}" class="dash-export-form" data-no-loader>
            <div class="field"><label for="anio">Año</label><select id="anio" name="anio">@for($year = now()->year - 1; $year <= now()->year + 1; $year++)<option value="{{ $year }}" @selected($year === now()->year)>{{ $year }}</option>@endfor</select></div>
            <div class="field"><label for="mes">Mes</label><select id="mes" name="mes">@foreach(\App\Support\Format::MESES as $number => $name)<option value="{{ $number }}" @selected($number === now()->month)>{{ $name }}</option>@endforeach</select></div>
            <div class="actions"><button type="submit" name="formato" value="pdf" class="btn btn-primary">PDF</button><button type="submit" name="formato" value="csv" class="btn btn-secondary">Excel</button></div>
        </form>
        <p class="hint">Confirmadas, canceladas y rechazadas del mes.</p>
    </section>
</section>

<section class="dash-kpis">
    <article class="dash-kpi"><p class="dash-kpi-label">Pendientes</p><p class="dash-kpi-value">{{ $pendientes }}</p><p class="hint">Solicitudes por confirmar</p></article>
    <article class="dash-kpi"><p class="dash-kpi-label">Hoy</p><p class="dash-kpi-value">{{ $hoy }}</p><p class="hint">Citas de este día</p></article>
    <article class="dash-kpi"><p class="dash-kpi-label">Confirmadas</p><p class="dash-kpi-value">{{ $confirmadasMes }}</p><p class="hint">Este mes</p></article>
    <article class="dash-kpi dash-kpi-accent"><p class="dash-kpi-label">Ingresos</p><p class="dash-kpi-value">{{ \App\Support\Format::precio($ingresosMes) }}</p><p class="hint">Estimado del mes</p></article>
</section>

<div class="dash-grid">
    <section class="card dash-card">
        <h3>Reservas últimos 6 meses</h3>
        <div class="dash-bars" aria-label="Reservas por mes">
            @foreach($meses as $item)
                <div class="dash-bar-col">
                    <div class="dash-bar-track"><span class="dash-bar dash-bar-all" style="height: {{ round(($item['total'] / $maxMes) * 100) }}%"></span><span class="dash-bar dash-bar-ok" style="height: {{ round(($item['ok'] / $maxMes) * 100) }}%"></span></div>
                    <strong>{{ $item['total'] }}</strong><small>{{ $item['label'] }}</small>
                </div>
            @endforeach
        </div>
        <ul class="dash-legend"><li><span class="swatch swatch-all"></span> Total</li><li><span class="swatch swatch-ok"></span> Confirmadas / realizadas</li></ul>
    </section>
    <section class="card dash-card">
        <h3>Servicios más pedidos</h3>
        @if($topServicios->isEmpty())
            <p class="hint">Aún no hay suficientes reservas para este gráfico.</p>
        @else
            <ul class="dash-ranks">
                @foreach($topServicios as $item)
                    <li><div class="dash-rank-row"><span>{{ $item['nombre'] }}</span><strong>{{ $item['n'] }}</strong></div><span class="dash-rank-bar" style="width: {{ round(($item['n'] / $maxTop) * 100) }}%"></span></li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
@endsection