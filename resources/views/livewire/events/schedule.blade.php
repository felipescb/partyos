<div class="flex flex-col gap-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Cronograma</flux:heading>
            <flux:text>Do load-in ao último parafuso. No dia, é esta lista que importa.</flux:text>
        </div>
        @if ($canEdit)
            <flux:button variant="primary" wire:click="create">Novo horário</flux:button>
        @endif
    </div>
    @if ($items->isEmpty())
        <x-empty-state title="O dia ainda não tem horários." body="Montagem, soundcheck, abertura, encerramento. Um template já sugere a espinha dorsal.">
            @if ($canEdit)
                <flux:button variant="primary" wire:click="create">Novo horário</flux:button>
            @endif
        </x-empty-state>
    @else
        <div class="tile-grid">
            @foreach ($items as $item)
                <x-tile type="button" wire:click="edit({{ $item->id }})">
                    <span class="tile-kicker">{{ $item->location ?: 'Sem lugar' }}</span>
                    <span class="tile-value">{{ $item->starts_at?->format('H:i') ?? '—' }}</span>
                    <span class="tile-title">{{ $item->title }}</span>
                    <span class="tile-meta">{{ $item->duration_minutes ? $item->duration_minutes.' min' : 'Sem duração' }}</span>
                </x-tile>
            @endforeach
        </div>
    @endif
    <flux:modal wire:model="showForm" class="max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Editar horário' : 'Novo horário' }}</flux:heading>
            <flux:input wire:model="title" label="O que acontece" placeholder="Soundcheck" />
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="startsAt" type="datetime-local" label="Começa" />
                <flux:input wire:model="duration" type="number" min="1" label="Duração (min)" />
            </div>
            <flux:input wire:model="location" label="Onde" placeholder="Palco, portaria, bar" />
            <flux:select wire:model="vendorId" label="Fornecedor" placeholder="Nenhum">
                <flux:select.option value="">Nenhum</flux:select.option>
                @foreach ($vendors as $vendor)
                    <flux:select.option value="{{ $vendor->id }}">{{ $vendor->name }}</flux:select.option>
                @endforeach
            </flux:select>
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
