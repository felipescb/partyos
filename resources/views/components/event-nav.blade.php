@props(['event'])

@php
    $items = [
        ['events.show', 'Quadro', 'Evento', null],
        ['events.costs', 'Custos', 'Dinheiro', 'viewFinance'],
        ['events.tickets', 'Ingressos', 'Dinheiro', 'viewFinance'],
        ['events.revenues', 'Receitas', 'Dinheiro', 'viewFinance'],
        ['events.cashflow', 'Fluxo', 'Dinheiro', 'viewFinance'],
        ['events.scenarios', 'Cenários', 'Dinheiro', 'viewFinance'],
        ['events.distribution', 'Divisão', 'Dinheiro', 'viewFinance'],
        ['events.close', 'Fechamento', 'Dinheiro', 'viewFinance'],
        ['events.guests', 'Convidados', 'Casa', 'viewGuests'],
        ['events.artists', 'Lineup', 'Casa', 'view'],
        ['events.tasks', 'Tarefas', 'Dia', 'view'],
        ['events.schedule', 'Horários', 'Dia', 'view'],
        ['events.team', 'Equipe', 'Dia', 'view'],
    ];
@endphp

<nav class="tile-strip" aria-label="Seções do evento">
    @foreach ($items as [$route, $label, $group, $ability])
        @continue($ability && auth()->user()->cannot($ability, $event))
        <x-tile :href="route($route, $event)" size="sm" :current="request()->routeIs($route)">
            <span class="tile-kicker">{{ $group }}</span>
            <span class="tile-title">{{ $label }}</span>
        </x-tile>
    @endforeach
</nav>
