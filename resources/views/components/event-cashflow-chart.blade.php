@props([
    'series',
    'event',
])

<article {{ $attributes->class(['broker-card', 'broker-cashflow-evolution']) }} role="listitem">
    <header class="broker-cashflow-map-head">
        <div>
            <x-event-category-symbol category="financeiro" />
            <h2 class="broker-cashflow-map-title">Evolução dos gastos</h2>
        </div>
        <p class="broker-cashflow-map-range">Saída de cada semana, em relação à festa</p>
    </header>

    @if (! $series['hasPoints'])
        <div class="broker-cashflow-map-empty">
            <p>Nada datado nessa janela para desenhar a evolução.</p>
        </div>
    @else
        <div class="broker-cashflow-chart-body">
            <svg class="broker-cashflow-chart-svg" viewBox="0 0 640 92" role="img" aria-label="Evolução dos gastos por semana">
                <line x1="10" y1="{{ $series['baseline'] }}" x2="630" y2="{{ $series['baseline'] }}" class="broker-cashflow-chart-axis" />

                @foreach ($series['bars'] as $bar)
                    @php($center = $bar['x'] + ($bar['width'] / 2))
                    <g>
                        <title>{{ $bar['label'] }} {{ $bar['value'] }}</title>
                        @if ($bar['height'] > 0)
                            <rect
                                x="{{ $bar['x'] }}"
                                y="{{ $bar['y'] }}"
                                width="{{ $bar['width'] }}"
                                height="{{ $bar['height'] }}"
                                @class([
                                    'broker-cashflow-chart-bar',
                                    'broker-cashflow-chart-bar-event' => $bar['isEvent'],
                                    'broker-cashflow-chart-bar-today' => $bar['isToday'],
                                ])
                            />
                        @endif
                        @if ($bar['paidHeight'] > 0)
                            <rect
                                x="{{ $bar['x'] }}"
                                y="{{ $bar['paidY'] }}"
                                width="{{ $bar['width'] }}"
                                height="{{ $bar['paidHeight'] }}"
                                class="broker-cashflow-chart-bar-paid"
                            />
                        @endif
                        @if ($bar['value'] !== '')
                            <text x="{{ $center }}" y="{{ max(10, $bar['y'] - 4) }}" text-anchor="middle" class="broker-cashflow-chart-value">{{ $bar['value'] }}</text>
                        @endif
                        <text x="{{ $center }}" y="{{ $series['baseline'] + 14 }}" text-anchor="middle" @class(['broker-cashflow-chart-tick', 'broker-cashflow-chart-tick-event' => $bar['isEvent']])>{{ $bar['label'] }}</text>
                    </g>
                @endforeach
            </svg>
            <ul class="broker-cashflow-chart-legend">
                <li><span class="broker-cashflow-chart-swatch broker-cashflow-chart-swatch-total"></span> Saída da semana</li>
                <li><span class="broker-cashflow-chart-swatch broker-cashflow-chart-swatch-paid"></span> Já saiu</li>
            </ul>
            @if ($series['undated'] > 0)
                <p class="broker-cashflow-chart-note">
                    Fora do gráfico:
                    <x-money :cents="$series['undated']" :currency="$event->currency" />
                    sem data
                </p>
            @endif
        </div>
    @endif
</article>
