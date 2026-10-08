<flux:modal wire:model="showTeamModal" class="max-w-lg">
    <form wire:submit="inviteTeamMember" class="space-y-4">
        <flux:heading size="lg">Adicionar à equipe</flux:heading>
        <flux:text>A pessoa precisa já ter conta no PartyOS. O nome vem do perfil dela; você define a atribuição aqui.</flux:text>
        <flux:input wire:model.blur="teamInviteEmail" type="email" label="E-mail da pessoa" autocomplete="email" />
        <flux:input wire:model="teamInviteName" type="text" label="Nome" readonly placeholder="Aparece quando o e-mail tem conta" />
        <flux:select wire:model="teamInviteRole" label="Atribuição">
            @foreach ($teamRoles as $role)
                <flux:select.option value="{{ $role->value }}">{{ $role->label() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:error name="teamInviteEmail" />
        <div class="flex justify-end gap-2">
            <flux:button type="button" wire:click="closeTeamModal">Cancelar</flux:button>
            <flux:button variant="primary" type="submit">Adicionar</flux:button>
        </div>
    </form>
</flux:modal>
