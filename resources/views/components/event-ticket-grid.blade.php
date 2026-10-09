@props([
    'event',
    'tiers',
    'sold' => 0,
    'available' => 0,
    'projectedProfit' => 0,
    'expanded' => false,
    'canManage' => false,
])

<article
    x-data="{ open: @entangle('ticketsGridExpanded').live }"
    x-bind:class="{
        'broker-ticket-grid-panel-expanded': open,
        'broker-ticket-grid-panel-collapsed': ! open,
    }"
    {{ $attributes->class([
        'broker-card',
        'broker-card-module',
        'broker-ticket-grid-panel',
    ]) }}
    role="listitem"
>
    <button
        type="button"
        class="broker-ticket-grid-toggle"
        x-on:click="open = ! open"
        x-bind:aria-expanded="open ? 'true' : 'false'"
        x-bind:aria-label="open ? 'Recolher ingressos' : 'Ampliar ingressos'"
    ></button>

    <div class="broker-ticket-grid-body-stack">
        <div class="broker-ticket-grid-collapsed-view" x-bind:aria-hidden="open ? 'true' : 'false'">
            <x-event-category-symbol category="financeiro" />
            <span class="broker-card-name">Ingressos</span>
            <span class="broker-card-quote">
                {{ $sold }}<span class="broker-card-quote-muted">/{{ $available }}</span>
            </span>
            <span class="broker-card-foot broker-ticket-grid-collapsed-foot">
                <x-money :cents="$projectedProfit" :currency="$event->currency" /> · lucro projetado
            </span>
            <span class="broker-ticket-grid-chevron" aria-hidden="true">
                <flux:icon.chevron-down
                    variant="mini"
                    class="size-4 broker-ticket-grid-chevron-icon"
                    x-bind:class="{ 'is-open': open }"
                />
            </span>
        </div>

        <div class="broker-ticket-grid-expandable" x-bind:class="{ 'is-open': open }">
            <div class="broker-ticket-grid-expandable-inner">
                <header class="broker-ticket-grid-head">
                    <div>
                        <x-event-category-symbol category="financeiro" />
                        <h2 class="broker-ticket-grid-title">Ingressos</h2>
                    </div>
                    @if ($canManage)
                        <div class="broker-ticket-grid-actions broker-ticket-grid-interactive">
                            <a
                                href="{{ route('events.tickets', $event) }}"
                                wire:navigate
                                class="broker-card-icon-btn"
                                aria-label="Importar vendidos"
                                title="Importar vendidos"
                            >
                                <flux:icon.arrow-up-tray variant="mini" class="size-4" />
                            </a>
                            <a
                                href="{{ route('events.tickets', $event) }}"
                                wire:navigate
                                class="broker-card-icon-btn"
                                aria-label="Novo ingresso"
                                title="Novo ingresso"
                            >
                                <flux:icon.plus variant="mini" class="size-4" />
                            </a>
                        </div>
                    @endif
                </header>

                <div class="broker-ticket-grid-expanded-meta">
                    <p class="broker-ticket-grid-metrics-inline">
                        <span><strong>{{ $sold }}</strong> vendidos</span>
                        <span aria-hidden="true">·</span>
                        <span><strong>{{ $available }}</strong> disponíveis</span>
                    </p>
                    <p class="broker-ticket-grid-profit-inline">
                        <x-money :cents="$projectedProfit" :currency="$event->currency" />
                        <span>lucro projetado</span>
                    </p>
                    <a href="{{ route('events.tickets', $event) }}" wire:navigate class="broker-task-panel-link broker-ticket-grid-interactive">
                        Ir para o controle de ingressos
                    </a>
                </div>

                <div class="broker-ticket-grid-detail">
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
                            <a
                                href="{{ route('events.tickets', $event) }}"
                                wire:navigate
                                @class(['broker-ticket-row', 'broker-ticket-row-link', 'broker-ticket-grid-interactive', 'broker-ticket-row-muted' => $soldOut])
                                role="listitem"
                            >
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
                            </a>
                        @empty
                            <div class="broker-ticket-empty">
                                <p>Nenhum lote ainda.</p>
                                <a href="{{ route('events.tickets', $event) }}" wire:navigate class="broker-task-panel-link broker-ticket-grid-interactive">Abrir controle de ingressos</a>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</article>
