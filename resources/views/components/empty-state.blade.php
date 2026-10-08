@props(['title', 'body' => null])

<div {{ $attributes->class('tile tile-ghost') }}>
    <p class="tile-title">{{ $title }}</p>
    @if ($body)
        <p class="tile-meta max-w-md">{{ $body }}</p>
    @endif
    @if (trim($slot) !== '')
        <div class="mt-2">{{ $slot }}</div>
    @endif
</div>
