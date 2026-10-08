<div class="flex flex-col gap-5">
    <div>
        <h1>Meus eventos</h1>
        <p class="mt-2 max-w-xl text-steel">Toque um evento para abrir o quadro. O dinheiro, a casa e o dia estão lá dentro.</p>
    </div>

    @if ($totals['events'] > 0)
        <div class="tile-grid tile-grid-3">
            @foreach (['active' => 'Em curso', 'finished' => 'Finalizados', 'all' => 'Todos'] as $value => $label)
                <x-tile type="button" size="sm" :current="$filter === $value" wire:click="$set('filter', '{{ $value }}')">
                    <span class="tile-title">{{ $label }}</span>
                </x-tile>
            @endforeach
        </div>

        <section class="tile-grid">
            <x-tile as="div" size="sm">
                <span class="tile-kicker">Eventos</span>
                <span class="tile-value">{{ $totals['events'] }}</span>
            </x-tile>
            <x-tile as="div" size="sm">
                <span class="tile-kicker">Receita realizada</span>
                <span class="tile-value"><x-money :cents="$totals['gross']" /></span>
            </x-tile>
            <x-tile as="div" size="sm">
                <span class="tile-kicker">Custos assumidos</span>
                <span class="tile-value"><x-money :cents="$totals['costs']" /></span>
            </x-tile>
            <x-tile as="div" size="sm">
                <span class="tile-kicker">Resultado</span>
                <span class="tile-value"><x-money :cents="$totals['profit']" /></span>
            </x-tile>
        </section>
    @endif

    <section class="tile-grid">
        <x-tile :href="route('events.create')" tone="accent" size="lg">
            <span class="tile-kicker">Começar</span>
            <span class="tile-title text-2xl">Novo evento</span>
            <span class="tile-meta">Um template monta o checklist e o cronograma.</span>
        </x-tile>

        @forelse ($rows as $row)
            @php($event = $row['event'])
            @php($statement = $row['statement'])
            <x-tile :href="route('events.show', $event)" size="lg">
                <span class="tile-kicker">{{ $event->type->label() }} · {{ $event->status->label() }}</span>
                <span class="tile-title text-2xl">{{ $event->name }}</span>
                <span class="tile-value"><x-money :cents="$statement->projectedProfit" :currency="$event->currency" /></span>
                <span class="tile-meta">
                    {{ $event->whenLabel() }}
                    @if ($event->city) · {{ $event->city }} @endif
                    · lucro projetado
                </span>
            </x-tile>
        @empty
            @if ($totals['events'] === 0)
                <x-tile as="div" tone="ghost" span>
                    <span class="tile-title">O quadro ainda está vazio.</span>
                    <span class="tile-meta">O tile roxo abre o primeiro evento.</span>
                </x-tile>
            @else
                <x-tile as="div" tone="ghost" span>
                    <span class="tile-title">Nada neste recorte.</span>
                    <span class="tile-meta">Troque o filtro para ver o resto da casa.</span>
                </x-tile>
            @endif
        @endforelse
    </section>
</div>
