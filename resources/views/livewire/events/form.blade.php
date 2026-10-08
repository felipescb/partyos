<div class="mx-auto flex max-w-3xl flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ $editing ? 'Editar evento' : 'Novo evento' }}</flux:heading>
        <flux:text class="mt-1">Comece pelo essencial. O resto do PartyOS se pendura neste evento.</flux:text>
    </div>

    <form wire:submit="save" class="grid gap-4">
        <flux:input wire:model="name" label="Nome" placeholder="Festa X — Outubro" required />
        <div class="grid gap-4 md:grid-cols-2">
            <flux:select wire:model="type" label="Tipo">
                @foreach ($types as $type)
                    <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="status" label="Status">
                @foreach ($statuses as $status)
                    <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        @unless ($editing)
            <flux:select wire:model="templateId" label="Template" placeholder="Começar do zero">
                <flux:select.option value="">Começar do zero</flux:select.option>
                @foreach ($templates as $template)
                    <flux:select.option value="{{ $template->id }}">{{ $template->name }} — {{ $template->description }}</flux:select.option>
                @endforeach
            </flux:select>
        @endunless

        <flux:textarea wire:model="description" label="Descrição" rows="3" />

        <div class="grid gap-4 md:grid-cols-2">
            <flux:input wire:model="startsAt" type="datetime-local" label="Início" />
            <flux:input wire:model="endsAt" type="datetime-local" label="Término" />
        </div>
        <flux:error name="endsAt" />

        <div class="grid gap-4 md:grid-cols-2">
            <flux:input wire:model="venueName" label="Local" placeholder="Nome da casa, galpão, sítio" />
            <flux:input wire:model="city" label="Cidade" />
        </div>
        <flux:input wire:model="address" label="Endereço" />

        <div class="grid gap-4 md:grid-cols-2">
            <flux:input wire:model="capacity" type="number" min="1" label="Capacidade" />
            <flux:select wire:model="currency" label="Moeda">
                <flux:select.option value="BRL">Real (R$)</flux:select.option>
                <flux:select.option value="USD">Dólar (US$)</flux:select.option>
                <flux:select.option value="EUR">Euro (€)</flux:select.option>
            </flux:select>
        </div>

        <flux:input wire:model="cover" type="file" label="Imagem" accept="image/*" />
        <flux:textarea wire:model="notes" label="Observações" rows="3" />

        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:button variant="primary" type="submit">Salvar evento</flux:button>
            @if ($editing)
                <flux:button type="button" variant="danger" wire:click="$set('confirmDelete', true)">Excluir evento</flux:button>
            @endif
        </div>
    </form>

    <flux:modal wire:model="confirmDelete" class="max-w-md">
        <flux:heading size="lg">Excluir este evento?</flux:heading>
        <flux:text class="mt-2">Excluir este evento remove o acesso a custos, convidados, tarefas e demais informações relacionadas. A exclusão fica na lixeira do sistema e não apaga o histórico de auditoria.</flux:text>
        <div class="mt-6 flex justify-end gap-2">
            <flux:button type="button" wire:click="$set('confirmDelete', false)">Cancelar</flux:button>
            <flux:button type="button" variant="danger" wire:click="delete">Excluir evento</flux:button>
        </div>
    </flux:modal>
</div>
