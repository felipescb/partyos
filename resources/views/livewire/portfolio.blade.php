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
        <a
            href="{{ route('events.show', $event) }}"
            wire:navigate
            class="broker-grid-item broker-card"
            role="listitem"
            data-grid-item="event"
            data-event-id="{{ $event->id }}"
        >
            <div class="broker-card-head">
                <span class="broker-card-symbol">{{ $event->type->label() }}</span>
                <span @class(['broker-card-status', 'broker-card-status-'.$statusTone])>{{ $event->status->label() }}</span>
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
        </a>
    @empty
        <div class="broker-grid-item broker-card broker-card-empty" role="listitem" data-grid-item="empty">
            <span class="broker-card-name">Nenhum evento ainda</span>
            <span class="broker-card-foot">Use o tile «+» para criar o primeiro.</span>
        </div>
    @endforelse
</div>
