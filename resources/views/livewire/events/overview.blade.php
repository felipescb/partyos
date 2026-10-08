<div class="flex flex-col gap-5">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="tile-kicker">{{ $event->type->label() }} · {{ $event->status->label() }}</p>
            <h1 class="mt-1">{{ $event->name }}</h1>
            <p class="mt-2 text-steel">
                {{ $event->whenLabel() }}
                @if ($event->venue_name) · {{ $event->venue_name }} @endif
                @if ($event->city) · {{ $event->city }} @endif
                @if ($event->capacity) · {{ $event->capacity }} pessoas @endif
            </p>
        </div>
        @can('manageOperations', $event)
            <div class="grid w-full grid-cols-2 gap-3 sm:w-auto">
                <x-tile :href="route('events.edit', $event)" size="sm">
                    <span class="tile-kicker">Evento</span>
                    <span class="tile-title">Editar</span>
                </x-tile>
                <x-tile type="button" size="sm" tone="accent" wire:click="$set('showDuplicate', true)">
                    <span class="tile-kicker">Edição</span>
                    <span class="tile-title">Duplicar</span>
                </x-tile>
            </div>
        @endcan
    </div>

    @if ($event->status === \App\Enums\EventStatus::Live)
        <x-tile as="div" tone="live" span class="!min-h-0">
            <span class="tile-kicker">Ao vivo</span>
            <span class="tile-title text-3xl">{{ $next?->title ?? 'Nada no cronograma agora' }}</span>
            <span class="tile-meta">
                {{ $next?->starts_at?->format('H:i') }}
                · {{ $statement->ticketsSold }} ingressos vendidos
                · meta {{ $statement->ticketsGoal + $statement->confirmedGuestHeads }} pessoas
            </span>
        </x-tile>
    @endif

    @if ($canFinance)
        @foreach ($alerts as $alert)
            <x-tile
                :href="$alert->route ? route($alert->route, $event) : null"
                :as="$alert->route ? null : 'div'"
                span
                class="!min-h-0"
            >
                <span class="tile-kicker">{{ $alert->tone === 'danger' ? 'Atenção' : 'Olhar' }}</span>
                <span class="tile-title">{{ $alert->message }}</span>
                @if ($alert->route)
                    <span class="tile-meta">Toque para resolver</span>
                @endif
            </x-tile>
        @endforeach
    @endif

    <section class="tile-grid" aria-label="Quadro do evento">
        @can('viewFinance', $event)
            <x-tile :href="route('events.costs', $event)" size="lg">
                <span class="tile-kicker">Dinheiro</span>
                <span class="tile-title">Custos</span>
                <span class="tile-value"><x-money :cents="$statement->committedCosts" :currency="$event->currency" /></span>
                <span class="tile-meta">Resta <x-money :cents="$statement->remainingCosts" :currency="$event->currency" /></span>
            </x-tile>
            <x-tile :href="route('events.tickets', $event)" size="lg">
                <span class="tile-kicker">Dinheiro</span>
                <span class="tile-title">Ingressos</span>
                <span class="tile-value">{{ $statement->ticketsSold }}<span class="text-xl text-zinc-500">/{{ $statement->ticketsGoal }}</span></span>
                <span class="tile-meta">Vendidos na meta · <x-money :cents="$statement->actualTicketRevenue" :currency="$event->currency" /></span>
            </x-tile>
            <x-tile :href="route('events.revenues', $event)">
                <span class="tile-kicker">Dinheiro</span>
                <span class="tile-title">Receitas</span>
                <span class="tile-value"><x-money :cents="$statement->expectedOtherRevenue" :currency="$event->currency" /></span>
                <span class="tile-meta">Fora dos ingressos</span>
            </x-tile>
            <x-tile :href="route('events.close', $event)" :tone="$statement->projectedProfit < 0 ? 'surface' : 'surface'">
                <span class="tile-kicker">Dinheiro</span>
                <span class="tile-title">Resultado</span>
                <span class="tile-value"><x-money :cents="$statement->projectedProfit" :currency="$event->currency" /></span>
                <span class="tile-meta">Lucro projetado</span>
            </x-tile>
            <x-tile :href="route('events.scenarios', $event)">
                <span class="tile-kicker">Dinheiro</span>
                <span class="tile-title">Empate</span>
                @if ($statement->committedCosts === 0 && $statement->ticketsGoal === 0)
                    <span class="tile-value">—</span>
                    <span class="tile-meta">Falta custo e lote</span>
                @elseif ($statement->breakEvenPeople === null)
                    <span class="tile-value">—</span>
                    <span class="tile-meta">Defina a meta dos lotes</span>
                @else
                    <span class="tile-value">{{ $statement->breakEvenPeople }}</span>
                    <span class="tile-meta">pessoas para empatar</span>
                @endif
            </x-tile>
            <x-tile :href="route('events.cashflow', $event)">
                <span class="tile-kicker">Dinheiro</span>
                <span class="tile-title">Fluxo</span>
                @if ($payments->isNotEmpty())
                    <span class="tile-value"><x-money :cents="$payments->sum('amount')" :currency="$event->currency" /></span>
                    <span class="tile-meta">{{ $payments->count() }} saídas agendadas</span>
                @else
                    <span class="tile-value">—</span>
                    <span class="tile-meta">Nada agendado</span>
                @endif
            </x-tile>
            <x-tile :href="route('events.distribution', $event)">
                <span class="tile-kicker">Dinheiro</span>
                <span class="tile-title">Divisão</span>
                <span class="tile-meta">Taxas e quem fica com o quê</span>
            </x-tile>
        @endcan

        <x-tile :href="route('events.tasks', $event)">
            <span class="tile-kicker">Dia</span>
            <span class="tile-title">Tarefas</span>
            <span class="tile-value">{{ $pendingTasks }}</span>
            <span class="tile-meta">{{ $tasks->first()?->title ?? 'Nada pendente' }}</span>
        </x-tile>
        <x-tile :href="route('events.schedule', $event)">
            <span class="tile-kicker">Dia</span>
            <span class="tile-title">Horários</span>
            <span class="tile-value">{{ $next?->starts_at?->format('H:i') ?? '—' }}</span>
            <span class="tile-meta">{{ $next?->title ?? 'Cronograma vazio' }}</span>
        </x-tile>
        @can('viewGuests', $event)
            <x-tile :href="route('events.guests', $event)">
                <span class="tile-kicker">Casa</span>
                <span class="tile-title">Convidados</span>
                <span class="tile-value">{{ $statement->confirmedGuestHeads }}</span>
                <span class="tile-meta">Confirmados, com acompanhantes</span>
            </x-tile>
        @endcan
        <x-tile :href="route('events.artists', $event)">
            <span class="tile-kicker">Casa</span>
            <span class="tile-title">Lineup</span>
            <span class="tile-value">{{ $lineupCount }}</span>
            <span class="tile-meta">Artistas neste evento</span>
        </x-tile>
        <x-tile :href="route('events.team', $event)">
            <span class="tile-kicker">Dia</span>
            <span class="tile-title">Equipe</span>
            <span class="tile-meta">Quem produz, quem vê o caixa, quem opera</span>
        </x-tile>
    </section>

    @if ($canFinance)
        <x-tile as="div" span class="!min-h-0">
            <span class="tile-kicker">Leitura</span>
            <span class="tile-title">O que isso significa</span>
            <ul class="mt-2 space-y-2 text-base leading-[1.38] text-steel">
                @if ($statement->committedCosts === 0 && $statement->expectedGross === 0)
                    <li>Lance os custos e os lotes. Aí o PartyOS calcula quantas pessoas empatam a noite.</li>
                @else
                    @if ($statement->additionalTicketsToBreakEven() > 0)
                        <li>Você precisa vender mais {{ $statement->additionalTicketsToBreakEven() }} ingressos para empatar.</li>
                    @elseif ($statement->breakEvenPeople === 0 && $statement->committedCosts > 0)
                        <li>As outras receitas já cobrem os custos. O ingresso entra como lucro.</li>
                    @elseif ($statement->breakEvenPeople === null)
                        <li>Defina a meta dos lotes para calcular quantas pessoas empatam.</li>
                    @endif
                    @if ($statement->breakEvenRevenue)
                        <li>Para empatar só com bilheteria, a venda precisa chegar em <x-money :cents="$statement->breakEvenRevenue" :currency="$event->currency" />.</li>
                    @endif
                    @if ($statement->breakEvenAverageTicket)
                        <li>No público da meta, o ingresso médio precisa ser de <x-money :cents="$statement->breakEvenAverageTicket" :currency="$event->currency" />.</li>
                    @endif
                    @if ($statement->expectedNet > 0)
                        <li>Dá para gastar até <x-money :cents="$statement->spendingCeiling" :currency="$event->currency" /> e ainda empatar.</li>
                        <li>O teto de cachê, sem mexer nos outros custos, é <x-money :cents="$statement->feeCeiling" :currency="$event->currency" />.</li>
                    @endif
                @endif
            </ul>
        </x-tile>
    @endif

    <flux:modal wire:model="showDuplicate" class="max-w-lg">
        <form wire:submit="duplicate" class="space-y-4">
            <flux:heading size="lg">Duplicar evento</flux:heading>
            <flux:text>A nova edição nasce em rascunho. Pagamentos já feitos não são copiados.</flux:text>
            <flux:input wire:model="duplicateName" label="Nome da nova edição" />
            <flux:input wire:model="duplicateStarts" type="datetime-local" label="Nova data de início" />
            <div class="grid grid-cols-2 gap-2 text-sm">
                @foreach (['budget' => 'Orçamento', 'artists' => 'Artistas', 'tasks' => 'Tarefas', 'schedule' => 'Cronograma', 'tickets' => 'Ingressos', 'guests' => 'Convidados'] as $value => $label)
                    <label class="flex min-h-12 items-center gap-3">
                        <input type="checkbox" value="{{ $value }}" wire:model="copy" class="size-5 rounded border-zinc-400">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="$set('showDuplicate', false)">Cancelar</flux:button>
                <flux:button variant="primary" type="submit">Criar edição</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
