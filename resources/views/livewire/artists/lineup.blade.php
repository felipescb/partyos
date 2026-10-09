<div class="broker-tickets-page">
    <header class="broker-tickets-head">
        <div class="broker-tickets-head-main">
            <a href="{{ route('events.show', $event) }}" wire:navigate class="broker-task-notebook-back">← Quadro</a>
            <div>
                <x-event-category-symbol category="casa" />
                <h1 class="broker-tickets-title">Lineup</h1>
                <p class="broker-tickets-sub">{{ $event->name }} · o cachê deste evento entra no orçamento</p>
            </div>
        </div>
        @if ($canEdit)
            <div class="broker-tickets-head-actions broker-card-actions">
                <button type="button" class="broker-card-icon-btn" wire:click="create" aria-label="Novo artista" title="Novo artista">
                    <flux:icon.plus variant="mini" class="size-4" />
                </button>
            </div>
        @endif
    </header>

    <div class="broker-grid broker-grid-event broker-grid-lineup" role="list" aria-label="Lineup do evento">
        @foreach ($lists as $item)
            @continue($item['count'] === 0)
            <button
                type="button"
                wire:click="selectList('{{ $item['key'] }}')"
                wire:key="lineup-list-{{ $item['key'] }}"
                @class(['broker-grid-item', 'broker-card', 'broker-card-module', 'broker-guest-list', 'broker-guest-list-current' => $list === $item['key']])
                role="listitem"
                aria-pressed="{{ $list === $item['key'] ? 'true' : 'false' }}"
            >
                <span class="broker-card-name">{{ $item['label'] }}</span>
                <span class="broker-card-quote">{{ $item['count'] }}</span>
                <span class="broker-card-foot"><x-money :cents="$item['fee']" :currency="$event->currency" /></span>
            </button>
        @endforeach

        <article class="broker-grid-item broker-card broker-ticket-panel broker-ticket-list-full" role="listitem">
            <header class="broker-ticket-panel-head">
                <div>
                    <x-event-category-symbol category="casa" />
                    <h2 class="broker-ticket-panel-title">{{ $activeListLabel }}</h2>
                </div>
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    class="broker-guest-search"
                    placeholder="Nome artístico"
                    aria-label="Buscar no lineup"
                />
            </header>

            <div class="broker-lineup-columns" aria-hidden="true">
                <span>Nome</span>
                <span>Status</span>
                <span>Cachê</span>
                <span>Horário</span>
            </div>

            <div class="broker-ticket-list" role="list" aria-label="{{ $activeListLabel }}">
                @forelse ($bookings as $booking)
                    <div class="broker-lineup-row" role="listitem" wire:key="booking-{{ $booking->id }}">
                        @if ($canEdit)
                            <button type="button" class="broker-ticket-row-hit" wire:click="edit({{ $booking->id }})" aria-label="Editar {{ $booking->artist->stage_name }}"></button>
                        @endif
                        <div class="broker-ticket-cell">
                            <span class="broker-ticket-name">{{ $booking->artist->stage_name }}</span>
                            @if ($booking->notes)
                                <span class="broker-ticket-sub">{{ $booking->notes }}</span>
                            @endif
                        </div>
                        <div class="broker-ticket-cell">
                            <span class="broker-ticket-sub">{{ $booking->status->label() }}</span>
                        </div>
                        <div class="broker-ticket-cell">
                            @if ($booking->budgetItem)
                                <span class="broker-ticket-metric"><x-money :cents="$booking->budgetItem->committedAmount()" :currency="$event->currency" /></span>
                            @else
                                <span class="broker-ticket-sub">—</span>
                            @endif
                        </div>
                        <div class="broker-ticket-cell">
                            <span class="broker-ticket-sub">
                                {{ $booking->starts_at?->format('d/m H:i') ?? 'A definir' }}@if ($booking->ends_at) – {{ $booking->ends_at->format('H:i') }}@endif
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="broker-ticket-empty">
                        <p>Nenhum artista neste lineup.</p>
                        <p class="broker-ticket-empty-hint">Escolha alguém que você já trabalhou ou cadastre agora. O cachê aparece em Custos.</p>
                        @if ($canEdit)
                            <button type="button" class="broker-task-panel-link" wire:click="create">Novo artista</button>
                        @endif
                    </div>
                @endforelse
            </div>
        </article>
    </div>

    <flux:modal wire:model="showForm" class="max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Editar artista' : 'Novo artista' }}</flux:heading>
            <flux:select wire:model="artistId" label="Artista da casa" placeholder="Novo nome">
                <flux:select.option value="">Cadastrar agora</flux:select.option>
                @foreach ($artists as $artist)
                    <flux:select.option value="{{ $artist->id }}">{{ $artist->stage_name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="stageName" label="Nome artístico, se for novo" />
            <flux:input wire:model="fee" label="Cachê" placeholder="2.000,00" />
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="startsAt" type="datetime-local" label="Entra" />
                <flux:input wire:model="endsAt" type="datetime-local" label="Sai" />
            </div>
            <flux:select wire:model="status" label="Status">
                @foreach ($statuses as $status)
                    <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:textarea wire:model="notes" label="Observação" rows="2" />
            <div class="flex justify-between">
                @if ($editingId)
                    <flux:button type="button" variant="danger" wire:click="destroy({{ $editingId }})" wire:confirm="Remover este artista do evento?">Remover do evento</flux:button>
                @endif
                <flux:button variant="primary" type="submit">Salvar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
