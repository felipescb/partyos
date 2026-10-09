@if (auth()->user()?->isPlatformAdmin())
    <flux:menu.submenu heading="Admin" icon="shield-check">
        <flux:menu.item :href="route('admin.accounts')" icon="users" wire:navigate data-test="admin-accounts-link">
            Contas
        </flux:menu.item>
    </flux:menu.submenu>
@endif
