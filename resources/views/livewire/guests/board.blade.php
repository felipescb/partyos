<div class="flex flex-col gap-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Convidados</flux:heading>
            <flux:text>Confirmados + acompanhantes entram no público estimado. Isso não mistura com ingresso vendido.</flux:text>
        </div>
        @if ($canEdit)
            <flux:button variant="primary" wire:click="create">Adicionar convidado</flux:button>
        @endif
    </div>
    <p class="text-sm">Confirmados, com acompanhantes: <span class="font-semibold tabular-nums">{{ $confirmed }}</span></p>
    <flux:input wire:model.live.debounce.300ms="search" label="Buscar" placeholder="Nome, e-mail, Instagram" />
    @if ($guests->isEmpty())
        <x-empty-state title="A lista ainda está vazia." body="VIP, imprensa, produção, amigos. O RSVP começa em convite não enviado.">
            @if ($canEdit)
                <flux:button variant="primary" wire:click="create">Adicionar convidado</flux:button>
            @endif
        </x-empty-state>
    @else
        <div class="tile-grid">
            @foreach ($guests as $guest)
                <x-tile as="div">
                    @if ($canEdit)
                        <button type="button" class="tile-fill" wire:click="edit({{ $guest->id }})" aria-label="Editar {{ $guest->name }}"></button>
                    @endif
                    <span class="tile-kicker">{{ $guest->category->label() }} · {{ $guest->rsvp_status->label() }}</span>
                    <span class="tile-title">{{ $guest->name }}</span>
                    <span class="tile-meta">{{ $guest->headcount() }} pessoas @if($guest->instagram) · {{ $guest->instagram }} @endif</span>
                    @if ($canEdit && $guest->rsvp_status !== \App\Enums\RsvpStatus::CheckedIn)
                        <button type="button" class="tile-corner" wire:click="checkIn({{ $guest->id }})">Check-in</button>
                    @elseif ($guest->checked_in_at)
                        <span class="tile-corner">{{ $guest->checked_in_at->format('H:i') }}</span>
                    @endif
                </x-tile>
            @endforeach
        </div>
        <div>{{ $guests->links() }}</div>
    @endif
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
                <flux:select wire:model="category" label="Categoria">
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
                    <flux:button type="button" variant="danger" wire:click="destroy({{ $editingId }})">Excluir</flux:button>
                @endif
                <flux:button variant="primary" type="submit">Salvar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
