@props([
    'event',
    'revenues',
    'canEdit' => false,
])

<article {{ $attributes->class(['broker-card', 'broker-revenue-sheet']) }} role="listitem">
    <header class="broker-cashflow-map-head">
        <div>
            <x-event-category-symbol category="financeiro" />
            <h2 class="broker-cashflow-map-title">Receitas adicionais</h2>
        </div>
        @if ($canEdit)
            <button type="button" class="broker-card-icon-btn" wire:click="create" aria-label="Nova receita" title="Nova receita">
                <flux:icon.plus variant="mini" class="size-4" />
            </button>
        @endif
    </header>

    <div class="broker-cost-scroll" tabindex="0" aria-label="Tabela de receitas adicionais">
        <div class="broker-cost-columns broker-revenue-columns" role="row">
            <span class="broker-task-notebook-sort broker-cost-cell broker-cost-cell-item">Descrição</span>
            <span class="broker-task-notebook-sort broker-cost-cell broker-cost-cell-category">Origem</span>
            <span class="broker-task-notebook-sort broker-cost-cell">Previsto</span>
            <span class="broker-task-notebook-sort broker-cost-cell">Entrou</span>
            <span class="broker-task-notebook-sort broker-cost-cell">Status</span>
            <span class="broker-task-notebook-sort broker-cost-cell">Data</span>
        </div>

        @forelse ($revenues as $revenue)
            <div
                @class([
                    'broker-cost-row',
                    'broker-revenue-row',
                    'broker-cost-cat-'.$revenue->category->colorSlug(),
                    'broker-cost-row-muted' => $revenue->status === \App\Enums\RevenueStatus::Cancelled,
                ])
                role="row"
                wire:key="revenue-{{ $revenue->id }}"
            >
                @if ($canEdit)
                    <button type="button" class="broker-revenue-row-hit" wire:click="edit({{ $revenue->id }})" aria-label="Editar {{ $revenue->description }}"></button>
                @endif
                <div class="broker-cost-cell broker-cost-cell-item" role="cell">
                    <span class="broker-task-notebook-read">{{ $revenue->description }}</span>
                </div>
                <div class="broker-cost-cell broker-cost-cell-category" role="cell">
                    <span class="broker-task-notebook-read">{{ $revenue->category->label() }}</span>
                </div>
                <div class="broker-cost-cell" role="cell">
                    <span class="broker-task-notebook-read"><x-money :cents="$revenue->expected_amount" :currency="$event->currency" /></span>
                </div>
                <div class="broker-cost-cell" role="cell">
                    <span class="broker-task-notebook-read"><x-money :cents="$revenue->actual_amount" :currency="$event->currency" /></span>
                </div>
                <div class="broker-cost-cell" role="cell">
                    <span class="broker-task-notebook-read">{{ $revenue->status->label() }}</span>
                </div>
                <div class="broker-cost-cell" role="cell">
                    <span class="broker-task-notebook-read">{{ $revenue->occurred_on?->format('d/m/Y') ?? '—' }}</span>
                </div>
            </div>
        @empty
            <div class="broker-task-notebook-empty">
                <p>Nenhuma receita além dos ingressos.</p>
                <p class="broker-task-notebook-empty-hint">Bar, patrocínio, porta e merch entram nesta tabela.</p>
            </div>
        @endforelse
    </div>
</article>
