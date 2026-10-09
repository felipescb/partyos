@php
    $windows = [
        ['value' => 'today', 'label' => 'Hoje', 'tone' => 'atencao'],
        ['value' => '7', 'label' => '7 dias', 'tone' => 'operacao'],
        ['value' => '30', 'label' => '30 dias', 'tone' => 'equipe'],
        ['value' => 'all', 'label' => 'Tudo', 'tone' => 'financeiro'],
        ['value' => 'nodate', 'label' => 'Sem data', 'tone' => 'leitura'],
    ];
@endphp

<div class="broker-cashflow-page">
    <header class="broker-cashflow-head">
        <a href="{{ route('events.show', $event) }}" wire:navigate class="broker-task-notebook-back">← Quadro</a>
        <div>
            <x-event-category-symbol category="quando" />
            <h1 class="broker-cashflow-title">Fluxo</h1>
            <p class="broker-cashflow-sub">{{ $event->name }} · quando o dinheiro sai</p>
        </div>
    </header>

    <div class="broker-grid broker-grid-event broker-grid-cashflow" role="list" aria-label="Quadro do fluxo">
        @foreach ($windows as $card)
            @php($summary = $summaries[$card['value']])
            <div class="broker-grid-item" role="listitem">
            <button
                type="button"
                @class([
                    'broker-card broker-card-module broker-cashflow-card',
                    'broker-cashflow-card-current' => $window === $card['value'],
                ])
                wire:click="$set('window', '{{ $card['value'] }}')"
                aria-pressed="{{ $window === $card['value'] ? 'true' : 'false' }}"
            >
                <x-event-category-symbol :category="$card['tone']" :label="$card['label']" />
                <span class="broker-card-name">{{ $card['label'] }}</span>
                <span class="broker-card-quote"><x-money :cents="$summary['amount']" :currency="$event->currency" /></span>
                <span class="broker-card-foot">{{ $summary['count'] }} {{ $summary['count'] === 1 ? 'saída' : 'saídas' }}</span>
            </button>
            </div>
        @endforeach

        <x-event-cashflow-chart
            :series="$series"
            :event="$event"
            class="broker-grid-item"
        />

        <x-event-cashflow-timeline
            :columns="$columns"
            :event="$event"
            :map="$map"
            :grain="$grain"
            class="broker-grid-item broker-cashflow-map"
        />
    </div>
</div>
