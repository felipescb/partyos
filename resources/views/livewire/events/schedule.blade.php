<div class="broker-schedule-page">
    <header class="broker-cashflow-head">
        <a href="{{ route('events.show', $event) }}" wire:navigate class="broker-task-notebook-back">← Quadro</a>
        <div>
            <x-event-category-symbol category="quando" />
            <h1 class="broker-cashflow-title">Cronograma</h1>
            <p class="broker-cashflow-sub">{{ $event->name }} · o timeflow da festa</p>
        </div>
    </header>

    <div class="broker-grid broker-grid-event" role="list" aria-label="Quadro do cronograma">
        <article class="broker-grid-item broker-card broker-card-module" role="listitem">
            <x-event-category-symbol category="quando" label="Portas" />
            <span class="broker-card-name">Portas</span>
            <span class="broker-card-quote">{{ $flow['doors']['clock'] }}</span>
            <span class="broker-card-foot">{{ $flow['doors']['foot'] }}</span>
        </article>
        <article class="broker-grid-item broker-card broker-card-module" role="listitem">
            <x-event-category-symbol category="atencao" label="Fecha" />
            <span class="broker-card-name">Fecha</span>
            <span class="broker-card-quote">{{ $flow['close']['clock'] }}</span>
            <span class="broker-card-foot">{{ $flow['close']['foot'] }}</span>
        </article>
        <article class="broker-grid-item broker-card broker-card-module" role="listitem">
            <x-event-category-symbol category="ao-vivo" label="No ar" />
            <span class="broker-card-name">No ar</span>
            <span class="broker-card-quote">{{ $flow['span']['value'] }}</span>
            <span class="broker-card-foot">{{ $flow['span']['foot'] }}</span>
        </article>
        <article class="broker-grid-item broker-card broker-card-module" role="listitem">
            <x-event-category-symbol category="leitura" label="Blocos" />
            <span class="broker-card-name">Blocos</span>
            <span class="broker-card-quote">{{ $flow['blocks']['value'] }}</span>
            <span class="broker-card-foot">{{ $flow['blocks']['foot'] }}</span>
        </article>

        <x-event-schedule-flow :flow="$flow" :can-edit="$canEdit" class="broker-grid-item" />
    </div>

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
