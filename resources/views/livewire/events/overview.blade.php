<div class="broker-grid broker-grid-event" role="list" aria-label="Quadro do evento">
    <div class="broker-event-intro-stack broker-h-3">
            <article class="broker-grid-item broker-card broker-card-hero" role="listitem">
                <div class="broker-card-head">
                    <x-event-category-symbol category="evento" :label="$event->type->label()" />
                    <div class="broker-card-actions">
                        <span @class(['broker-card-status', 'broker-card-status-'.$statusTone])>{{ $event->status->label() }}</span>
                        @can('viewFinance', $event)
                            <a href="{{ route('events.tickets', $event) }}" wire:navigate class="broker-card-icon-btn" aria-label="Gerenciar ingressos">
                                <flux:icon.ticket variant="mini" class="size-4" />
                            </a>
                        @endcan
                        @can('manageOperations', $event)
                            <a href="{{ route('events.edit', $event) }}" wire:navigate class="broker-card-icon-btn" aria-label="Editar evento">
                                <flux:icon.pencil-square variant="mini" class="size-4" />
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="broker-card-hero-body">
                    <h1 class="broker-card-hero-title">{{ $event->name }}</h1>
                    <p class="broker-card-hero-meta">
                        {{ $event->whenLabel() }}
                        @if ($event->venue_name) · {{ $event->venue_name }} @endif
                        @if ($event->city) · {{ $event->city }} @endif
                        @if ($event->capacity) · {{ $event->capacity }} pessoas @endif
                    </p>
                    @if ($canFinance)
                        <p class="broker-card-hero-quote">
                            <x-money :cents="$statement->projectedProfit" :currency="$event->currency" />
                            <span class="broker-card-hero-quote-label">lucro projetado</span>
                        </p>
                    @endif
                </div>
            </article>

            <x-event-task-grid
                :event="$event"
                :tasks="$tasks"
                :open-count="$pendingTasks"
                class="broker-grid-item broker-event-tasks"
            />

            <x-event-team-grid
                :event="$event"
                :members="$teamMembers"
                :member-count="$teamMemberCount"
                :can-manage="$canManageTeam"
                :roles="$teamRoles"
                class="broker-grid-item broker-event-team"
            />
    </div>

    <x-event-planning-timeline
        :timeline="$planningTimeline"
        :event="$event"
        class="broker-grid-item broker-event-intro-timeline"
    />

    @can('viewFinance', $event)
        <a href="{{ route('events.costs', $event) }}" wire:navigate class="broker-grid-item broker-card broker-card-module" role="listitem">
            <x-event-category-symbol category="financeiro" />
            <span class="broker-card-name">Custos</span>
            <span class="broker-card-quote"><x-money :cents="$statement->committedCosts" :currency="$event->currency" /></span>
            <span class="broker-card-foot">Resta <x-money :cents="$statement->remainingCosts" :currency="$event->currency" /></span>
        </a>
        <x-event-ticket-grid
            :event="$event"
            :tiers="$ticketTiers"
            :sold="$ticketsSold"
            :available="$ticketsAvailable"
            :projected-profit="$statement->projectedProfit"
            :expanded="$ticketsGridExpanded"
            :can-manage="auth()->user()->can('manageFinance', $event)"
            class="broker-grid-item"
        />
        <a href="{{ route('events.revenues', $event) }}" wire:navigate class="broker-grid-item broker-card broker-card-module" role="listitem">
            <x-event-category-symbol category="financeiro" />
            <span class="broker-card-name">Receitas</span>
            <span class="broker-card-quote"><x-money :cents="$statement->expectedOtherRevenue" :currency="$event->currency" /></span>
            <span class="broker-card-foot">Receitas adicionais além dos ingressos</span>
        </a>
        <a href="{{ route('events.close', $event) }}" wire:navigate class="broker-grid-item broker-card broker-card-module" role="listitem">
            <x-event-category-symbol category="financeiro" />
            <span class="broker-card-name">Resultado</span>
            <span class="broker-card-quote"><x-money :cents="$statement->projectedProfit" :currency="$event->currency" /></span>
            <span class="broker-card-foot">Lucro projetado</span>
        </a>
        <a href="{{ route('events.scenarios', $event) }}" wire:navigate class="broker-grid-item broker-card broker-card-module" role="listitem">
            <x-event-category-symbol category="financeiro" />
            <span class="broker-card-name">Empate</span>
            @if ($statement->committedCosts === 0 && $statement->ticketsGoal === 0)
                <span class="broker-card-quote broker-card-quote-muted">—</span>
                <span class="broker-card-foot">Falta custo e lote</span>
            @elseif ($statement->breakEvenPeople === null)
                <span class="broker-card-quote broker-card-quote-muted">—</span>
                <span class="broker-card-foot">Defina a meta dos lotes</span>
            @else
                <span class="broker-card-quote">{{ $statement->breakEvenPeople }}</span>
                <span class="broker-card-foot">pessoas para empatar</span>
            @endif
        </a>
        <x-event-cashflow-grid
            :event="$event"
            :preview="$cashflowPreview"
            class="broker-grid-item"
        />
    @endcan

    @can('viewGuests', $event)
        <a href="{{ route('events.guests', $event) }}" wire:navigate class="broker-grid-item broker-card broker-card-module" role="listitem">
            <x-event-category-symbol category="casa" />
            <span class="broker-card-name">Convidados</span>
            <span class="broker-card-quote">{{ $statement->confirmedGuestHeads }}</span>
            <span class="broker-card-foot">Confirmados</span>
        </a>
    @endcan
    <a href="{{ route('events.artists', $event) }}" wire:navigate class="broker-grid-item broker-card broker-card-module" role="listitem">
        <x-event-category-symbol category="casa" />
        <span class="broker-card-name">Lineup</span>
        <span class="broker-card-quote">{{ $lineupCount }}</span>
        <span class="broker-card-foot">Artistas neste evento</span>
    </a>
    <x-event-operation-grid
        :event="$event"
        :flow="$scheduleFlow"
        :total="$scheduleCount"
        class="broker-grid-item"
    />
    @if ($event->status === \App\Enums\EventStatus::Live)
        <article class="broker-grid-item broker-card broker-card-span-2 broker-card-live broker-event-stack-span" role="listitem">
            <x-event-category-symbol category="ao-vivo" />
            <span class="broker-card-name">{{ $next?->title ?? 'Nada no cronograma agora' }}</span>
            <span class="broker-card-foot">
                {{ $next?->starts_at?->format('H:i') }}
                · {{ $statement->ticketsSold }} ingressos vendidos
                · meta {{ $statement->ticketsGoal + $statement->confirmedGuestHeads }} pessoas
            </span>
        </article>
    @endif

    @if ($canFinance)
        @foreach ($alerts as $alert)
            @if ($alert->route)
                <a href="{{ route($alert->route, $event) }}" wire:navigate class="broker-grid-item broker-card broker-card-span-2 broker-card-alert broker-event-stack-span" role="listitem">
                    <x-event-category-symbol :category="$alert->tone === 'danger' ? 'atencao' : 'olhar'" />
                    <span class="broker-card-name">{{ $alert->message }}</span>
                    <span class="broker-card-foot">Toque para resolver</span>
                </a>
            @else
                <article class="broker-grid-item broker-card broker-card-span-2 broker-card-alert broker-event-stack-span" role="listitem">
                    <x-event-category-symbol :category="$alert->tone === 'danger' ? 'atencao' : 'olhar'" />
                    <span class="broker-card-name">{{ $alert->message }}</span>
                </article>
            @endif
        @endforeach
    @endif

    @if ($canFinance)
        <article class="broker-grid-item broker-card broker-card-span-2 broker-card-read broker-event-stack-span" role="listitem">
            <x-event-category-symbol category="leitura" />
            <span class="broker-card-name">O que isso significa</span>
            <ul class="broker-card-read-list">
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
        </article>
    @endif

    @include('partials.event-team-modal')
</div>
