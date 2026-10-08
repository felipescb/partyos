@props([
    'timeline',
    'event',
])

@php
    /** @var \App\Support\PlanningTimeline $timeline */
    $ready = $timeline->ready && count($timeline->weeks) > 0;
    $progress = $ready ? (int) round(($timeline->progressRatio ?? 0) * 100) : 0;
@endphp

<article {{ $attributes->class(['broker-card', 'broker-timeline-panel']) }} role="listitem">
    <header class="broker-timeline-head">
        <div>
            <x-event-category-symbol category="quando" />
            <h2 class="broker-timeline-title">Timeline do planejamento</h2>
        </div>
        @if ($ready)
            <p class="broker-timeline-range">
                {{ $timeline->planningStartsOn?->translatedFormat('d M Y') }}
                →
                {{ $timeline->eventOn?->translatedFormat('d M Y') }}
            </p>
        @endif
    </header>

    @if (! $ready)
        <div class="broker-timeline-empty">
            <p>Defina o início do planejamento e a data da festa para ver as semanas e marcos.</p>
            @can('manageOperations', $event)
                <a href="{{ route('events.edit', $event) }}" wire:navigate class="broker-task-panel-link">Editar datas</a>
            @endcan
        </div>
    @else
        <div class="broker-timeline-progress" aria-hidden="true">
            <span class="broker-timeline-progress-fill" style="width: {{ $progress }}%"></span>
            <span class="broker-timeline-progress-now" style="left: {{ $progress }}%"></span>
        </div>

        <div class="broker-timeline-scroll" tabindex="0" aria-label="Semanas até a festa">
            <div class="broker-timeline-grid" role="list">
                @foreach ($timeline->weeks as $week)
                    <section
                        @class([
                            'broker-timeline-week',
                            'broker-timeline-week-current' => $week->isCurrent,
                            'broker-timeline-week-event' => $week->isEventWeek,
                            'broker-timeline-week-past' => $week->isPast,
                        ])
                        role="listitem"
                        aria-label="Semana {{ $week->index + 1 }}"
                    >
                        <header class="broker-timeline-week-head">
                            <span class="broker-timeline-week-index">S{{ $week->index + 1 }}</span>
                            <span class="broker-timeline-week-dates">{{ $week->shortLabel() }}</span>
                            @if ($week->isEventWeek)
                                <span class="broker-timeline-week-badge">Festa</span>
                            @elseif ($week->isCurrent)
                                <span class="broker-timeline-week-badge broker-timeline-week-badge-now">Agora</span>
                            @endif
                        </header>

                        <div class="broker-timeline-track" aria-hidden="true">
                            <span class="broker-timeline-track-line"></span>
                            <span @class(['broker-timeline-track-node', 'broker-timeline-track-node-active' => $week->isCurrent || $week->isEventWeek])></span>
                        </div>

                        <ul class="broker-timeline-milestones">
                            @forelse ($week->milestones as $milestone)
                                <li
                                    @class([
                                        'broker-timeline-milestone',
                                        'broker-timeline-milestone-auto' => $milestone->kind === 'auto',
                                        'broker-timeline-milestone-task' => $milestone->kind === 'task',
                                        'broker-timeline-milestone-done' => $milestone->isDone,
                                    ])
                                    title="{{ $milestone->title }}"
                                >
                                    <span class="broker-timeline-milestone-kind">
                                        {{ $milestone->kind === 'auto' ? 'Marco' : 'Tarefa' }}
                                    </span>
                                    <span class="broker-timeline-milestone-title">{{ $milestone->title }}</span>
                                    @if ($milestone->statusLabel)
                                        <span class="broker-timeline-milestone-meta">{{ $milestone->statusLabel }}</span>
                                    @endif
                                </li>
                            @empty
                                <li class="broker-timeline-milestone broker-timeline-milestone-empty" aria-hidden="true">—</li>
                            @endforelse
                        </ul>
                    </section>
                @endforeach
            </div>
        </div>
    @endif
</article>
