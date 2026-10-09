@props([
    'map',
    'grain' => 'week',
])

<div class="broker-cashflow-modes">
    <div class="broker-cashflow-map-switch" role="group" aria-label="Modo do mapa">
        <button type="button" wire:click="$set('map', 'time')" aria-pressed="{{ $map === 'time' ? 'true' : 'false' }}">Por tempo</button>
        <button type="button" wire:click="$set('map', 'category')" aria-pressed="{{ $map === 'category' ? 'true' : 'false' }}">Por categoria</button>
    </div>
    @if ($map === 'time')
        <div class="broker-cashflow-map-switch" role="group" aria-label="Recorte do tempo">
            <button type="button" wire:click="$set('grain', 'week')" aria-pressed="{{ $grain === 'week' ? 'true' : 'false' }}">Semanas</button>
            <button type="button" wire:click="$set('grain', 'day')" aria-pressed="{{ $grain === 'day' ? 'true' : 'false' }}">Dias</button>
        </div>
    @endif
</div>
