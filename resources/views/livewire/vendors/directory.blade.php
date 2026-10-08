<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Fornecedores</flux:heading>
            <flux:text>Uma ficha só. Os eventos usam essa pessoa, sem copiar telefone para todo lado.</flux:text>
        </div>
        <flux:button variant="primary" wire:click="create">Novo fornecedor</flux:button>
    </div>
    <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar pelo nome" />
    @if ($vendors->isEmpty())
        <x-empty-state title="Você ainda não adicionou nenhum fornecedor." body="Som, foto, casa, segurança. Cadastre uma vez e use em todos os eventos.">
            <flux:button variant="primary" wire:click="create">Adicionar fornecedor</flux:button>
        </x-empty-state>
    @else
        <div class="tile-grid">
            @foreach ($vendors as $vendor)
                @php($total = (int) $vendor->budgetItems->sum(fn ($item) => $item->contracted_amount ?? $item->estimated_amount))
                <x-tile type="button" wire:click="edit({{ $vendor->id }})" size="lg">
                    <span class="tile-kicker">{{ $vendor->category ?: 'Sem categoria' }}</span>
                    <span class="tile-title text-2xl">{{ $vendor->name }}</span>
                    <span class="tile-value"><x-money :cents="$total" /></span>
                    <span class="tile-meta">{{ $vendor->budgetItems->pluck('event.name')->filter()->unique()->take(3)->join(', ') ?: 'Nenhum evento ainda' }}</span>
                </x-tile>
            @endforeach
        </div>
    @endif
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
                    <flux:button type="button" variant="danger" wire:click="destroy({{ $editingId }})">Excluir</flux:button>
                @endif
                <flux:button variant="primary" type="submit">Salvar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
