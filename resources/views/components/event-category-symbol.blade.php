@props([
    'category',
    'label' => null,
    'variant' => 'symbol',
])

@php
    $slug = match ($category) {
        'dinheiro' => 'financeiro',
        'dia' => 'operacao',
        default => $category,
    };

    $defaultLabel = match ($slug) {
        'financeiro' => 'Financeiro',
        'checklist' => 'Checklist',
        'operacao' => 'Operação',
        'casa' => 'Casa',
        'equipe' => 'Equipe',
        'quando' => 'Quando',
        'ao-vivo' => 'Ao vivo',
        'atencao' => 'Atenção',
        'olhar' => 'Olhar',
        'leitura' => 'Leitura',
        'evento' => 'Evento',
        'novo' => 'Novo evento',
        'editar' => 'Editar',
        default => ucfirst(str_replace('-', ' ', $slug)),
    };

    $text = $label ?? $defaultLabel;

    $class = match ($variant) {
        'kicker' => 'tile-kicker',
        default => 'broker-card-symbol',
    };
@endphp

<span {{ $attributes->class([$class, 'broker-cat', "broker-cat-{$slug}"]) }}>{{ $text }}</span>
