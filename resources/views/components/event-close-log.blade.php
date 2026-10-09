@props([
    'audits',
])

<article {{ $attributes->class(['broker-card', 'broker-close-log']) }} role="listitem">
    <header class="broker-cashflow-map-head">
        <div>
            <x-event-category-symbol category="leitura" />
            <h2 class="broker-cashflow-map-title">Alterações recentes</h2>
        </div>
    </header>

    @forelse ($audits as $audit)
        <p class="broker-close-log-row">
            <span class="broker-close-log-when">{{ $audit->created_at?->format('d/m H:i') }}</span>
            <span class="broker-close-log-text">{{ app(\App\Domain\Finance\AuditSummary::class)->phrase($audit) }}</span>
            @if ($audit->user)
                <span class="broker-close-log-who">{{ $audit->user->name }}</span>
            @endif
        </p>
    @empty
        <p class="broker-close-log-empty">Ainda não houve mudança financeira registrada.</p>
    @endforelse
</article>
