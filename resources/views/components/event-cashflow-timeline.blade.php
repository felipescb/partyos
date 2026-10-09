@props([
    'columns',
    'event',
    'map' => 'time',
    'grain' => 'week',
])

@php
    $dated = collect($columns)->filter(fn (array $column): bool => ! $column['isUndated']);
    $first = $dated->first();
    $last = $dated->last();
@endphp

<article {{ $attributes->class(['broker-card', 'broker-cashflow-map']) }} role="listitem">
    <header class="broker-cashflow-map-head">
        <div>
            <x-event-category-symbol category="quando" />
            <h2 class="broker-cashflow-map-title">Mapa do fluxo</h2>
        </div>
        <x-event-cashflow-modes :map="$map" :grain="$grain" />
        @if ($map === 'category' && count($columns) > 0)
            <p class="broker-cashflow-map-range">Agrupado por categoria</p>
        @elseif ($first)
            <p class="broker-cashflow-map-range">{{ $first['label'] }} → {{ $last['label'] }}</p>
        @endif
    </header>

    @if (count($columns) === 0)
        <div class="broker-cashflow-map-empty">
            <p>Nada para pagar nessa janela.</p>
            <p>Lance custos com vencimento ou pagamentos agendados para ver o mapa.</p>
        </div>
    @else
        <div class="broker-cashflow-scroll" tabindex="0" aria-label="Mapa das saídas">
            <div class="broker-cashflow-track" role="list">
                @foreach ($columns as $column)
                    <section
                        @class([
                            'broker-cashflow-day',
                            'broker-cashflow-day-today' => $column['isToday'],
                            'broker-cashflow-day-event' => $column['isEvent'],
                            'broker-cashflow-day-past' => $column['isPast'],
                            'broker-cashflow-day-undated' => $column['isUndated'],
                            'broker-cashflow-day-category' => filled($column['slug'] ?? null),
                            'broker-cost-cat-'.($column['slug'] ?? 'none') => filled($column['slug'] ?? null),
                        ])
                        role="listitem"
                        aria-label="{{ $column['label'] }}"
                    >
                        <header class="broker-cashflow-day-head">
                            <span class="broker-cashflow-day-date">{{ $column['label'] }}</span>
                            <span class="broker-cashflow-day-week">{{ $column['weekday'] }}</span>
                            @if ($column['total'] > 0)
                                <span class="broker-cashflow-day-total">
                                    <x-money :cents="$column['total']" :currency="$event->currency" />
                                </span>
                            @endif
                            @if ($column['isEvent'])
                                <span class="broker-cashflow-day-badge">Festa</span>
                            @elseif ($column['isToday'])
                                <span class="broker-cashflow-day-badge broker-cashflow-day-badge-now">Hoje</span>
                            @endif
                        </header>

                        <div class="broker-cashflow-rail" aria-hidden="true">
                            <span class="broker-cashflow-rail-line"></span>
                            <span @class(['broker-cashflow-node', 'broker-cashflow-node-active' => $column['isToday'] || $column['isEvent']])></span>
                        </div>

                        <ul class="broker-cashflow-events">
                            @forelse ($column['events'] as $movement)
                                <li @class(['broker-cashflow-event', 'broker-cashflow-event-'.$movement['tone'], 'broker-cost-cat-'.($movement['categorySlug'] ?? 'none')])>
                                    <span class="broker-cashflow-event-kind">{{ $movement['state'] }}</span>
                                    <span class="broker-cashflow-event-title">{{ $movement['label'] }}</span>
                                    <span class="broker-cashflow-event-amount">
                                        <x-money :cents="$movement['amount']" :currency="$event->currency" />
                                    </span>
                                    <span class="broker-cashflow-event-meta">
                                        @if ($movement['date'])
                                            {{ \Illuminate\Support\Carbon::parse($movement['date'])->translatedFormat('d M') }}
                                            ·
                                        @endif
                                        {{ $movement['detail'] }}
                                        @if ($movement['category'])
                                            · {{ $movement['category'] }}
                                        @endif
                                    </span>
                                </li>
                            @empty
                                <li class="broker-cashflow-event broker-cashflow-event-empty" aria-hidden="true">—</li>
                            @endforelse
                        </ul>
                    </section>
                @endforeach
            </div>
        </div>
    @endif
</article>
