<div class="broker-revenue-page">
    <header class="broker-cashflow-head">
        <a href="{{ route('events.show', $event) }}" wire:navigate class="broker-task-notebook-back">← Quadro</a>
        <div>
            <x-event-category-symbol category="financeiro" />
            <h1 class="broker-cashflow-title">Receitas</h1>
            <p class="broker-cashflow-sub">{{ $event->name }} · receitas adicionais além dos ingressos</p>
        </div>
    </header>

    <div class="broker-grid broker-grid-event broker-grid-revenue" role="list" aria-label="Quadro das receitas extras">
        @foreach ([
            ['label' => 'Previsto', 'tone' => 'leitura', 'cents' => $summary['expected'], 'foot' => 'Receitas adicionais além dos ingressos'],
            ['label' => 'Entrou', 'tone' => 'ao-vivo', 'cents' => $summary['actual'], 'foot' => 'Já no caixa'],
            ['label' => 'Falta', 'tone' => 'atencao', 'cents' => $summary['remaining'], 'foot' => 'Ainda por entrar'],
        ] as $card)
            <article class="broker-grid-item broker-card broker-card-module" role="listitem">
                <x-event-category-symbol :category="$card['tone']" :label="$card['label']" />
                <span class="broker-card-name">{{ $card['label'] }}</span>
                <span class="broker-card-quote"><x-money :cents="$card['cents']" :currency="$event->currency" /></span>
                <span class="broker-card-foot">{{ $card['foot'] }}</span>
            </article>
        @endforeach

        <article class="broker-grid-item broker-card broker-card-module" role="listitem">
            <x-event-category-symbol category="financeiro" label="Linhas" />
            <span class="broker-card-name">Linhas</span>
            <span class="broker-card-quote">{{ $summary['count'] }}</span>
            <span class="broker-card-foot">{{ $summary['count'] === 1 ? 'Receita extra' : 'Receitas extras' }}</span>
        </article>

        <x-event-revenue-table
            :event="$event"
            :revenues="$revenues"
            :can-edit="$canEdit"
            class="broker-grid-item"
        />
    </div>

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
