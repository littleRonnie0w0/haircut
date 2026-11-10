<ul class="hours-list">
@foreach($items as $item)
    @php($started = now()->greaterThanOrEqualTo($item['inicio']))
    <li class="hours-item" data-start="{{ $item['inicio']->format('Y-m-d\TH:i:s') }}" data-end="{{ $item['fin']->format('Y-m-d\TH:i:s') }}" data-base="{{ $item['estado'] }}">
        <div class="hours-when">
            <time>{{ $item['inicio']->format('H:i') }} – {{ $item['fin']->format('H:i') }}</time>
            <form method="post" action="{{ route('peluquero.horas.update') }}" class="hours-del" data-confirm="{{ $started ? '¿Seguro que quiere marcar que no llegó? Queda en el informe.' : '¿Seguro que quiere eliminar esta hora de la agenda?' }}" data-confirm-title="{{ $started ? 'Confirmar' : 'Eliminar' }}">
                @csrf @method('PATCH')
                <input type="hidden" name="accion" value="{{ $started ? 'noshow' : 'borrar' }}">
                @foreach($item['ids'] as $id)<input type="hidden" name="ids[]" value="{{ $id }}">@endforeach
                <button type="submit" class="hours-del-btn" title="{{ $started ? 'No llegó' : 'Quitar de la agenda' }}" aria-label="{{ $started ? 'No llegó' : 'Quitar de la agenda' }}">×</button>
            </form>
        </div>
        <div><strong>{{ $item['cliente'] }}</strong><span>{{ $item['servicio'] }} · {{ \App\Support\Format::lugar($item['lugar']) }} · {{ \App\Support\Format::duracion($item['duracion']) }}</span></div>
        <span class="dash-pill hours-status st-{{ $item['estado_ui'] }}">{{ \App\Support\Format::estado($item['estado_ui']) }}</span>
    </li>
@endforeach
</ul>