<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Artistas</flux:heading>
            <flux:text>O mesmo artista em vários eventos. O cachê de cada noite fica no booking, não aqui.</flux:text>
        </div>
        <flux:button variant="primary" wire:click="create">Novo artista</flux:button>
    </div>
    <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar pelo nome artístico" />
    @if ($artists->isEmpty())
        <x-empty-state title="Nenhum artista na casa ainda." body="Cadastre uma vez. Quando entrar num evento, o cachê vira custo daquele evento.">
            <flux:button variant="primary" wire:click="create">Adicionar artista</flux:button>
        </x-empty-state>
    @else
        <div class="tile-grid">
            @foreach ($artists as $artist)
                @php($total = (int) $artist->bookings->sum(fn ($booking) => $booking->budgetItem?->committedAmount() ?? 0))
                <x-tile type="button" wire:click="edit({{ $artist->id }})" size="lg">
                    <span class="tile-kicker">{{ $artist->agency ?: 'Artista' }}</span>
                    <span class="tile-title text-2xl">{{ $artist->stage_name }}</span>
                    <span class="tile-value"><x-money :cents="$total" /></span>
                    <span class="tile-meta">{{ $artist->bookings->pluck('event.name')->filter()->unique()->take(3)->join(', ') ?: 'Ainda sem eventos' }}</span>
                </x-tile>
            @endforeach
        </div>
    @endif
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
                    <flux:button type="button" variant="danger" wire:click="destroy({{ $editingId }})">Excluir</flux:button>
                @endif
                <flux:button variant="primary" type="submit">Salvar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
