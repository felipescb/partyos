<div class="broker-tickets-page">
    <header class="broker-tickets-head">
        <div class="broker-tickets-head-main">
            <a href="{{ route('events.show', $event) }}" wire:navigate class="broker-task-notebook-back">← Quadro</a>
            <div>
                <x-event-category-symbol category="financeiro" />
                <h1 class="broker-tickets-title">Ingressos</h1>
                <p class="broker-tickets-sub">{{ $event->name }} · meta e vendido alimentam receita e empate</p>
            </div>
        </div>
        @if ($canEdit)
            <div class="broker-tickets-head-actions broker-card-actions">
                <button
                    type="button"
                    class="broker-card-icon-btn"
                    wire:click="openImportModal"
                    aria-label="Importar vendidos"
                    title="Importar vendidos"
                >
                    <flux:icon.arrow-up-tray variant="mini" class="size-4" />
                </button>
                <button
                    type="button"
                    class="broker-card-icon-btn"
                    wire:click="create"
                    aria-label="Novo ingresso"
                    title="Novo ingresso"
                >
                    <flux:icon.plus variant="mini" class="size-4" />
                </button>
            </div>
        @endif
    </header>

    <div class="broker-grid broker-grid-tickets" role="list" aria-label="Ingressos do evento">
        <article class="broker-grid-item broker-card broker-card-module" role="listitem">
            <x-event-category-symbol category="financeiro" />
            <span class="broker-card-name">Vendidos</span>
            <span class="broker-card-quote">{{ $sold }}</span>
            <span class="broker-card-foot">Ingressos confirmados</span>
        </article>
        <article class="broker-grid-item broker-card broker-card-module" role="listitem">
            <x-event-category-symbol category="financeiro" />
            <span class="broker-card-name">Meta</span>
            <span class="broker-card-quote">{{ $goal }}</span>
            <span class="broker-card-foot">Soma das metas dos lotes</span>
        </article>
        <article class="broker-grid-item broker-card broker-card-module" role="listitem">
            <x-event-category-symbol category="financeiro" />
            <span class="broker-card-name">Receita</span>
            <span class="broker-card-quote"><x-money :cents="$revenue" :currency="$event->currency" /></span>
            <span class="broker-card-foot">Preço × vendidos</span>
        </article>

        <x-event-ticket-list
            :event="$event"
            :tiers="$tiers"
            :can-edit="$canEdit"
            class="broker-grid-item broker-ticket-list-full"
        />
    </div>

    <flux:modal wire:model="showImportModal" class="max-w-lg">
        <form wire:submit="importSold" class="space-y-4">
            <flux:heading size="lg">Importar vendidos</flux:heading>
            <flux:text>Envie o CSV da plataforma. Lotes que não existirem no evento serão criados; os vendidos serão atualizados conforme o arquivo.</flux:text>
            <flux:select wire:model.live="importPlatform" label="Plataforma">
                @foreach ($importPlatforms as $platform)
                    <flux:select.option value="{{ $platform->value }}">{{ $platform->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="importFile" type="file" label="Arquivo CSV" accept=".csv,text/csv,text/plain" />
            @if ($importPlatform === 'shotgun')
                <flux:text class="text-sm text-steel">Shotgun: exporte pedidos válidos (colunas DEAL TITLE, STATUS, CLIENT PRICE).</flux:text>
            @endif
            <flux:error name="importPlatform" />
            <flux:error name="importFile" />
            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="closeImportModal">Cancelar</flux:button>
                <flux:button variant="primary" type="submit">Importar</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showForm" class="max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Editar lote' : 'Novo lote' }}</flux:heading>
            <flux:input wire:model="name" label="Nome" placeholder="Pré-venda" />
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="price" label="Preço" placeholder="35,00" />
                <flux:input wire:model="quantity" type="number" min="0" label="Quantidade" />
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="goal" type="number" min="0" label="Meta de vendas" />
                <flux:input wire:model="sold" type="number" min="0" label="Já vendidos" />
            </div>
            <flux:input wire:model="payout" type="number" min="0" max="100" label="Repasse da bilheteria (%)" />
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="startsAt" type="datetime-local" label="Início das vendas" />
                <flux:input wire:model="endsAt" type="datetime-local" label="Fim das vendas" />
            </div>
            <div class="flex justify-between">
                @if ($editingId)
                    <flux:button type="button" variant="danger" wire:click="destroy({{ $editingId }})">Excluir</flux:button>
                @endif
                <flux:button variant="primary" type="submit">Salvar lote</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
