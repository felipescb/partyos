<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="tile-shell min-h-screen">
        @php($currentEvent = request()->route('event') instanceof \App\Models\Event ? request()->route('event') : null)

        <header class="broker-statusbar broker-statusbar-app" role="banner">
            <div class="broker-statusbar-start">
                <a href="{{ route('dashboard') }}" wire:navigate class="broker-statusbar-brand">PartyOS</a>
                @if ($currentEvent)
                    <span class="broker-statusbar-sep" aria-hidden="true"></span>
                    <span class="broker-statusbar-title">{{ $currentEvent->name }}</span>
                @endif
            </div>
            <div class="broker-statusbar-end">
                <a href="{{ route('dashboard') }}" wire:navigate @class(['broker-statusbar-link', 'broker-statusbar-link-active' => request()->routeIs('dashboard')])>
                    Eventos
                </a>
                <flux:dropdown position="bottom" align="end">
                    <button type="button" class="broker-statusbar-user">
                        <span class="broker-statusbar-avatar">{{ auth()->user()->initials() }}</span>
                        <span class="broker-statusbar-username hidden sm:inline">{{ auth()->user()->name }}</span>
                    </button>
                <flux:menu>
                    <div class="px-2 py-1.5 text-sm">
                        <p class="font-medium">{{ auth()->user()->name }}</p>
                        <p class="text-steel">{{ auth()->user()->email }}</p>
                    </div>
                    <flux:menu.separator />
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="logout-button">
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
                </flux:dropdown>
            </div>
        </header>

        <main @class(['tile-stage', 'tile-stage-event' => $currentEvent && ! request()->routeIs('events.show')])>
            {{ $slot }}
        </main>

        @if ($currentEvent && ! request()->routeIs('events.show'))
            <div class="tile-strip-bar">
                <x-event-nav :event="$currentEvent" />
            </div>
        @endif

        <nav class="tile-dock" aria-label="Produtora">
            <x-tile :href="route('dashboard')" size="sm" :current="request()->routeIs('dashboard')">
                <span class="tile-kicker">Casa</span>
                <span class="tile-title">Eventos</span>
            </x-tile>
            <x-tile :href="route('vendors.index')" size="sm" :current="request()->routeIs('vendors.*')">
                <span class="tile-kicker">Rede</span>
                <span class="tile-title">Fornecedores</span>
            </x-tile>
            <x-tile :href="route('artists.index')" size="sm" :current="request()->routeIs('artists.index')">
                <span class="tile-kicker">Rede</span>
                <span class="tile-title">Artistas</span>
            </x-tile>
            <x-tile :href="route('events.create')" size="sm" tone="accent" :current="request()->routeIs('events.create')">
                <span class="tile-kicker">Começar</span>
                <span class="tile-title">Novo</span>
            </x-tile>
        </nav>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
