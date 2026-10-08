<div class="flex flex-col gap-5">
    <div>
        <flux:heading size="xl">Taxas e divisão</flux:heading>
        <flux:text>Cada evento tem o seu acordo. Percentuais saem da base escolhida, sem um incidir sobre o outro.</flux:text>
    </div>
    <section class="grid gap-8 lg:grid-cols-2">
        <div>
            <flux:heading size="lg">Taxas</flux:heading>
            @forelse ($fees as $fee)
                <div class="mt-3 flex items-center justify-between text-sm">
                    <span>{{ $fee->name }} · {{ $bases[$fee->applies_to === 'category' ? $fee->revenue_category : $fee->applies_to] ?? $fee->applies_to }} · {{ $fee->kind->value === 'percent' ? \App\Domain\Finance\Money::formatPercent($fee->basis_points ?? 0) : '' }}@if($fee->kind->value === 'fixed')<x-money :cents="$fee->amount ?? 0" :currency="$event->currency" />@endif</span>
                    @if ($canEdit)
                        <button type="button" wire:click="deleteFee({{ $fee->id }})" class="text-zinc-500 underline">Remover</button>
                    @endif
                </div>
            @empty
                <p class="mt-2 text-sm text-zinc-500">Nenhuma taxa. Bilheteria de plataforma, por exemplo, entra aqui.</p>
            @endforelse
            @if ($canEdit)
                <form wire:submit="addFee" class="mt-4 space-y-3">
                    <flux:input wire:model="feeName" label="Nome" placeholder="Taxa da bilheteria" />
                    <flux:select wire:model="feeApplies" label="Incide sobre">
                        @foreach ($bases as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div class="grid grid-cols-2 gap-3">
                        <flux:select wire:model="feeKind" label="Tipo">
                            <flux:select.option value="percent">Percentual</flux:select.option>
                            <flux:select.option value="fixed">Valor fixo</flux:select.option>
                        </flux:select>
                        <flux:input wire:model="feeValue" label="Valor" placeholder="10 ou 500,00" />
                    </div>
                    <flux:button type="submit">Adicionar taxa</flux:button>
                </form>
            @endif
        </div>
        <div>
            <flux:heading size="lg">Quem fica com o quê</flux:heading>
            @forelse ($shares as $share)
                <div class="mt-3 flex items-center justify-between text-sm">
                    <span>{{ $share->beneficiary_name }} · {{ $bases[$share->applies_to === 'category' ? $share->revenue_category : $share->applies_to] ?? $share->applies_to }} · {{ $share->kind->value === 'percent' ? \App\Domain\Finance\Money::formatPercent($share->basis_points ?? 0) : '' }}@if($share->kind->value === 'fixed')<x-money :cents="$share->amount ?? 0" :currency="$event->currency" />@endif</span>
                    @if ($canEdit)
                        <button type="button" wire:click="deleteShare({{ $share->id }})" class="text-zinc-500 underline">Remover</button>
                    @endif
                </div>
            @empty
                <p class="mt-2 text-sm text-zinc-500">Nenhuma divisão. Exemplo: 25% da bilheteria para a casa, 75% para a produção.</p>
            @endforelse
            @if ($canEdit)
                <form wire:submit="addShare" class="mt-4 space-y-3">
                    <flux:input wire:model="beneficiary" label="Quem recebe" placeholder="Casa, coletivo, sócio" />
                    <flux:select wire:model="shareApplies" label="Sobre qual receita">
                        @foreach ($bases as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div class="grid grid-cols-2 gap-3">
                        <flux:select wire:model="shareKind" label="Tipo">
                            <flux:select.option value="percent">Percentual</flux:select.option>
                            <flux:select.option value="fixed">Valor fixo</flux:select.option>
                        </flux:select>
                        <flux:input wire:model="shareValue" label="Valor" placeholder="25" />
                    </div>
                    <flux:button type="submit">Adicionar divisão</flux:button>
                </form>
            @endif
        </div>
    </section>
</div>
