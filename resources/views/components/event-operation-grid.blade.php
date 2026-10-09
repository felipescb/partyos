@props([
    'event',
    'flow',
    'total' => null,
])

@php
    $count = $total ?? collect($flow['days'])->sum(fn (array $day): int => count($day['beats'])) + count($flow['undated']);
@endphp

<article {{ $attributes->class(['broker-card', 'broker-operation-panel']) }} role="listitem">
    <header class="broker-task-panel-head">
        <div>
            <x-event-category-symbol category="operacao" />
            <h2 class="broker-task-panel-title">Horários</h2>
        </div>
        <a href="{{ route('events.schedule', $event) }}" wire:navigate class="broker-task-panel-link">
            Ver todas{{ $count > 0 ? ' · '.$count : '' }}
        </a>
    </header>

    @if ($flow['days'] === [] && $flow['undated'] === [])
        <div class="broker-operation-empty">
            <p>O dia ainda não tem horários.</p>
            <a href="{{ route('events.schedule', $event) }}" wire:navigate class="broker-task-panel-link">Abrir o timeflow</a>
        </div>
    @else
        <div class="broker-operation-scroll">
            @if ($flow['days'] !== [])
                @php
                    $sideIndex = 0;
                @endphp
                <ol class="broker-operation-rail">
                    @foreach ($flow['days'] as $day)
                        <li class="broker-operation-day">
                            <span class="broker-operation-day-label">
                                <span class="broker-operation-kicker">{{ $day['kicker'] }}</span>
                                <span>{{ $day['label'] }}</span>
                            </span>
                        </li>
                        @foreach ($day['beats'] as $beat)
                            @php
                                $side = $sideIndex % 2 === 0 ? 'left' : 'right';
                                $sideIndex++;
                            @endphp
                            <li @class(['broker-operation-stop', 'broker-operation-stop-'.$side])>
                                <a
                                    href="{{ route('events.schedule', $event) }}"
                                    wire:navigate
                                    @class([
                                        'broker-operation-beat',
                                        'broker-operation-beat-'.$beat['phase'],
                                        'broker-operation-beat-current' => $beat['current'],
                                    ])
                                >
                                    <span class="broker-operation-copy">
                                        <span class="broker-operation-title">{{ $beat['title'] }}</span>
                                        <span class="broker-operation-when">
                                            <span class="broker-operation-clock">{{ $beat['clock'] }}</span>
                                            @if ($beat['code'])
                                                <span class="broker-operation-code">{{ $beat['code'] }}</span>
                                            @endif
                                        </span>
                                    </span>
                                    <span class="broker-operation-node" aria-hidden="true"></span>
                                </a>
                            </li>
                        @endforeach
                    @endforeach
                </ol>
            @endif

            @if ($flow['undated'] !== [])
                <p class="broker-operation-open">{{ count($flow['undated']) }} sem hora</p>
            @endif
        </div>
    @endif
</article>
