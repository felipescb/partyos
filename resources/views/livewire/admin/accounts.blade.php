<div class="broker-tickets-page">
    <header class="broker-tickets-head">
        <div class="broker-tickets-head-main">
            <div>
                <h1 class="broker-tickets-title">Contas</h1>
                <p class="broker-tickets-sub">Uma conta cria os próprios eventos e entra nos dos outros. A outra só entra quando é chamada.</p>
            </div>
        </div>
        <div class="broker-tickets-head-actions broker-card-actions">
            <button type="button" class="broker-card-icon-btn" wire:click="create" aria-label="Nova conta" title="Nova conta" data-test="create-account">
                <flux:icon.plus variant="mini" class="size-4" />
            </button>
        </div>
    </header>

    <div class="broker-grid broker-grid-event">
        <article class="broker-grid-item broker-card broker-ticket-panel broker-ticket-list-full">
            <header class="broker-ticket-panel-head">
                <div>
                    <h2 class="broker-ticket-panel-title">Pessoas na plataforma</h2>
                </div>
            </header>

            <div class="broker-account-columns" aria-hidden="true">
                <span>Nome</span>
                <span>Conta</span>
                <span>Desde</span>
            </div>

            <div class="broker-ticket-list" role="list" aria-label="Contas">
                @foreach ($accounts as $account)
                    <div class="broker-account-row" role="listitem" wire:key="account-{{ $account->id }}">
                        <div class="broker-ticket-cell">
                            <span class="broker-ticket-name">{{ $account->name }}</span>
                            <span class="broker-ticket-sub">{{ $account->email }}</span>
                        </div>
                        <div class="broker-ticket-cell">
                            <span class="broker-ticket-sub">{{ $account->account_kind->label() }}</span>
                            @if ($account->isPlatformAdmin())
                                <span class="broker-ticket-sub">Admin da plataforma</span>
                            @endif
                        </div>
                        <div class="broker-ticket-cell">
                            <span class="broker-ticket-sub">{{ $account->created_at?->translatedFormat('d M Y') }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>
    </div>

    <flux:modal wire:model="showForm" class="max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">Nova conta</flux:heading>
            <flux:text>A pessoa entra com este e-mail e esta senha. O e-mail já nasce confirmado.</flux:text>
            <flux:input wire:model="name" label="Nome" autocomplete="off" />
            <flux:input wire:model="email" type="email" label="E-mail" autocomplete="off" />
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="password" type="password" label="Senha" autocomplete="new-password" viewable />
                <flux:input wire:model="password_confirmation" type="password" label="Confirmar senha" autocomplete="new-password" viewable />
            </div>
            <flux:select wire:model="kind" label="Esta conta">
                @foreach ($kinds as $option)
                    <flux:select.option value="{{ $option->value }}">{{ $option->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <div class="flex justify-end">
                <flux:button variant="primary" type="submit" data-test="save-account">Criar conta</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
