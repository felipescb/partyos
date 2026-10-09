@props(['event'])

@php
    $items = [
        ['events.show', 'Quadro', 'evento', null],
        ['events.costs', 'Custos', 'financeiro', 'viewFinance'],
        ['events.tickets', 'Ingressos', 'financeiro', 'viewFinance'],
        ['events.revenues', 'Receitas', 'financeiro', 'viewFinance'],
        ['events.cashflow', 'Fluxo', 'financeiro', 'viewFinance'],
        ['events.scenarios', 'Cenários', 'financeiro', 'viewFinance'],
        ['events.close', 'Fechamento', 'financeiro', 'viewFinance'],
        ['events.guests', 'Convidados', 'casa', 'viewGuests'],
        ['events.artists', 'Lineup', 'casa', 'view'],
        ['events.tasks', 'Tarefas', 'checklist', 'view'],
        ['events.schedule', 'Horários', 'operacao', 'view'],
        ['events.team', 'Equipe', 'equipe', 'view'],
    ];
@endphp

<nav class="tile-strip" aria-label="Seções do evento">
    @foreach ($items as [$route, $label, $category, $ability])
        @continue($ability && auth()->user()->cannot($ability, $event))
        <x-tile :href="route($route, $event)" size="sm" :current="request()->routeIs($route)">
            <x-event-category-symbol :category="$category" variant="kicker" />
            <span class="tile-title">{{ $label }}</span>
        </x-tile>
    @endforeach
</nav>
