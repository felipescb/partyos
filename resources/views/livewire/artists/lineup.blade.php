<div class="flex flex-col gap-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Lineup</flux:heading>
            <flux:text>O artista é da produtora. O cachê deste evento vira um custo, uma vez só.</flux:text>
        </div>
        @if ($canEdit)
            <flux:button variant="primary" wire:click="create">Adicionar artista</flux:button>
        @endif
    </div>
    @if ($bookings->isEmpty())
        <x-empty-state title="Nenhum artista neste evento." body="Escolha alguém que você já trabalhou ou cadastre agora. O cachê aparece em Custos.">
            @if ($canEdit)
                <flux:button variant="primary" wire:click="create">Adicionar artista</flux:button>
            @endif
        </x-empty-state>
    @else
        <div class="tile-grid">
            @foreach ($bookings as $booking)
                <x-tile type="button" wire:click="edit({{ $booking->id }})" size="lg">
                    <span class="tile-kicker">{{ $booking->status->label() }}</span>
                    <span class="tile-title text-2xl">{{ $booking->artist->stage_name }}</span>
                    @if ($booking->budgetItem)
                        <span class="tile-value"><x-money :cents="$booking->budgetItem->committedAmount()" :currency="$event->currency" /></span>
                    @endif
                    <span class="tile-meta">
                        {{ $booking->starts_at?->format('H:i') ?? 'Horário a definir' }}@if($booking->ends_at)–{{ $booking->ends_at->format('H:i') }}@endif
                    </span>
                </x-tile>
            @endforeach
        </div>
    @endif
    <flux:modal wire:model="showForm" class="max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Editar booking' : 'Novo booking' }}</flux:heading>
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
                    <flux:button type="button" variant="danger" wire:click="destroy({{ $editingId }})">Remover do evento</flux:button>
                @endif
                <flux:button variant="primary" type="submit">Salvar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
