@props([
    'flow',
    'canEdit' => false,
])

<article {{ $attributes->class(['broker-card', 'broker-schedule-flow']) }} role="listitem">
    <header class="broker-cashflow-map-head">
        <div>
            <x-event-category-symbol category="quando" label="Timeflow" />
            <h2 class="broker-cashflow-map-title">Timeflow</h2>
        </div>
        <div class="broker-schedule-head-end">
            @if ($flow['range'])
                <p class="broker-cashflow-map-range">{{ $flow['range'] }}</p>
            @endif
            @if ($canEdit)
                <button type="button" class="broker-card-icon-btn" wire:click="create" aria-label="Novo horário" title="Novo horário">
                    <flux:icon.plus variant="mini" class="size-4" />
                </button>
            @endif
        </div>
    </header>

    @if ($flow['days'] === [] && $flow['undated'] === [])
        <div class="broker-schedule-empty">
            <p>O dia ainda não tem horários.</p>
            <p>Montagem, soundcheck, abertura e encerramento entram nesta linha.</p>
        </div>
    @else
        <div class="broker-schedule-scroll">
            @if ($flow['days'] !== [])
                @php
                    $sideIndex = 0;
                @endphp
                <ol class="broker-schedule-rail">
                    @foreach ($flow['days'] as $day)
                        <li class="broker-schedule-day">
                            <span class="broker-schedule-day-label">
                                <span class="broker-schedule-day-kicker">{{ $day['kicker'] }}</span>
                                <span>{{ $day['label'] }}</span>
                            </span>
                        </li>
                        @foreach ($day['beats'] as $beat)
                            @php
                                $side = $sideIndex % 2 === 0 ? 'left' : 'right';
                                $sideIndex++;
                            @endphp
                            <li @class(['broker-schedule-stop', 'broker-schedule-stop-'.$side])>
                                @php($tag = $canEdit ? 'button' : 'div')
                                <{{ $tag }}
                                    @class([
                                        'broker-schedule-beat',
                                        'broker-schedule-beat-'.$beat['phase'],
                                        'broker-schedule-beat-current' => $beat['current'],
                                    ])
                                    @if ($canEdit) type="button" wire:click="edit({{ $beat['id'] }})" @endif
                                    @if ($canEdit) aria-label="Editar {{ $beat['title'] }}" @endif
                                >
                                    <span class="broker-schedule-copy">
                                        <span class="broker-schedule-body">
                                            <span class="broker-schedule-title">{{ $beat['title'] }}</span>
                                            <span class="broker-schedule-meta">
                                                @if ($beat['duration'])
                                                    <span>{{ $beat['duration'] }}</span>
                                                @endif
                                                @if ($beat['place'])
                                                    <span>{{ $beat['place'] }}</span>
                                                @endif
                                                @if ($beat['current'])
                                                    <span class="broker-schedule-now">agora</span>
                                                @endif
                                            </span>
                                        </span>
                                        <span class="broker-schedule-when">
                                            <span class="broker-schedule-clock">{{ $beat['clock'] }}</span>
                                            @if ($beat['code'])
                                                <span class="broker-schedule-code">{{ $beat['code'] }}</span>
                                            @endif
                                        </span>
                                    </span>
                                    <span class="broker-schedule-node" aria-hidden="true"></span>
                                </{{ $tag }}>
                            </li>
                        @endforeach
                    @endforeach
                </ol>
            @endif

            @if ($flow['undated'] !== [])
                <div class="broker-schedule-undated">
                    <p class="broker-schedule-day-kicker">Sem hora</p>
                    <ul class="broker-schedule-open">
                        @foreach ($flow['undated'] as $item)
                            <li>
                                @php($tag = $canEdit ? 'button' : 'div')
                                <{{ $tag }}
                                    class="broker-schedule-open-row"
                                    @if ($canEdit) type="button" wire:click="edit({{ $item['id'] }})" @endif
                                >
                                    <span class="broker-schedule-title">{{ $item['title'] }}</span>
                                    @if ($item['place'])
                                        <span class="broker-schedule-meta">{{ $item['place'] }}</span>
                                    @endif
                                </{{ $tag }}>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif
</article>
