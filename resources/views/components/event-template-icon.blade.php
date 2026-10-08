@props(['slug'])

@switch($slug)
    @case('festa')
        <flux:icon.sparkles {{ $attributes }} />
        @break
    @case('jantar')
        <flux:icon.building-storefront {{ $attributes }} />
        @break
    @case('exposicao')
        <flux:icon.photo {{ $attributes }} />
        @break
    @default
        <flux:icon.document-text {{ $attributes }} />
@endswitch
