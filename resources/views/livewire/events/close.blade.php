<div class="flex flex-col gap-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Fechamento</flux:heading>
            <flux:text>O que você planejou contra o que aconteceu. Receita líquida já desconta taxa e divisão.</flux:text>
        </div>
        @if ($canEdit && $event->status !== \App\Enums\EventStatus::Finished)
            <flux:button variant="primary" wire:click="finish">Marcar como finalizado</flux:button>
        @endif
    </div>
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm">
            <thead class="text-xs tracking-wide text-zinc-500 uppercase">
                <tr>
                    <th class="px-4 py-3"></th>
                    <th class="px-4 py-3">Previsto</th>
                    <th class="px-4 py-3">Real</th>
                </tr>
            </thead>
            <tbody>
                @foreach ([
                    ['Receita bruta', $statement->expectedGross, $statement->actualGross],
                    ['Taxas', $statement->expectedFees, $statement->actualFees],
                    ['Divisão', $statement->expectedDistributions, $statement->actualDistributions],
                    ['Receita líquida', $statement->expectedNet, $statement->actualNet],
                    ['Custos', $statement->committedCosts, $statement->paidCosts],
                    ['Resultado', $statement->projectedProfit, $statement->currentResult],
                ] as [$label, $planned, $actual])
                    <tr class="border-t border-zinc-200 dark:border-zinc-700">
                        <th class="px-4 py-3 font-medium">{{ $label }}</th>
                        <td class="px-4 py-3"><x-money :cents="$planned" :currency="$event->currency" /></td>
                        <td class="px-4 py-3"><x-money :cents="$actual" :currency="$event->currency" /></td>
                    </tr>
                @endforeach
                <tr class="border-t border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 font-medium">Público</th>
                    <td class="px-4 py-3 tabular-nums">{{ $statement->ticketsGoal }} ingressos na meta</td>
                    <td class="px-4 py-3 tabular-nums">{{ $statement->ticketsSold }} vendidos · {{ $statement->confirmedGuestHeads }} convidados</td>
                </tr>
                <tr class="border-t border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 font-medium">Ticket médio da meta</th>
                    <td class="px-4 py-3" colspan="2">
                        @if ($statement->averageTicket)
                            <x-money :cents="$statement->averageTicket" :currency="$event->currency" />
                        @else
                            Defina a meta dos lotes.
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div>
        <flux:heading size="lg">Alterações recentes</flux:heading>
        @forelse ($audits as $audit)
            <p class="mt-2 text-sm text-zinc-500">
                {{ $audit->created_at?->format('d/m H:i') }}
                · {{ class_basename($audit->auditable_type) }}
                {{ $audit->action }}
                @if ($audit->user) · {{ $audit->user->name }} @endif
            </p>
        @empty
            <p class="mt-2 text-sm text-zinc-500">Ainda não houve mudança financeira registrada.</p>
        @endforelse
    </div>
</div>
