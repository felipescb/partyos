<div class="flex flex-col gap-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Ingressos</flux:heading>
            <flux:text>A meta de cada lote alimenta a receita prevista e o ponto de empate. O vendido é o que já aconteceu.</flux:text>
        </div>
        @if ($canEdit)
            <flux:button variant="primary" wire:click="create">Novo lote</flux:button>
        @endif
    </div>
    <section class="tile-grid">
        <x-tile as="div" size="sm"><span class="tile-kicker">Vendidos</span><span class="tile-value">{{ $sold }}</span></x-tile>
        <x-tile as="div" size="sm"><span class="tile-kicker">Meta</span><span class="tile-value">{{ $goal }}</span></x-tile>
        <x-tile as="div" size="sm"><span class="tile-kicker">Receita gerada</span><span class="tile-value"><x-money :cents="$revenue" :currency="$event->currency" /></span></x-tile>
    </section>
    @if ($tiers->isEmpty())
        <x-empty-state title="Nenhum lote ainda." body="Pré-venda, primeiro lote, porta. Sem isso o PartyOS não sabe quantas pessoas você precisa vender.">
            @if ($canEdit)
                <flux:button variant="primary" wire:click="create">Criar lote</flux:button>
            @endif
        </x-empty-state>
    @else
        <div class="tile-grid">
            @foreach ($tiers as $tier)
                <x-tile type="button" wire:click="edit({{ $tier->id }})" size="lg">
                    <span class="tile-kicker">{{ $tier->sold_quantity }} vendidos · meta {{ $tier->goal }}</span>
                    <span class="tile-title">{{ $tier->name }}</span>
                    <span class="tile-value"><x-money :cents="$tier->price" :currency="$event->currency" /></span>
                    <span class="tile-meta">Receita <x-money :cents="$tier->revenue()" :currency="$event->currency" /> · restam {{ $tier->remaining() }}</span>
                </x-tile>
            @endforeach
        </div>
    @endif
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
