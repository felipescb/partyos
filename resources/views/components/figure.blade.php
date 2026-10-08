@props(['label', 'hint' => null])

<div {{ $attributes->class('bg-canvas px-4 py-4') }}>
    <p class="text-xs font-medium tracking-normal text-steel uppercase">{{ $label }}</p>
    <div class="mt-2 text-subheading">{{ $slot }}</div>
    @if ($hint)
        <p class="mt-1 text-xs text-silver">{{ $hint }}</p>
    @endif
</div>
