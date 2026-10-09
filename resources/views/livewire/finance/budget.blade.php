<div class="broker-task-notebook broker-cost-notebook">
    <header class="broker-task-notebook-head">
        <div class="broker-task-notebook-head-main">
            <a href="{{ route('events.show', $event) }}" wire:navigate class="broker-task-notebook-back">← Quadro</a>
            <div>
                <x-event-category-symbol category="financeiro" />
                <h1 class="broker-task-notebook-title">Custos</h1>
                <p class="broker-task-notebook-sub">{{ $event->name }}</p>
            </div>
        </div>
        @if ($canEdit)
            <button type="button" class="broker-card-icon-btn" wire:click="create" aria-label="Novo custo" title="Novo custo">
                <flux:icon.plus variant="mini" class="size-4" />
            </button>
        @endif
    </header>

    <section class="broker-cost-stats" aria-label="Resumo dos custos">
        @foreach ([
            ['label' => 'Estimado', 'cents' => $statement->estimatedCosts, 'tone' => 'leitura', 'foot' => 'O plano'],
            ['label' => 'Contratado', 'cents' => $statement->contractedCosts, 'tone' => 'equipe', 'foot' => 'O que fechou'],
            ['label' => 'Pago', 'cents' => $statement->paidCosts, 'tone' => 'ao-vivo', 'foot' => 'O que já saiu'],
            ['label' => 'Resta', 'cents' => $statement->remainingCosts, 'tone' => 'atencao', 'foot' => 'Ainda por pagar'],
        ] as $card)
            <article class="broker-cost-stat">
                <span class="broker-card-symbol broker-cat broker-cat-{{ $card['tone'] }}">{{ $card['label'] }}</span>
                <span class="broker-cost-stat-value"><x-money :cents="$card['cents']" :currency="$event->currency" /></span>
                <span class="broker-cost-stat-foot">{{ $card['foot'] }}</span>
            </article>
        @endforeach
    </section>

    <x-event-cost-grid
        :event="$event"
        :items="$items"
        :categories="$categories"
        :vendors="$vendors"
        :statuses="$statuses"
        :can-edit="$canEdit"
        :sort-column="$sortColumn"
        :sort-direction="$sortDirection"
    />

    <flux:modal wire:model="showForm" class="max-w-2xl">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Editar custo' : 'Novo custo' }}</flux:heading>
            <flux:input wire:model="description" label="Descrição" placeholder="Fotografia da festa" />
            <flux:input wire:model="detail" label="Tipo / nome" placeholder="Quem faz, ou o modelo" />
            <div class="grid gap-4 md:grid-cols-2">
                <flux:select wire:model="categoryId" label="Categoria" placeholder="Escolher">
                    <flux:select.option value="">Sem categoria</flux:select.option>
                    @foreach ($categories as $category)
                        <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="vendorId" label="Fornecedor" placeholder="Quem faz isso?">
                    <flux:select.option value="">Ainda sem fornecedor</flux:select.option>
                    @foreach ($vendors as $vendor)
                        <flux:select.option value="{{ $vendor->id }}">{{ $vendor->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <flux:input wire:model="newVendorName" label="Ou cadastrar fornecedor agora" placeholder="Nome" />
            <div class="grid gap-4 md:grid-cols-3">
                <flux:input wire:model="quantity" type="number" min="0" label="Quantidade" />
                <flux:input wire:model="unitAmount" label="Valor unitário" placeholder="8,00" />
                <flux:input wire:model="estimated" label="Total estimado" placeholder="1.000,00" />
            </div>
            <flux:input wire:model="contracted" label="Quanto ficou contratado?" placeholder="Deixe vazio se ainda não fechou" />
            <div class="grid gap-4 md:grid-cols-3">
                <flux:input wire:model="dueOn" type="date" label="Vencimento" />
                <flux:select wire:model="status" label="Status">
                    @foreach ($statuses as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="paymentMethod" label="Forma de pagamento" placeholder="PIX, transferência" />
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="responsibleName" label="Responsável pelo pagamento" />
                <flux:input wire:model="pix" label="PIX / CPF" />
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="invoiceNumber" label="Nota fiscal" />
                <flux:input wire:model="invoiceUrl" label="Link da nota" />
            </div>
            <flux:textarea wire:model="notes" label="Observação" rows="2" />
            <div class="flex justify-between gap-2">
                @if ($editingId)
                    <flux:button type="button" variant="danger" wire:click="destroy({{ $editingId }})">Excluir</flux:button>
                @else
                    <span></span>
                @endif
                <flux:button variant="primary" type="submit">Salvar</flux:button>
            </div>
        </form>

        @if ($editingId)
            <div class="mt-6 border-t border-line pt-4">
                <flux:heading>Pagamentos</flux:heading>
                @forelse ($editingPayments as $payment)
                    <div class="mt-2 flex items-center justify-between text-sm">
                        <span>{{ $payment->status->label() }} · {{ $payment->due_on?->format('d/m/Y') ?? 'sem data' }}</span>
                        <span class="flex items-center gap-3">
                            <x-money :cents="$payment->amount" :currency="$event->currency" />
                            @if ($canEdit)
                                <button type="button" wire:click="removePayment({{ $payment->id }})" class="font-medium text-brand underline">Remover</button>
                            @endif
                        </span>
                    </div>
                @empty
                    <p class="mt-2 text-base text-steel">Nenhum pagamento. O valor pago da linha é a soma daqui.</p>
                @endforelse
                @if ($canEdit)
                    <div class="mt-3 grid gap-3 md:grid-cols-4">
                        <flux:input wire:model="paymentAmount" label="Valor" placeholder="400,00" />
                        <flux:input wire:model="paymentDate" type="date" label="Data" />
                        <flux:select wire:model="paymentStatus" label="Situação">
                            <flux:select.option value="paid">Pago</flux:select.option>
                            <flux:select.option value="scheduled">Agendado</flux:select.option>
                        </flux:select>
                        <div class="flex items-end">
                            <flux:button type="button" wire:click="addPayment">Lançar</flux:button>
                        </div>
                    </div>
                    <flux:error name="paymentAmount" />
                @endif
            </div>
        @endif
    </flux:modal>
</div>
