<div>
<div class="broker-grid" role="list" data-broker-grid>
    <a href="{{ route('events.create') }}" wire:navigate class="broker-grid-item broker-card broker-card-new" role="listitem" data-grid-item="new">
        <span class="broker-card-symbol">+</span>
        <span class="broker-card-name">Novo evento</span>
        <span class="broker-card-foot">Abrir formulário</span>
    </a>

    @forelse ($rows as $row)
        @php($event = $row['event'])
        @php($statement = $row['statement'])
        @php($statusTone = match ($event->status) {
            \App\Enums\EventStatus::Live, \App\Enums\EventStatus::OnSale => 'up',
            \App\Enums\EventStatus::Finished, \App\Enums\EventStatus::Cancelled => 'down',
            default => 'flat',
        })
        <article
            class="broker-grid-item broker-card broker-card-has-actions"
            role="listitem"
            data-grid-item="event"
            data-event-id="{{ $event->id }}"
        >
            <a href="{{ route('events.show', $event) }}" wire:navigate class="broker-card-open" aria-label="Abrir {{ $event->name }}"></a>
            <div class="broker-card-head">
                <x-event-category-symbol category="evento" :label="$event->type->label()" />
                <div class="broker-card-actions">
                    <span @class(['broker-card-status', 'broker-card-status-'.$statusTone])>{{ $event->status->label() }}</span>
                    @can('manageOperations', $event)
                        <button
                            type="button"
                            class="broker-card-icon-btn"
                            wire:click="openDuplicate({{ $event->id }})"
                            aria-label="Duplicar {{ $event->name }}"
                        >
                            <flux:icon.document-duplicate variant="mini" class="size-4" />
                        </button>
                    @endcan
                </div>
            </div>
            <div class="broker-card-body">
                <span class="broker-card-name">{{ $event->name }}</span>
                <span class="broker-card-quote">
                    <x-money :cents="$statement->projectedProfit" :currency="$event->currency" />
                </span>
            </div>
            <div class="broker-card-metrics">
                <div class="broker-card-metric">
                    <span class="broker-card-metric-label">Data</span>
                    <span class="broker-card-metric-value">{{ $event->whenLabel() }}</span>
                </div>
                <div class="broker-card-metric">
                    <span class="broker-card-metric-label">Local</span>
                    <span class="broker-card-metric-value">{{ $event->city ?: ($event->venue_name ?: '—') }}</span>
                </div>
            </div>
        </article>
    @empty
        <div class="broker-grid-item broker-card broker-card-empty" role="listitem" data-grid-item="empty">
            <span class="broker-card-name">Nenhum evento ainda</span>
            <span class="broker-card-foot">Use o tile «+» para criar o primeiro.</span>
        </div>
    @endforelse
</div>

@include('partials.event-duplicate-modal')
</div>
