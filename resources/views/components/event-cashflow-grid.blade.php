@props([
    'event',
    'preview',
])

<article
    x-data="{ open: @entangle('cashflowGridExpanded').live }"
    x-bind:class="{
        'broker-ticket-grid-panel-expanded': open,
        'broker-ticket-grid-panel-collapsed': ! open,
    }"
    {{ $attributes->class([
        'broker-card',
        'broker-card-module',
        'broker-ticket-grid-panel',
        'broker-cashflow-sample-panel',
    ]) }}
    role="listitem"
>
    <button
        type="button"
        class="broker-ticket-grid-toggle"
        x-on:click="open = ! open"
        x-bind:aria-expanded="open ? 'true' : 'false'"
        x-bind:aria-label="open ? 'Recolher fluxo' : 'Ampliar fluxo'"
    ></button>

    <div class="broker-ticket-grid-body-stack">
        <div class="broker-ticket-grid-collapsed-view" x-bind:aria-hidden="open ? 'true' : 'false'">
            <x-event-category-symbol category="financeiro" />
            <span class="broker-card-name">Fluxo</span>
            @if ($preview['count'] > 0)
                <span class="broker-card-quote"><x-money :cents="$preview['amount']" :currency="$event->currency" /></span>
                <span class="broker-card-foot broker-ticket-grid-collapsed-foot">
                    {{ $preview['count'] === 1 ? '1 saída à frente' : $preview['count'].' saídas à frente' }}
                </span>
            @else
                <span class="broker-card-quote broker-card-quote-muted">—</span>
                <span class="broker-card-foot">Nada à frente</span>
            @endif
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
                        <h2 class="broker-ticket-grid-title">Fluxo</h2>
                    </div>
                </header>

                <div class="broker-ticket-grid-expanded-meta">
                    <p class="broker-ticket-grid-metrics-inline">
                        @if ($preview['count'] > 0)
                            <strong>{{ $preview['count'] }}</strong> {{ $preview['count'] === 1 ? 'saída à frente' : 'saídas à frente' }}
                        @else
                            Nada à frente
                        @endif
                    </p>
                    @if ($preview['amount'] > 0)
                        <p class="broker-ticket-grid-profit-inline">
                            <x-money :cents="$preview['amount']" :currency="$event->currency" />
                            <span>ainda por sair</span>
                        </p>
                    @endif
                    <a href="{{ route('events.cashflow', $event) }}" wire:navigate class="broker-task-panel-link broker-ticket-grid-interactive">
                        Ver o fluxo todo
                    </a>
                </div>

                <div class="broker-cashflow-sample-scroll">
                    @if ($preview['days'] === [] && $preview['weeks'] === [])
                        <p class="broker-cashflow-sample-empty">Nenhum pagamento nos próximos dias ou semanas.</p>
                    @endif

                    @if ($preview['days'] !== [])
                        <section>
                            <h3 class="broker-cashflow-sample-heading">Próximos dias</h3>
                            <ul class="broker-cashflow-sample-list" role="list">
                                @foreach ($preview['days'] as $line)
                                    <li @class(['broker-cashflow-sample-row', 'broker-cost-cat-'.$line['slug']]) role="listitem">
                                        <span class="broker-cashflow-sample-when">{{ $line['code'] }}</span>
                                        <span class="broker-cashflow-sample-main">
                                            <span class="broker-cashflow-sample-title">{{ $line['label'] }}</span>
                                            <span class="broker-cashflow-sample-meta">{{ $line['when'] }} · {{ $line['state'] }}</span>
                                        </span>
                                        <span class="broker-cashflow-sample-amount">
                                            <x-money :cents="$line['amount']" :currency="$event->currency" />
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if ($preview['weeks'] !== [])
                        <section>
                            <h3 class="broker-cashflow-sample-heading">Próximas semanas</h3>
                            <ul class="broker-cashflow-sample-list" role="list">
                                @foreach ($preview['weeks'] as $line)
                                    <li @class(['broker-cashflow-sample-row', 'broker-cost-cat-'.$line['slug']]) role="listitem">
                                        <span class="broker-cashflow-sample-when">{{ $line['code'] }}</span>
                                        <span class="broker-cashflow-sample-main">
                                            <span class="broker-cashflow-sample-title">{{ $line['label'] }}</span>
                                            <span class="broker-cashflow-sample-meta">{{ $line['when'] }} · {{ $line['state'] }}</span>
                                        </span>
                                        <span class="broker-cashflow-sample-amount">
                                            <x-money :cents="$line['amount']" :currency="$event->currency" />
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                </div>
            </div>
        </div>
    </div>
</article>
