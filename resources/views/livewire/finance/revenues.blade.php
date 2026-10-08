<div class="flex flex-col gap-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Receitas</flux:heading>
            <flux:text>Ingressos ficam na aba de lotes. Aqui entra o resto: bar, patrocínio, porta, merch.</flux:text>
        </div>
        @if ($canEdit)
            <flux:button variant="primary" wire:click="create">Adicionar receita</flux:button>
        @endif
    </div>
    @if ($revenues->isEmpty())
        <x-empty-state title="Nenhuma receita além dos ingressos." body="Se o bar, um patrocinador ou a porta entram no caixa, registre aqui.">
            @if ($canEdit)
                <flux:button variant="primary" wire:click="create">Adicionar receita</flux:button>
            @endif
        </x-empty-state>
    @else
        <div class="tile-grid">
            @foreach ($revenues as $revenue)
                <x-tile type="button" wire:click="edit({{ $revenue->id }})">
                    <span class="tile-kicker">{{ $revenue->category->label() }} · {{ $revenue->status->label() }}</span>
                    <span class="tile-title">{{ $revenue->description }}</span>
                    <span class="tile-value"><x-money :cents="$revenue->actual_amount" :currency="$event->currency" /></span>
                    <span class="tile-meta">Previsto <x-money :cents="$revenue->expected_amount" :currency="$event->currency" /></span>
                </x-tile>
            @endforeach
        </div>
    @endif
    <flux:modal wire:model="showForm" class="max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Editar receita' : 'Nova receita' }}</flux:heading>
            <flux:select wire:model="category" label="Origem">
                @foreach ($categories as $category)
                    <flux:select.option value="{{ $category->value }}">{{ $category->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="description" label="Descrição" />
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="expected" label="Quanto você espera receber?" />
                <flux:input wire:model="actual" label="Quanto já entrou?" />
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <flux:select wire:model="status" label="Status">
                    @foreach ($statuses as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="occurredOn" type="date" label="Data" />
            </div>
            <flux:textarea wire:model="notes" label="Observação" rows="2" />
            <div class="flex justify-between">
                @if ($editingId)
                    <flux:button type="button" variant="danger" wire:click="destroy({{ $editingId }})">Excluir</flux:button>
                @endif
                <flux:button variant="primary" type="submit">Salvar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
