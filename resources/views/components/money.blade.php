@props(['cents' => 0, 'currency' => 'BRL'])

<span {{ $attributes->class(['tabular-nums']) }}>
    {{ \App\Domain\Finance\Money::format((int) $cents, $currency) }}
</span>
