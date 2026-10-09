<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="broker-shell min-h-dvh">
        @php($routeEvent = request()->route('event'))
        @php($headerEvent = $routeEvent instanceof \App\Models\Event ? $routeEvent : null)
        <header class="broker-statusbar" role="banner">
            <div class="broker-statusbar-start">
                <a href="{{ route('dashboard') }}" wire:navigate class="broker-statusbar-brand">PartyOS</a>
                <span class="broker-statusbar-sep" aria-hidden="true"></span>
                <span class="broker-statusbar-title">{{ $headerEvent?->name ?? ($title ?? __('Meus eventos')) }}</span>
            </div>

            <div class="broker-statusbar-end">
                <a href="{{ route('dashboard') }}" wire:navigate @class(['broker-statusbar-link', 'broker-statusbar-link-active' => request()->routeIs('dashboard', 'events.show')])>
                    Eventos
                </a>
                <a href="{{ route('vendors.index') }}" wire:navigate @class(['broker-statusbar-link', 'broker-statusbar-link-active' => request()->routeIs('vendors.*')])>
                    Fornecedores
                </a>
                <a href="{{ route('artists.index') }}" wire:navigate @class(['broker-statusbar-link', 'broker-statusbar-link-active' => request()->routeIs('artists.*')])>
                    Artistas
                </a>
                <a href="{{ route('events.create') }}" wire:navigate @class(['broker-statusbar-link', 'broker-statusbar-link-accent' => request()->routeIs('events.create', 'events.create.configure')])>
                    Novo evento
                </a>
                <span class="broker-statusbar-sep" aria-hidden="true"></span>
                <flux:dropdown position="bottom" align="end">
                    <button type="button" class="broker-statusbar-user">
                        <span class="broker-statusbar-avatar">{{ auth()->user()->initials() }}</span>
                        <span class="broker-statusbar-username">{{ auth()->user()->name }}</span>
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

        <main class="broker-stage">
            {{ $slot }}
        </main>

        @if ($headerEvent && ! request()->routeIs('events.show'))
            <div class="tile-strip-bar">
                <x-event-nav :event="$headerEvent" />
            </div>
        @endif

        @unless ($headerEvent && request()->routeIs('events.show'))
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
        @endunless

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
