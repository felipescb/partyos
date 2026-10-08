<div class="flex flex-col gap-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Custos</flux:heading>
            <flux:text>Estimado é o plano. Contratado é o que você fechou. Pago é o que já saiu.</flux:text>
        </div>
        @if ($canEdit)
            <flux:button variant="primary" wire:click="create">Adicionar custo</flux:button>
        @endif
    </div>

    <section class="tile-grid">
        <x-tile as="div" size="sm"><span class="tile-kicker">Estimado</span><span class="tile-value"><x-money :cents="$statement->estimatedCosts" :currency="$event->currency" /></span></x-tile>
        <x-tile as="div" size="sm"><span class="tile-kicker">Contratado</span><span class="tile-value"><x-money :cents="$statement->contractedCosts" :currency="$event->currency" /></span></x-tile>
        <x-tile as="div" size="sm"><span class="tile-kicker">Pago</span><span class="tile-value"><x-money :cents="$statement->paidCosts" :currency="$event->currency" /></span></x-tile>
        <x-tile as="div" size="sm"><span class="tile-kicker">Resta</span><span class="tile-value"><x-money :cents="$statement->remainingCosts" :currency="$event->currency" /></span></x-tile>
    </section>

    @if ($items->isEmpty())
        <x-empty-state title="Você ainda não adicionou nenhum custo." body="Comece pelo que já sabe: local, som, artistas. Dá para ajustar depois.">
            @if ($canEdit)
                <flux:button variant="primary" wire:click="create">Adicionar custo</flux:button>
            @endif
        </x-empty-state>
    @else
        <div class="tile-grid">
            @foreach ($items as $item)
                @php($paid = (int) $item->payments->where('status', \App\Enums\PaymentStatus::Paid)->sum('amount'))
                @php($committed = $item->committedAmount())
                @if ($canEdit)
                    <button type="button" class="tile" wire:click="edit({{ $item->id }})">
                @else
                    <div class="tile">
                @endif
                    <span class="tile-kicker">{{ $item->category?->name ?? 'Sem categoria' }} · {{ $item->status->label() }}</span>
                    <span class="tile-title">{{ $item->description }}</span>
                    <span class="tile-value"><x-money :cents="$committed" :currency="$event->currency" /></span>
                    <span class="tile-meta">
                        Pago <x-money :cents="$paid" :currency="$event->currency" />
                        · resta <x-money :cents="$committed - $paid" :currency="$event->currency" />
                        @if ($item->vendor) · {{ $item->vendor->name }} @else · sem fornecedor @endif
                    </span>
                @if ($canEdit)
                    </button>
                @else
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    <flux:modal wire:model="showForm" class="max-w-2xl">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Editar custo' : 'Novo custo' }}</flux:heading>
            <flux:input wire:model="description" label="Descrição" placeholder="Fotografia da festa" />
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
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="estimated" label="Quanto você espera gastar?" placeholder="1.000,00" />
                <flux:input wire:model="contracted" label="Quanto ficou contratado?" placeholder="Deixe vazio se ainda não fechou" />
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                <flux:input wire:model="dueOn" type="date" label="Vencimento" />
                <flux:select wire:model="status" label="Status">
                    @foreach ($statuses as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="paymentMethod" label="Forma de pagamento" placeholder="PIX, transferência" />
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
            <div class="mt-6 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:heading>Pagamentos</flux:heading>
                @forelse ($editingPayments as $payment)
                    <div class="mt-2 flex items-center justify-between text-sm">
                        <span>{{ $payment->status->label() }} · {{ $payment->due_on?->format('d/m/Y') ?? 'sem data' }}</span>
                        <span class="flex items-center gap-3">
                            <x-money :cents="$payment->amount" :currency="$event->currency" />
                            @if ($canEdit)
                                <button type="button" wire:click="removePayment({{ $payment->id }})" class="text-zinc-500 underline">Remover</button>
                            @endif
                        </span>
                    </div>
                @empty
                    <p class="mt-2 text-sm text-zinc-500">Nenhum pagamento. O valor pago da linha é a soma daqui.</p>
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
