@php
    $baseOf = function (object $item) use ($bases): string {
        $key = $item->applies_to === 'category' ? $item->revenue_category : $item->applies_to;

        return $bases[$key] ?? (string) $item->applies_to;
    };
@endphp

<div class="broker-grid broker-grid-event broker-grid-config broker-grid-config-pair" role="list" aria-label="Taxas e divisão">
    <article class="broker-grid-item broker-card broker-config-card" role="listitem">
        <x-event-category-symbol category="financeiro" />
        <h2 class="broker-card-name">Taxas</h2>
        <p class="broker-config-note">Percentuais saem da base escolhida, sem uma taxa incidir sobre a outra.</p>
        @if ($fees->isEmpty())
            <p class="broker-config-note">Nenhuma taxa. Bilheteria de plataforma, por exemplo, entra aqui.</p>
        @else
            <ul class="broker-config-adjustments">
                @foreach ($fees as $fee)
                    <li class="broker-config-adjustment">
                        <span class="broker-config-adjustment-main">
                            <span class="broker-config-adjustment-name">{{ $fee->name }}</span>
                            <span class="broker-config-adjustment-meta">{{ $baseOf($fee) }} · @if ($fee->kind->value === 'percent'){{ \App\Domain\Finance\Money::formatPercent($fee->basis_points ?? 0) }}@else<x-money :cents="$fee->amount ?? 0" :currency="$event->currency" />@endif</span>
                        </span>
                        @if ($canEdit)
                            <button type="button" class="broker-config-adjustment-remove" wire:click="deleteFee({{ $fee->id }})" wire:confirm="Remover esta taxa?" aria-label="Remover {{ $fee->name }}" title="Remover">
                                <flux:icon.trash variant="mini" class="size-3.5" />
                            </button>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
        @if ($canEdit)
            <form wire:submit="addFee" class="broker-config-fields">
                <flux:input wire:model="feeName" label="Nome" placeholder="Taxa da bilheteria" />
                <flux:select wire:model="feeApplies" label="Incide sobre">
                    @foreach ($bases as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                <div class="broker-config-fields-duo">
                    <flux:select wire:model="feeKind" label="Tipo">
                        <flux:select.option value="percent">Percentual</flux:select.option>
                        <flux:select.option value="fixed">Valor fixo</flux:select.option>
                    </flux:select>
                    <flux:input wire:model="feeValue" label="Valor" placeholder="10 ou 500,00" />
                </div>
                <flux:button type="submit" size="sm" variant="primary">Adicionar taxa</flux:button>
            </form>
        @endif
    </article>

    <article class="broker-grid-item broker-card broker-config-card" role="listitem">
        <x-event-category-symbol category="financeiro" />
        <h2 class="broker-card-name">Quem fica com o quê</h2>
        <p class="broker-config-note">A parte de cada um sai da receita escolhida, no acordo desta festa.</p>
        @if ($shares->isEmpty())
            <p class="broker-config-note">Nenhuma divisão. Exemplo: 25% da bilheteria para a casa, 75% para a produção.</p>
        @else
            <ul class="broker-config-adjustments">
                @foreach ($shares as $share)
                    <li class="broker-config-adjustment">
                        <span class="broker-config-adjustment-main">
                            <span class="broker-config-adjustment-name">{{ $share->beneficiary_name }}</span>
                            <span class="broker-config-adjustment-meta">{{ $baseOf($share) }} · @if ($share->kind->value === 'percent'){{ \App\Domain\Finance\Money::formatPercent($share->basis_points ?? 0) }}@else<x-money :cents="$share->amount ?? 0" :currency="$event->currency" />@endif</span>
                        </span>
                        @if ($canEdit)
                            <button type="button" class="broker-config-adjustment-remove" wire:click="deleteShare({{ $share->id }})" wire:confirm="Remover esta divisão?" aria-label="Remover {{ $share->beneficiary_name }}" title="Remover">
                                <flux:icon.trash variant="mini" class="size-3.5" />
                            </button>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
        @if ($canEdit)
            <form wire:submit="addShare" class="broker-config-fields">
                <flux:input wire:model="beneficiary" label="Quem recebe" placeholder="Casa, coletivo, sócio" />
                <flux:select wire:model="shareApplies" label="Sobre qual receita">
                    @foreach ($bases as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                <div class="broker-config-fields-duo">
                    <flux:select wire:model="shareKind" label="Tipo">
                        <flux:select.option value="percent">Percentual</flux:select.option>
                        <flux:select.option value="fixed">Valor fixo</flux:select.option>
                    </flux:select>
                    <flux:input wire:model="shareValue" label="Valor" placeholder="25" />
                </div>
                <flux:button type="submit" size="sm" variant="primary">Adicionar divisão</flux:button>
            </form>
        @endif
    </article>
</div>
