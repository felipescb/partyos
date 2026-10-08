<div class="flex flex-col gap-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Cenários</flux:heading>
            <flux:text>Ruim, esperado, ótimo. O PartyOS recalcula receita, taxas, divisão e custos. Nada aqui altera o evento de verdade.</flux:text>
        </div>
        @if ($canEdit)
            @if ($scenarios->isEmpty())
                <flux:button variant="primary" wire:click="generate">Gerar cenários</flux:button>
            @else
                <flux:button wire:click="$set('confirmReplace', true)">Refazer a partir de agora</flux:button>
            @endif
        @endif
    </div>
    @if ($scenarios->isEmpty())
        <x-empty-state title="Ainda não há cenários." body="Gerar cria Ruim, Conservador, Esperado, OK e Ótimo a partir da meta de ingressos e dos custos atuais.">
            @if ($canEdit)
                <flux:button variant="primary" wire:click="generate">Gerar cenários</flux:button>
            @endif
        </x-empty-state>
    @else
        <div class="tile-grid">
            @foreach ($scenarios as $scenario)
                @php($projection = $projections[$scenario->id])
                <x-tile type="button" size="lg" wire:click="select({{ $scenario->id }})" :current="$scenarioId === $scenario->id">
                    <span class="tile-kicker">{{ $scenario->kind->label() }}</span>
                    <span class="tile-value">{{ $projection->attendance }}</span>
                    <span class="tile-title">pessoas</span>
                    <span class="tile-meta">
                        Receita <x-money :cents="$projection->gross" :currency="$event->currency" />
                        · resultado <x-money :cents="$projection->result" :currency="$event->currency" />
                    </span>
                </x-tile>
            @endforeach
        </div>
    @endif

    @if ($scenarioId && $canEdit)
        <form wire:submit="save" class="space-y-4 rounded-2xl border border-line bg-canvas p-4 shadow-whisper">
            <flux:heading size="lg">Ajustar cenário</flux:heading>
            <div class="flex flex-wrap items-end gap-3">
                <flux:input wire:model="attendance" type="number" min="0" label="Público" class="max-w-xs" />
                <flux:button type="button" wire:click="applyAttendance">Distribuir nos lotes</flux:button>
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                @foreach ($tiers as $tier)
                    <flux:input wire:model="quantities.{{ $tier->id }}" type="number" min="0" :label="$tier->name.' · vendas'" />
                @endforeach
            </div>
            @if ($revenues->isNotEmpty())
                <flux:heading>Outras receitas</flux:heading>
                <div class="grid gap-3 md:grid-cols-2">
                    @foreach ($revenues as $revenue)
                        <flux:input wire:model="revenueAmounts.{{ $revenue->id }}" :label="$revenue->description" />
                    @endforeach
                </div>
            @endif
            @if ($costs->isNotEmpty())
                <flux:heading>Custos neste cenário</flux:heading>
                <div class="grid gap-3 md:grid-cols-2">
                    @foreach ($costs as $cost)
                        <flux:input wire:model="costAmounts.{{ $cost->id }}" :label="$cost->description" />
                    @endforeach
                </div>
            @endif
            <flux:button variant="primary" type="submit">Salvar cenário</flux:button>
        </form>
    @endif

    <flux:modal wire:model="confirmReplace" class="max-w-md">
        <flux:heading size="lg">Refazer os cenários?</flux:heading>
        <flux:text class="mt-2">Isso substitui os números editados pelos dados atuais do evento.</flux:text>
        <div class="mt-6 flex justify-end gap-2">
            <flux:button type="button" wire:click="$set('confirmReplace', false)">Cancelar</flux:button>
            <flux:button variant="danger" type="button" wire:click="generate(true)">Refazer</flux:button>
        </div>
    </flux:modal>
</div>
