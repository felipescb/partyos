@props([
    'href' => null,
    'as' => null,
    'tone' => 'surface',
    'size' => 'md',
    'current' => false,
    'span' => false,
])

@php
    $tag = $href ? 'a' : ($as ?? 'button');
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" wire:navigate @endif
    @if ($tag === 'button' && ! $attributes->has('type')) type="button" @endif
    {{ $attributes->class([
        'tile',
        'tile-sm' => $size === 'sm',
        'tile-lg' => $size === 'lg',
        'tile-span' => $span,
        'tile-accent' => $tone === 'accent',
        'tile-live' => $tone === 'live',
        'tile-ghost' => $tone === 'ghost',
        'tile-current' => $current,
    ]) }}
>
    {{ $slot }}
</{{ $tag }}>
