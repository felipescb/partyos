<div class="broker-tickets-page">
    <header class="broker-tickets-head">
        <div class="broker-tickets-head-main">
            <div>
                <x-event-category-symbol category="rede" label="Rede" />
                <h1 class="broker-tickets-title">Artistas</h1>
                <p class="broker-tickets-sub">O mesmo artista em vários eventos. O cachê de cada noite fica no evento.</p>
            </div>
        </div>
        <div class="broker-tickets-head-actions broker-card-actions">
            <button type="button" class="broker-card-icon-btn" wire:click="create" aria-label="Novo artista" title="Novo artista">
                <flux:icon.plus variant="mini" class="size-4" />
            </button>
        </div>
    </header>

    <div class="broker-grid broker-grid-event broker-grid-artists">
        <article class="broker-grid-item broker-card broker-ticket-panel broker-ticket-list-full">
            <header class="broker-ticket-panel-head">
                <div>
                    <x-event-category-symbol category="rede" label="Rede" />
                    <h2 class="broker-ticket-panel-title">{{ $activeListLabel }}</h2>
                </div>
            </header>

            <div class="broker-vendor-toolbar">
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    class="broker-guest-search"
                    placeholder="Nome, agência, telefone, e-mail"
                    aria-label="Buscar artista"
                />
                <select wire:model.live="list" class="broker-vendor-filter" aria-label="Agência">
                    @foreach ($lists as $item)
                        @continue($item['count'] === 0 && $item['key'] !== 'all')
                        <option value="{{ $item['key'] }}">{{ $item['label'] }}</option>
                    @endforeach
                </select>
                <select wire:model.live="work" class="broker-vendor-filter" aria-label="Relação com eventos">
                    <option value="all">Qualquer relação</option>
                    <option value="booked">Já tocou</option>
                    <option value="idle">Ainda não tocou</option>
                </select>
                <select wire:model.live="contact" class="broker-vendor-filter" aria-label="Contato">
                    <option value="all">Qualquer contato</option>
                    <option value="phone">Com telefone</option>
                    <option value="email">Com e-mail</option>
                    <option value="missing">Sem contato</option>
                </select>
            </div>

            <div class="broker-artist-columns" aria-hidden="true">
                <span>Nome</span>
                <span>Agência</span>
                <span>Contato</span>
                <span>Eventos</span>
                <span>Total</span>
            </div>

            <div class="broker-ticket-list" role="list" aria-label="{{ $activeListLabel }}">
                @forelse ($artists as $artist)
                    @php($events = $artist->bookings->pluck('event.name')->filter()->unique()->values())
                    <div class="broker-artist-row" role="listitem" wire:key="artist-{{ $artist->id }}">
                        <button type="button" class="broker-ticket-row-hit" wire:click="edit({{ $artist->id }})" aria-label="Editar {{ $artist->stage_name }}"></button>
                        <div class="broker-ticket-cell">
                            <span class="broker-ticket-name">{{ $artist->stage_name }}</span>
                            @if ($artist->legal_name)
                                <span class="broker-ticket-sub">{{ $artist->legal_name }}</span>
                            @endif
                        </div>
                        <div class="broker-ticket-cell">
                            <span class="broker-ticket-sub">{{ $artist->agency ?: 'Sem agência' }}</span>
                        </div>
                        <div class="broker-ticket-cell">
                            <span class="broker-ticket-sub">{{ $artist->phone ?: ($artist->email ?: '—') }}</span>
                            @if ($artist->instagram)
                                <span class="broker-ticket-sub">{{ $artist->instagram }}</span>
                            @endif
                        </div>
                        <div class="broker-ticket-cell">
                            <span class="broker-ticket-sub">{{ $events->isEmpty() ? 'Nenhum evento' : $events->take(2)->join(', ') }}</span>
                            @if ($events->count() > 2)
                                <span class="broker-ticket-sub">+{{ $events->count() - 2 }}</span>
                            @endif
                        </div>
                        <div class="broker-ticket-cell">
                            <span class="broker-ticket-metric"><x-money :cents="$artist->bookings->sum(fn ($booking) => $booking->budgetItem?->committedAmount() ?? 0)" /></span>
                        </div>
                    </div>
                @empty
                    <div class="broker-ticket-empty">
                        @if ($list === 'all' && $search === '' && $work === 'all' && $contact === 'all')
                            <p>Você ainda não adicionou nenhum artista.</p>
                            <p class="broker-ticket-empty-hint">Cadastre uma vez e use o mesmo nome em todos os eventos.</p>
                            <button type="button" class="broker-task-panel-link" wire:click="create">Novo artista</button>
                        @else
                            <p>Nenhum artista com esses filtros.</p>
                            <p class="broker-ticket-empty-hint">Troque a agência, a relação ou o contato.</p>
                        @endif
                    </div>
                @endforelse
            </div>
        </article>
    </div>

    <flux:modal wire:model="showForm" class="max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Editar artista' : 'Novo artista' }}</flux:heading>
            <flux:input wire:model="stageName" label="Nome artístico" />
            <flux:input wire:model="legalName" label="Nome" />
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="instagram" label="Instagram" />
                <flux:input wire:model="phone" label="Telefone" />
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="email" type="email" label="E-mail" />
                <flux:input wire:model="agency" label="Agência" />
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="defaultFee" label="Cachê de referência" />
                <flux:input wire:model="pix" label="PIX" />
            </div>
            <flux:textarea wire:model="techRider" label="Rider técnico" rows="3" />
            <flux:textarea wire:model="hospitalityRider" label="Rider de hospitalidade" rows="3" />
            <flux:textarea wire:model="notes" label="Observações" rows="2" />
            <div class="flex justify-between">
                @if ($editingId)
                    <flux:button type="button" variant="danger" wire:click="destroy({{ $editingId }})" wire:confirm="Remover este artista?">Excluir</flux:button>
                @endif
                <flux:button variant="primary" type="submit">Salvar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
