<div class="flex flex-col gap-5">
    <div>
        <flux:heading size="xl">Equipe</flux:heading>
        <flux:text>Quem produz, quem vê o financeiro, quem só opera no dia. A pessoa precisa já ter uma conta.</flux:text>
    </div>
    <div class="tile-grid">
        @foreach ($members as $member)
            <x-tile as="div">
                <span class="tile-kicker">{{ \App\Enums\EventRole::from($member->pivot->role)->label() }}</span>
                <span class="tile-title">{{ $member->name }}</span>
                <span class="tile-meta">{{ $member->email }}</span>
                @if ($canEdit)
                    <button type="button" class="tile-corner" wire:click="remove({{ $member->id }})">Remover</button>
                @endif
            </x-tile>
        @endforeach
    </div>
    @if ($canEdit)
        <form wire:submit="invite" class="grid gap-4 md:grid-cols-3">
            <flux:input wire:model="email" type="email" label="E-mail" />
            <flux:select wire:model="role" label="Papel">
                @foreach ($roles as $role)
                    <flux:select.option value="{{ $role->value }}">{{ $role->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <div class="flex items-end">
                <flux:button variant="primary" type="submit">Adicionar</flux:button>
            </div>
        </form>
        <flux:error name="email" />
    @endif
</div>
