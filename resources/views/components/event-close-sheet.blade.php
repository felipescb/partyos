@props([
    'event',
    'statement',
    'complete' => false,
])

@php
    $print = function (int $cents, bool $deduction = false) use ($event): string {
        $formatted = \App\Domain\Finance\Money::format($cents, $event->currency);

        if ($deduction && $cents > 0) {
            return '− '.$formatted;
        }

        return $formatted;
    };

    $slips = [
        [
            'kicker' => 'Extrato previsto',
            'gross' => $statement->expectedGross,
            'fees' => $statement->expectedFees,
            'distributions' => $statement->expectedDistributions,
            'net' => $statement->expectedNet,
            'costs' => $statement->committedCosts,
            'paid' => null,
            'result' => $statement->projectedProfit,
            'audience' => $statement->ticketsGoal.' na meta',
            'ticket' => $statement->averageTicket,
        ],
        [
            'kicker' => 'Extrato real',
            'gross' => $statement->actualGross,
            'fees' => $statement->actualFees,
            'distributions' => $statement->actualDistributions,
            'net' => $statement->actualNet,
            'costs' => $statement->committedCosts,
            'paid' => $statement->paidCosts,
            'result' => $statement->currentResult,
            'audience' => $statement->ticketsSold.' vendidos · '.$statement->confirmedGuestHeads.' convidados',
            'ticket' => null,
        ],
    ];

    $gap = $statement->currentResult - $statement->projectedProfit;
@endphp

<article {{ $attributes->class(['broker-card', 'broker-close-sheet']) }} role="listitem">
    <header class="broker-cashflow-map-head">
        <div>
            <x-event-category-symbol category="financeiro" />
            <h2 class="broker-cashflow-map-title">Previsto e real</h2>
            <p class="broker-cashflow-sub">Receita líquida já desconta taxa e divisão.</p>
        </div>
        <div class="broker-cashflow-map-switch" role="group" aria-label="Formato do fechamento">
            <button type="button" wire:click="$set('complete', false)" aria-pressed="{{ $complete ? 'false' : 'true' }}">Extrato</button>
            <button type="button" wire:click="$set('complete', true)" aria-pressed="{{ $complete ? 'true' : 'false' }}">Completo</button>
        </div>
    </header>

    @if ($complete)
        <div class="broker-cost-scroll" tabindex="0" aria-label="Previsto contra o realizado">
            <div class="broker-cost-columns broker-close-columns" role="row">
                <span class="broker-task-notebook-sort broker-cost-cell broker-cost-cell-item">Linha</span>
                <span class="broker-task-notebook-sort broker-cost-cell broker-close-money">Previsto</span>
                <span class="broker-task-notebook-sort broker-cost-cell broker-close-money">Real</span>
                <span class="broker-task-notebook-sort broker-cost-cell broker-close-money">Diferença</span>
            </div>

            @foreach ([
                ['Receita bruta', $statement->expectedGross, $statement->actualGross, false],
                ['Taxas', $statement->expectedFees, $statement->actualFees, false],
                ['Divisão', $statement->expectedDistributions, $statement->actualDistributions, false],
                ['Receita líquida', $statement->expectedNet, $statement->actualNet, true],
                ['Custos', $statement->committedCosts, $statement->paidCosts, false],
                ['Resultado', $statement->projectedProfit, $statement->currentResult, true],
            ] as [$label, $planned, $actual, $emphasis])
                @php
                    $delta = $actual - $planned;
                @endphp
                <div @class(['broker-cost-row', 'broker-close-row', 'broker-close-row-total' => $emphasis]) role="row">
                    <div class="broker-cost-cell broker-cost-cell-item" role="cell">
                        <span class="broker-task-notebook-read">{{ $label }}</span>
                    </div>
                    <div class="broker-cost-cell broker-close-money" role="cell">
                        <span class="broker-task-notebook-read"><x-money :cents="$planned" :currency="$event->currency" /></span>
                    </div>
                    <div class="broker-cost-cell broker-close-money" role="cell">
                        <span class="broker-task-notebook-read"><x-money :cents="$actual" :currency="$event->currency" /></span>
                    </div>
                    <div class="broker-cost-cell broker-close-money" role="cell">
                        <span @class([
                            'broker-task-notebook-read',
                            'broker-close-delta-up' => $delta > 0,
                            'broker-close-delta-down' => $delta < 0,
                        ])><x-money :cents="$delta" :currency="$event->currency" /></span>
                    </div>
                </div>
            @endforeach

            <div class="broker-cost-row broker-close-row" role="row">
                <div class="broker-cost-cell broker-cost-cell-item" role="cell">
                    <span class="broker-task-notebook-read">Público</span>
                </div>
                <div class="broker-cost-cell broker-close-money" role="cell">
                    <span class="broker-task-notebook-read">{{ $statement->ticketsGoal }} na meta</span>
                </div>
                <div class="broker-cost-cell broker-close-money" role="cell">
                    <span class="broker-task-notebook-read">{{ $statement->ticketsSold }} vendidos · {{ $statement->confirmedGuestHeads }} convidados</span>
                </div>
                <div class="broker-cost-cell broker-close-money" role="cell">
                    @php
                        $heads = $statement->ticketsSold - $statement->ticketsGoal;
                    @endphp
                    <span @class([
                        'broker-task-notebook-read',
                        'broker-close-delta-up' => $heads > 0,
                        'broker-close-delta-down' => $heads < 0,
                    ])>{{ $heads > 0 ? '+' : '' }}{{ $heads }}</span>
                </div>
            </div>

            <div class="broker-cost-row broker-close-row" role="row">
                <div class="broker-cost-cell broker-cost-cell-item" role="cell">
                    <span class="broker-task-notebook-read">Ticket médio da meta</span>
                </div>
                <div class="broker-cost-cell broker-close-money" role="cell">
                    <span class="broker-task-notebook-read">
                        @if ($statement->averageTicket)
                            <x-money :cents="$statement->averageTicket" :currency="$event->currency" />
                        @else
                            Defina a meta dos lotes.
                        @endif
                    </span>
                </div>
                <div class="broker-cost-cell broker-close-money" role="cell">
                    <span class="broker-task-notebook-read">—</span>
                </div>
                <div class="broker-cost-cell broker-close-money" role="cell">
                    <span class="broker-task-notebook-read">—</span>
                </div>
            </div>
        </div>
    @else
    <div class="broker-close-desk">
    <div class="broker-close-slips">
        @foreach ($slips as $slip)
            <section class="broker-close-slip" aria-label="{{ $slip['kicker'] }}">
                <p class="broker-close-slip-brand">PartyOS</p>
                <p class="broker-close-slip-kicker">{{ $slip['kicker'] }}</p>
                <p class="broker-close-slip-event">{{ $event->name }}</p>
                <p class="broker-close-slip-meta">{{ $event->whenLabel() }}</p>

                <div class="broker-close-slip-rule"></div>

                <p class="broker-close-slip-line">
                    <span>Receita bruta</span>
                    <span>{{ $print($slip['gross']) }}</span>
                </p>
                <p class="broker-close-slip-line">
                    <span>Taxas</span>
                    <span>{{ $print($slip['fees'], true) }}</span>
                </p>
                <p class="broker-close-slip-line">
                    <span>Divisão</span>
                    <span>{{ $print($slip['distributions'], true) }}</span>
                </p>

                <div class="broker-close-slip-rule"></div>

                <p class="broker-close-slip-line broker-close-slip-sub">
                    <span>Receita líquida</span>
                    <span>{{ $print($slip['net']) }}</span>
                </p>
                <p class="broker-close-slip-line">
                    <span>Custos</span>
                    <span>{{ $print($slip['costs'], true) }}</span>
                </p>
                @if ($slip['paid'] !== null)
                    <p class="broker-close-slip-memo">já pagos {{ $print($slip['paid']) }}</p>
                @endif

                <div class="broker-close-slip-rule broker-close-slip-rule-double"></div>

                <p class="broker-close-slip-line broker-close-slip-result">
                    <span>Resultado</span>
                    <span @class([
                        'broker-close-delta-up' => $slip['result'] > 0,
                        'broker-close-delta-down' => $slip['result'] < 0,
                    ])>{{ $print($slip['result']) }}</span>
                </p>

                <div class="broker-close-slip-rule"></div>

                <p class="broker-close-slip-foot">Público · {{ $slip['audience'] }}</p>
                @if ($slip['ticket'])
                    <p class="broker-close-slip-foot">Ticket médio da meta · {{ $print($slip['ticket']) }}</p>
                @elseif ($slip['ticket'] === null && $slip['kicker'] === 'Extrato previsto')
                    <p class="broker-close-slip-foot">Ticket médio da meta · defina a meta dos lotes</p>
                @endif
            </section>
        @endforeach
    </div>

    <p class="broker-close-slip-note">
        Diferença do resultado
        <span @class([
            'broker-close-delta-up' => $gap > 0,
            'broker-close-delta-down' => $gap < 0,
        ])>{{ $print($gap) }}</span>
    </p>
    </div>
    @endif
</article>
