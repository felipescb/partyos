<div class="broker-close-page">
    <header class="broker-tickets-head">
        <div class="broker-tickets-head-main">
            <a href="{{ route('events.show', $event) }}" wire:navigate class="broker-task-notebook-back">← Quadro</a>
            <div>
                <x-event-category-symbol category="financeiro" />
                <h1 class="broker-cashflow-title">Fechamento</h1>
                <p class="broker-cashflow-sub">{{ $event->name }} · o que você planejou contra o que aconteceu</p>
            </div>
        </div>
        <div class="broker-tickets-head-actions">
            <span @class([
                'broker-card-status',
                'broker-card-status-up' => $event->status === \App\Enums\EventStatus::Finished,
            ])>{{ $event->status->label() }}</span>
            @if ($canEdit && $event->status !== \App\Enums\EventStatus::Finished)
                <button
                    type="button"
                    class="broker-card-icon-btn"
                    wire:click="finish"
                    aria-label="Marcar como finalizado"
                    title="Marcar como finalizado"
                >
                    <flux:icon.check variant="mini" class="size-4" />
                </button>
            @endif
        </div>
    </header>

    <div class="broker-grid broker-grid-event broker-grid-close" role="list" aria-label="Quadro do fechamento">
        <article class="broker-grid-item broker-card broker-card-module" role="listitem">
            <x-event-category-symbol category="leitura" label="Previsto" />
            <span class="broker-card-name">Previsto</span>
            <span class="broker-card-quote"><x-money :cents="$statement->projectedProfit" :currency="$event->currency" /></span>
            <span class="broker-card-foot">Resultado no plano</span>
        </article>
        <article class="broker-grid-item broker-card broker-card-module" role="listitem">
            <x-event-category-symbol :category="$statement->currentResult < 0 ? 'atencao' : 'ao-vivo'" label="Real" />
            <span class="broker-card-name">Real</span>
            <span class="broker-card-quote"><x-money :cents="$statement->currentResult" :currency="$event->currency" /></span>
            <span class="broker-card-foot">Líquido menos o comprometido</span>
        </article>
        <article class="broker-grid-item broker-card broker-card-module" role="listitem">
            <x-event-category-symbol category="casa" label="Público" />
            <span class="broker-card-name">Público</span>
            <span class="broker-card-quote">{{ $statement->ticketsSold }}</span>
            <span class="broker-card-foot">de {{ $statement->ticketsGoal }} na meta · {{ $statement->confirmedGuestHeads }} convidados</span>
        </article>
        <article class="broker-grid-item broker-card broker-card-module" role="listitem">
            <x-event-category-symbol category="financeiro" label="Ticket" />
            <span class="broker-card-name">Ticket médio</span>
            @if ($statement->averageTicket)
                <span class="broker-card-quote"><x-money :cents="$statement->averageTicket" :currency="$event->currency" /></span>
                <span class="broker-card-foot">Média da meta</span>
            @else
                <span class="broker-card-quote broker-card-quote-muted">—</span>
                <span class="broker-card-foot">Defina a meta dos lotes</span>
            @endif
        </article>

        <x-event-close-sheet :event="$event" :statement="$statement" :complete="$complete" class="broker-grid-item" />
        <x-event-close-log :audits="$audits" class="broker-grid-item" />
    </div>
</div>
