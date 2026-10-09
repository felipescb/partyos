<div class="broker-tickets-page">
    <header class="broker-tickets-head">
        <div class="broker-tickets-head-main">
            <a href="{{ route('events.show', $event) }}" wire:navigate class="broker-task-notebook-back">← Quadro</a>
            <div>
                <x-event-category-symbol category="casa" />
                <h1 class="broker-tickets-title">Convidados</h1>
                <p class="broker-tickets-sub">{{ $event->name }} · cada cartão é uma lista, pronta para CSV</p>
            </div>
        </div>
        <div class="broker-tickets-head-actions broker-card-actions">
            <button type="button" class="broker-card-icon-btn" wire:click="exportList" aria-label="Baixar CSV" title="Baixar CSV">
                <flux:icon.arrow-down-tray variant="mini" class="size-4" />
            </button>
            @if ($canEdit)
                <button type="button" class="broker-card-icon-btn" wire:click="openImport" aria-label="Subir CSV" title="Subir CSV">
                    <flux:icon.arrow-up-tray variant="mini" class="size-4" />
                </button>
                <button type="button" class="broker-card-icon-btn" wire:click="create" aria-label="Novo convidado" title="Novo convidado">
                    <flux:icon.plus variant="mini" class="size-4" />
                </button>
            @endif
        </div>
    </header>

    <div class="broker-grid broker-grid-event broker-grid-guests" role="list" aria-label="Listas de convidados">
        @foreach ($lists as $item)
            @continue($item['count'] === 0)
            <button
                type="button"
                wire:click="selectList('{{ $item['key'] }}')"
                wire:key="guest-list-{{ $item['key'] }}"
                @class(['broker-grid-item', 'broker-card', 'broker-card-module', 'broker-guest-list', 'broker-guest-list-current' => $list === $item['key']])
                role="listitem"
                aria-pressed="{{ $list === $item['key'] ? 'true' : 'false' }}"
            >
                <span class="broker-card-name">{{ $item['label'] }}</span>
                <span class="broker-card-quote">{{ $item['count'] }}</span>
                <span class="broker-card-foot">{{ $item['heads'] }} confirmados</span>
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
                    placeholder="Nome, e-mail, Instagram"
                    aria-label="Buscar nesta lista"
                />
            </header>

            <div class="broker-guest-columns" aria-hidden="true">
                <span>Nome</span>
                <span>Lista</span>
                <span>RSVP</span>
                <span>Pessoas</span>
                <span>Contato</span>
                <span></span>
            </div>

            <div class="broker-ticket-list" role="list" aria-label="{{ $activeListLabel }}">
                @forelse ($guests as $guest)
                    <div class="broker-guest-row" role="listitem" wire:key="guest-{{ $guest->id }}">
                        @if ($canEdit)
                            <button type="button" class="broker-ticket-row-hit" wire:click="edit({{ $guest->id }})" aria-label="Editar {{ $guest->name }}"></button>
                        @endif
                        <div class="broker-ticket-cell">
                            <span class="broker-ticket-name">{{ $guest->name }}</span>
                            @if ($guest->instagram)
                                <span class="broker-ticket-sub">{{ $guest->instagram }}</span>
                            @endif
                        </div>
                        <div class="broker-ticket-cell">
                            <span class="broker-ticket-sub">{{ $guest->category->label() }}</span>
                        </div>
                        <div class="broker-ticket-cell">
                            <span class="broker-ticket-sub">{{ $guest->rsvp_status->label() }}</span>
                        </div>
                        <div class="broker-ticket-cell">
                            <span class="broker-ticket-metric">{{ $guest->headcount() }}</span>
                        </div>
                        <div class="broker-ticket-cell">
                            <span class="broker-ticket-sub">{{ $guest->email ?: ($guest->phone ?: '—') }}</span>
                        </div>
                        <div class="broker-guest-checkin">
                            @if ($canEdit && $guest->rsvp_status !== \App\Enums\RsvpStatus::CheckedIn)
                                <button type="button" wire:click="checkIn({{ $guest->id }})">Check-in</button>
                            @elseif ($guest->checked_in_at)
                                <span>{{ $guest->checked_in_at->format('H:i') }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="broker-ticket-empty">
                        <p>Essa lista ainda está vazia.</p>
                        <p class="broker-ticket-empty-hint">Adicione uma pessoa ou suba um CSV com nome, lista e RSVP.</p>
                        @if ($canEdit)
                            <button type="button" class="broker-task-panel-link" wire:click="create">Novo convidado</button>
                        @endif
                    </div>
                @endforelse
            </div>

            @if ($guests->hasPages())
                <div class="broker-guest-pages">{{ $guests->links() }}</div>
            @endif
        </article>
    </div>

    <flux:modal wire:model="showForm" class="max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Editar convidado' : 'Novo convidado' }}</flux:heading>
            <flux:input wire:model="name" label="Nome" />
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="phone" label="Telefone" />
                <flux:input wire:model="instagram" label="Instagram" />
            </div>
            <flux:input wire:model="email" type="email" label="E-mail" />
            <div class="grid gap-4 md:grid-cols-3">
                <flux:select wire:model="category" label="Lista">
                    @foreach ($categories as $category)
                        <flux:select.option value="{{ $category->value }}">{{ $category->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="rsvp" label="RSVP">
                    @foreach ($statuses as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="plusOnes" type="number" min="0" label="Acompanhantes" />
            </div>
            <flux:textarea wire:model="notes" label="Observação" rows="2" />
            <div class="flex justify-between">
                @if ($editingId)
                    <flux:button type="button" variant="danger" wire:click="destroy({{ $editingId }})" wire:confirm="Remover este convidado?">Excluir</flux:button>
                @endif
                <flux:button variant="primary" type="submit">Salvar</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showImport" class="max-w-lg">
        <form wire:submit="importList" class="space-y-4">
            <flux:heading size="lg">Subir CSV</flux:heading>
            <flux:text>A coluna nome é obrigatória. Lista, RSVP e acompanhantes entram quando o arquivo tiver. Sem a coluna lista, as pessoas caem na lista aberta. O mesmo e-mail atualiza quem já está na festa.</flux:text>
            <flux:input wire:model="importFile" type="file" label="Arquivo CSV" accept=".csv,text/csv,text/plain" />
            <flux:error name="importFile" />
            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="closeImport">Cancelar</flux:button>
                <flux:button variant="primary" type="submit">Importar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
