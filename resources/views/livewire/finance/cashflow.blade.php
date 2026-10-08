<div class="flex flex-col gap-5">
    <div>
        <flux:heading size="xl">Quando o dinheiro sai</flux:heading>
        <flux:text>Não é só quanto o evento custa. É quando você precisa ter o valor na conta.</flux:text>
    </div>
    <div class="tile-grid">
        @foreach (['today' => 'Hoje', '7' => '7 dias', '30' => '30 dias', 'all' => 'Tudo'] as $value => $label)
            <x-tile type="button" size="sm" :current="(string) $value === $window" wire:click="$set('window', '{{ $value }}')">
                <span class="tile-title">{{ $label }}</span>
            </x-tile>
        @endforeach
    </div>
    @if ($rows->isEmpty())
        <x-empty-state title="Nada para pagar nessa janela." body="Lance custos com vencimento ou pagamentos agendados para ver a linha do tempo." />
    @else
        <div class="tile-grid">
            @foreach ($rows as $row)
                <x-tile as="div">
                    <span class="tile-kicker">{{ $row['date'] ? \Illuminate\Support\Carbon::parse($row['date'])->format('d/m') : 'Sem data' }} · {{ $row['state'] }}</span>
                    <span class="tile-title">{{ $row['label'] }}</span>
                    <span class="tile-value"><x-money :cents="$row['amount']" :currency="$event->currency" /></span>
                    <span class="tile-meta">{{ $row['detail'] }}</span>
                </x-tile>
            @endforeach
        </div>
    @endif
</div>
