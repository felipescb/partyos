<div class="broker-tickets-page">
    <header class="broker-tickets-head">
        <div class="broker-tickets-head-main">
            <div>
                <x-event-category-symbol category="rede" label="Rede" />
                <h1 class="broker-tickets-title">Fornecedores</h1>
                <p class="broker-tickets-sub">Uma ficha só. Os eventos usam essa pessoa, sem copiar telefone para todo lado.</p>
            </div>
        </div>
        <div class="broker-tickets-head-actions broker-card-actions">
            <button type="button" class="broker-card-icon-btn" wire:click="create" aria-label="Novo fornecedor" title="Novo fornecedor">
                <flux:icon.plus variant="mini" class="size-4" />
            </button>
        </div>
    </header>

    <div class="broker-grid broker-grid-event broker-grid-vendors">
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
                    placeholder="Nome, empresa, telefone, e-mail"
                    aria-label="Buscar fornecedor"
                />
                <select wire:model.live="list" class="broker-vendor-filter" aria-label="Categoria">
                    @foreach ($lists as $item)
                        @continue($item['count'] === 0 && $item['key'] !== 'all')
                        <option value="{{ $item['key'] }}">{{ $item['label'] }}</option>
                    @endforeach
                </select>
                <select wire:model.live="work" class="broker-vendor-filter" aria-label="Relação com eventos">
                    <option value="all">Qualquer relação</option>
                    <option value="booked">Já trabalhou</option>
                    <option value="idle">Ainda não trabalhou</option>
                </select>
                <select wire:model.live="contact" class="broker-vendor-filter" aria-label="Contato">
                    <option value="all">Qualquer contato</option>
                    <option value="whatsapp">Com WhatsApp</option>
                    <option value="email">Com e-mail</option>
                    <option value="missing">Sem contato</option>
                </select>
            </div>

            <div class="broker-vendor-columns" aria-hidden="true">
                <span>Nome</span>
                <span>Categoria</span>
                <span>Contato</span>
                <span>Eventos</span>
                <span>Total</span>
            </div>

            <div class="broker-ticket-list" role="list" aria-label="{{ $activeListLabel }}">
                @forelse ($vendors as $vendor)
                    @php($events = $vendor->budgetItems->pluck('event.name')->filter()->unique()->values())
                    <div class="broker-vendor-row" role="listitem" wire:key="vendor-{{ $vendor->id }}">
                        <button type="button" class="broker-ticket-row-hit" wire:click="edit({{ $vendor->id }})" aria-label="Editar {{ $vendor->name }}"></button>
                        <div class="broker-ticket-cell">
                            <span class="broker-ticket-name">{{ $vendor->name }}</span>
                            @if ($vendor->company)
                                <span class="broker-ticket-sub">{{ $vendor->company }}</span>
                            @endif
                        </div>
                        <div class="broker-ticket-cell">
                            <span class="broker-ticket-sub">{{ $vendor->category ?: 'Sem categoria' }}</span>
                        </div>
                        <div class="broker-ticket-cell">
                            <span class="broker-ticket-sub">{{ $vendor->whatsapp ?: ($vendor->phone ?: ($vendor->email ?: '—')) }}</span>
                            @if ($vendor->instagram)
                                <span class="broker-ticket-sub">{{ $vendor->instagram }}</span>
                            @endif
                        </div>
                        <div class="broker-ticket-cell">
                            <span class="broker-ticket-sub">{{ $events->isEmpty() ? 'Nenhum evento' : $events->take(2)->join(', ') }}</span>
                            @if ($events->count() > 2)
                                <span class="broker-ticket-sub">+{{ $events->count() - 2 }}</span>
                            @endif
                        </div>
                        <div class="broker-ticket-cell">
                            <span class="broker-ticket-metric"><x-money :cents="$vendor->budgetItems->sum(fn ($item) => $item->committedAmount())" /></span>
                        </div>
                    </div>
                @empty
                    <div class="broker-ticket-empty">
                        @if ($list === 'all' && $search === '' && $work === 'all' && $contact === 'all')
                            <p>Você ainda não adicionou nenhum fornecedor.</p>
                            <p class="broker-ticket-empty-hint">Som, foto, casa, segurança. Cadastre uma vez e use em todos os eventos.</p>
                            <button type="button" class="broker-task-panel-link" wire:click="create">Novo fornecedor</button>
                        @else
                            <p>Nenhum fornecedor com esses filtros.</p>
                            <p class="broker-ticket-empty-hint">Troque a categoria, a relação ou o contato.</p>
                        @endif
                    </div>
                @endforelse
            </div>
        </article>
    </div>

    <flux:modal wire:model="showForm" class="max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Editar fornecedor' : 'Novo fornecedor' }}</flux:heading>
            <flux:input wire:model="name" label="Nome" />
            <flux:input wire:model="company" label="Empresa" />
            <flux:select wire:model="category" label="Categoria" placeholder="Escolher">
                <flux:select.option value="">Outra</flux:select.option>
                @foreach ($categories as $slug => $label)
                    <flux:select.option value="{{ $label }}">{{ $label }}</flux:select.option>
                @endforeach
                @if ($category !== '' && ! in_array($category, $categories, true))
                    <flux:select.option value="{{ $category }}">{{ $category }}</flux:select.option>
                @endif
            </flux:select>
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="phone" label="Telefone" />
                <flux:input wire:model="whatsapp" label="WhatsApp" />
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="email" type="email" label="E-mail" />
                <flux:input wire:model="instagram" label="Instagram" />
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="taxId" label="CPF ou CNPJ" />
                <flux:input wire:model="pix" label="PIX" />
            </div>
            <flux:input wire:model="address" label="Endereço" />
            <flux:textarea wire:model="notes" label="Observações" rows="2" />
            <div class="flex justify-between">
                @if ($editingId)
                    <flux:button type="button" variant="danger" wire:click="destroy({{ $editingId }})" wire:confirm="Remover este fornecedor?">Excluir</flux:button>
                @endif
                <flux:button variant="primary" type="submit">Salvar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
