@props([
    'event',
    'items',
    'categories',
    'vendors',
    'statuses',
    'canEdit' => false,
    'sortColumn' => 'sort_order',
    'sortDirection' => 'asc',
])

<div {{ $attributes->class(['broker-cost-sheet']) }} role="region" aria-label="Planilha de custos">
    <div class="broker-cost-scroll">
        <div class="broker-cost-columns" role="row">
            @foreach ([
                'description' => 'Item',
                'cost_category_id' => 'Categoria',
                'detail' => 'Tipo / nome',
                'vendor_id' => 'Fornecedor',
                'quantity' => 'Quantidade',
                'unit_amount' => 'Unitário',
                'estimated_amount' => 'Estimado',
                'contracted_amount' => 'Contratado',
            ] as $column => $label)
                <button
                    type="button"
                    @class(['broker-task-notebook-sort', 'broker-cost-cell', 'broker-cost-cell-item' => $column === 'description', 'broker-cost-cell-category' => $column === 'cost_category_id', 'broker-task-notebook-sort-active' => $sortColumn === $column])
                    wire:click="sort('{{ $column }}')"
                    aria-sort="{{ $sortColumn === $column ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                >
                    <span>{{ $label }}</span>
                    @if ($sortColumn === $column)
                        <flux:icon.chevron-up variant="micro" @class(['broker-task-notebook-sort-icon', 'broker-task-notebook-sort-icon-desc' => $sortDirection === 'desc']) />
                    @endif
                </button>
            @endforeach
            <span class="broker-task-notebook-sort broker-cost-cell">Pago</span>
            @foreach ([
                'status' => 'Status',
                'due_on' => 'Vencimento',
                'payment_method' => 'Pagamento',
                'responsible_name' => 'Responsável',
                'pix' => 'PIX / CPF',
                'invoice_number' => 'NF',
                'invoice_url' => 'Link NF',
                'notes' => 'Observação',
            ] as $column => $label)
                <button
                    type="button"
                    @class(['broker-task-notebook-sort', 'broker-cost-cell', 'broker-task-notebook-sort-active' => $sortColumn === $column])
                    wire:click="sort('{{ $column }}')"
                    aria-sort="{{ $sortColumn === $column ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                >
                    <span>{{ $label }}</span>
                    @if ($sortColumn === $column)
                        <flux:icon.chevron-up variant="micro" @class(['broker-task-notebook-sort-icon', 'broker-task-notebook-sort-icon-desc' => $sortDirection === 'desc']) />
                    @endif
                </button>
            @endforeach
            <span class="broker-cost-cell broker-cost-cell-actions" aria-hidden="true"></span>
        </div>

        <div class="broker-cost-list" role="list">
            @forelse ($items as $item)
                @php($paid = (int) $item->payments->where('status', \App\Enums\PaymentStatus::Paid)->sum('amount'))
                <div
                    @class([
                        'broker-cost-row',
                        'broker-cost-row-muted' => $item->status === \App\Enums\CostStatus::Cancelled,
                        'broker-cost-cat-'.($item->category->slug ?? 'none'),
                    ])
                    role="listitem"
                    wire:key="cost-row-{{ $item->id }}"
                >
                    <div class="broker-cost-cell broker-cost-cell-item">
                        @if ($canEdit)
                            <input type="text" class="broker-task-notebook-input broker-task-notebook-input-title" value="{{ $item->description }}" wire:blur="updateCost({{ $item->id }}, 'description', $event.target.value)" aria-label="Item" />
                        @else
                            <span class="broker-task-notebook-read">{{ $item->description }}</span>
                        @endif
                    </div>
                    <div class="broker-cost-cell broker-cost-cell-category">
                        @if ($canEdit)
                            <select class="broker-task-notebook-select" wire:change="updateCost({{ $item->id }}, 'category_id', $event.target.value)" aria-label="Categoria">
                                <option value="">Sem categoria</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected($item->cost_category_id === $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        @else
                            <span class="broker-task-notebook-read">{{ $item->category?->name ?? '—' }}</span>
                        @endif
                    </div>
                    <div class="broker-cost-cell">
                        @if ($canEdit)
                            <input type="text" class="broker-task-notebook-input" value="{{ $item->detail }}" wire:blur="updateCost({{ $item->id }}, 'detail', $event.target.value)" aria-label="Tipo / nome" />
                        @else
                            <span class="broker-task-notebook-read">{{ $item->detail ?: '—' }}</span>
                        @endif
                    </div>
                    <div class="broker-cost-cell">
                        @if ($canEdit)
                            <select class="broker-task-notebook-select" wire:change="updateCost({{ $item->id }}, 'vendor_id', $event.target.value)" aria-label="Fornecedor">
                                <option value="">Sem fornecedor</option>
                                @foreach ($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @selected($item->vendor_id === $vendor->id)>{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                        @else
                            <span class="broker-task-notebook-read">{{ $item->vendor?->name ?? '—' }}</span>
                        @endif
                    </div>
                    <div class="broker-cost-cell">
                        @if ($canEdit)
                            <input type="number" min="0" class="broker-task-notebook-input broker-cost-input-number" value="{{ $item->quantity }}" wire:change="updateCost({{ $item->id }}, 'quantity', $event.target.value)" aria-label="Quantidade" />
                        @else
                            <span class="broker-task-notebook-read">{{ $item->quantity }}</span>
                        @endif
                    </div>
                    <div class="broker-cost-cell">
                        @if ($canEdit)
                            <input type="text" inputmode="decimal" class="broker-task-notebook-input broker-cost-input-money" value="{{ $item->unit_amount === null ? '' : \App\Domain\Finance\Money::input($item->unit_amount) }}" wire:blur="updateCost({{ $item->id }}, 'unit_amount', $event.target.value)" aria-label="Unitário" placeholder="0,00" />
                        @else
                            <span class="broker-task-notebook-read">{{ $item->unit_amount === null ? '—' : \App\Domain\Finance\Money::format($item->unit_amount, $event->currency) }}</span>
                        @endif
                    </div>
                    <div class="broker-cost-cell">
                        @if ($canEdit)
                            <input type="text" inputmode="decimal" class="broker-task-notebook-input broker-cost-input-money" value="{{ \App\Domain\Finance\Money::input($item->estimated_amount) }}" wire:blur="updateCost({{ $item->id }}, 'estimated_amount', $event.target.value)" aria-label="Estimado" />
                        @else
                            <span class="broker-task-notebook-read"><x-money :cents="$item->estimated_amount" :currency="$event->currency" /></span>
                        @endif
                    </div>
                    <div class="broker-cost-cell">
                        @if ($canEdit)
                            <input type="text" inputmode="decimal" class="broker-task-notebook-input broker-cost-input-money" value="{{ $item->contracted_amount === null ? '' : \App\Domain\Finance\Money::input($item->contracted_amount) }}" wire:blur="updateCost({{ $item->id }}, 'contracted_amount', $event.target.value)" aria-label="Contratado" placeholder="—" />
                        @else
                            <span class="broker-task-notebook-read">{{ $item->contracted_amount === null ? '—' : \App\Domain\Finance\Money::format($item->contracted_amount, $event->currency) }}</span>
                        @endif
                    </div>
                    <div class="broker-cost-cell">
                        <span class="broker-task-notebook-read broker-cost-paid"><x-money :cents="$paid" :currency="$event->currency" /></span>
                    </div>
                    <div class="broker-cost-cell">
                        @if ($canEdit)
                            <select class="broker-task-notebook-select" wire:change="updateCost({{ $item->id }}, 'status', $event.target.value)" aria-label="Status">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}" @selected($item->status === $status)>{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        @else
                            <span class="broker-task-notebook-read">{{ $item->status->label() }}</span>
                        @endif
                    </div>
                    <div class="broker-cost-cell">
                        @if ($canEdit)
                            <input type="date" class="broker-task-notebook-input" value="{{ $item->due_on?->toDateString() }}" wire:change="updateCost({{ $item->id }}, 'due_on', $event.target.value)" aria-label="Vencimento" />
                        @else
                            <span class="broker-task-notebook-read">{{ $item->due_on?->format('d/m/Y') ?? '—' }}</span>
                        @endif
                    </div>
                    <div class="broker-cost-cell">
                        @if ($canEdit)
                            <input type="text" class="broker-task-notebook-input" value="{{ $item->payment_method }}" wire:blur="updateCost({{ $item->id }}, 'payment_method', $event.target.value)" aria-label="Pagamento" />
                        @else
                            <span class="broker-task-notebook-read">{{ $item->payment_method ?: '—' }}</span>
                        @endif
                    </div>
                    <div class="broker-cost-cell">
                        @if ($canEdit)
                            <input type="text" class="broker-task-notebook-input" value="{{ $item->responsible_name }}" wire:blur="updateCost({{ $item->id }}, 'responsible_name', $event.target.value)" aria-label="Responsável" />
                        @else
                            <span class="broker-task-notebook-read">{{ $item->responsible_name ?: '—' }}</span>
                        @endif
                    </div>
                    <div class="broker-cost-cell">
                        @if ($canEdit)
                            <input type="text" class="broker-task-notebook-input" value="{{ $item->pix }}" wire:blur="updateCost({{ $item->id }}, 'pix', $event.target.value)" aria-label="PIX / CPF" />
                        @else
                            <span class="broker-task-notebook-read">{{ $item->pix ?: '—' }}</span>
                        @endif
                    </div>
                    <div class="broker-cost-cell">
                        @if ($canEdit)
                            <input type="text" class="broker-task-notebook-input" value="{{ $item->invoice_number }}" wire:blur="updateCost({{ $item->id }}, 'invoice_number', $event.target.value)" aria-label="NF" />
                        @else
                            <span class="broker-task-notebook-read">{{ $item->invoice_number ?: '—' }}</span>
                        @endif
                    </div>
                    <div class="broker-cost-cell">
                        @if ($canEdit)
                            <input type="text" class="broker-task-notebook-input" value="{{ $item->invoice_url }}" wire:blur="updateCost({{ $item->id }}, 'invoice_url', $event.target.value)" aria-label="Link NF" />
                        @elseif ($item->invoice_url)
                            <a href="{{ $item->invoice_url }}" class="broker-task-notebook-read broker-cost-link" target="_blank" rel="noopener">Abrir</a>
                        @else
                            <span class="broker-task-notebook-read">—</span>
                        @endif
                    </div>
                    <div class="broker-cost-cell">
                        @if ($canEdit)
                            <input type="text" class="broker-task-notebook-input" value="{{ $item->notes }}" wire:blur="updateCost({{ $item->id }}, 'notes', $event.target.value)" aria-label="Observação" />
                        @else
                            <span class="broker-task-notebook-read">{{ $item->notes ?: '—' }}</span>
                        @endif
                    </div>
                    <div class="broker-cost-cell broker-cost-cell-actions">
                        <button type="button" class="broker-task-notebook-icon-btn" wire:click="edit({{ $item->id }})" aria-label="Pagamentos de {{ $item->description }}">
                            <flux:icon.pencil-square variant="micro" class="size-4" />
                        </button>
                        @if ($canEdit)
                            <button type="button" class="broker-task-notebook-icon-btn broker-task-notebook-icon-btn-danger" wire:click="destroy({{ $item->id }})" wire:confirm="Excluir este custo?" aria-label="Excluir {{ $item->description }}">
                                <flux:icon.trash variant="micro" class="size-4" />
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="broker-task-notebook-empty">
                    <p>Nenhum custo ainda.</p>
                    <p class="broker-task-notebook-empty-hint">Escreva o primeiro item na linha de baixo.</p>
                </div>
            @endforelse
        </div>
    </div>

    @if ($canEdit)
        <form wire:submit="createFromDraft" class="broker-task-notebook-compose broker-cost-compose">
            <input
                type="text"
                class="broker-task-notebook-input broker-task-notebook-input-title"
                wire:model="newDescription"
                placeholder="Novo custo… Enter para adicionar"
                aria-label="Novo custo"
            />
            <button type="submit" class="broker-card-icon-btn" aria-label="Adicionar custo" title="Adicionar">
                <flux:icon.plus variant="mini" class="size-4" />
            </button>
            <flux:error name="newDescription" />
        </form>
    @endif
</div>
