@props([
    'event',
    'tiers',
    'canEdit' => false,
])

<article {{ $attributes->class(['broker-card', 'broker-ticket-panel']) }} role="listitem">
    <header class="broker-ticket-panel-head">
        <div>
            <x-event-category-symbol category="financeiro" />
            <h2 class="broker-ticket-panel-title">Lotes de ingresso</h2>
        </div>
        @if ($canEdit)
            <button type="button" class="broker-card-icon-btn" wire:click="create" aria-label="Novo ingresso" title="Novo ingresso">
                <flux:icon.plus variant="mini" class="size-4" />
            </button>
        @endif
    </header>

    <div class="broker-ticket-columns" aria-hidden="true">
        <span>Lote</span>
        <span>Preço</span>
        <span>Vendas</span>
        <span>Receita</span>
        <span>Restam</span>
        <span>Janela</span>
    </div>

    <div class="broker-ticket-list" role="list" aria-label="Lotes de ingresso">
        @forelse ($tiers as $tier)
            @php($soldOut = $tier->remaining() <= 0 && $tier->quantity > 0)
            <div
                @class(['broker-ticket-row', 'broker-ticket-row-muted' => $soldOut])
                role="listitem"
                wire:key="ticket-tier-{{ $tier->id }}"
            >
                @if ($canEdit)
                    <button type="button" class="broker-ticket-row-hit" wire:click="edit({{ $tier->id }})" aria-label="Editar {{ $tier->name }}"></button>
                @endif
                <div class="broker-ticket-cell broker-ticket-cell-name">
                    <span class="broker-ticket-name">{{ $tier->name }}</span>
                    @if ($tier->quantity > 0)
                        <span class="broker-ticket-sub">{{ $tier->quantity }} no lote</span>
                    @endif
                    @if ($tier->payout_basis_points !== null && $tier->payout_basis_points !== 10000)
                        <span class="broker-ticket-sub">repasse {{ \App\Domain\Finance\Money::formatPercent($tier->payout_basis_points) }}</span>
                    @endif
                </div>
                <div class="broker-ticket-cell broker-ticket-cell-price">
                    <x-money :cents="$tier->price" :currency="$event->currency" />
                </div>
                <div class="broker-ticket-cell broker-ticket-cell-sales">
                    <span class="broker-ticket-metric">{{ $tier->sold_quantity }}<span class="broker-ticket-metric-muted">/{{ $tier->goal }}</span></span>
                    <span class="broker-ticket-sub">meta</span>
                </div>
                <div class="broker-ticket-cell broker-ticket-cell-revenue">
                    <x-money :cents="$tier->revenue()" :currency="$event->currency" />
                </div>
                <div class="broker-ticket-cell broker-ticket-cell-remaining">
                    {{ max(0, $tier->remaining()) }}
                </div>
                <div class="broker-ticket-cell broker-ticket-cell-window">
                    @if ($tier->starts_at || $tier->ends_at)
                        <span class="broker-ticket-sub">
                            {{ $tier->starts_at?->format('d/m H:i') ?? '—' }}
                            @if ($tier->ends_at)
                                → {{ $tier->ends_at->format('d/m H:i') }}
                            @endif
                        </span>
                    @else
                        <span class="broker-ticket-sub">Sem janela</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="broker-ticket-empty">
                <p>Nenhum lote ainda.</p>
                <p class="broker-ticket-empty-hint">Pré-venda, primeiro lote, porta — a meta alimenta o empate no quadro.</p>
                @if ($canEdit)
                    <button type="button" class="broker-task-panel-link" wire:click="create">Criar lote</button>
                @endif
            </div>
        @endforelse
    </div>
</article>
