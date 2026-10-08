<flux:modal wire:model="showDuplicate" class="max-w-lg">
    <form wire:submit="duplicate" class="space-y-4">
        <flux:heading size="lg">Duplicar evento</flux:heading>
        <flux:text>A nova edição nasce em rascunho. Pagamentos já feitos não são copiados.</flux:text>
        <flux:input wire:model="duplicateName" label="Nome da nova edição" />
        <flux:input wire:model="duplicateStarts" type="datetime-local" label="Nova data de início" />
        <div class="grid grid-cols-2 gap-2 text-sm">
            @foreach (['budget' => 'Orçamento', 'artists' => 'Artistas', 'tasks' => 'Tarefas', 'schedule' => 'Cronograma', 'tickets' => 'Ingressos', 'guests' => 'Convidados'] as $value => $label)
                <label class="flex min-h-12 items-center gap-3">
                    <input type="checkbox" value="{{ $value }}" wire:model="copy" class="size-5 border-zinc-400">
                    {{ $label }}
                </label>
            @endforeach
        </div>
        <div class="flex justify-end gap-2">
            <flux:button type="button" wire:click="$set('showDuplicate', false)">Cancelar</flux:button>
            <flux:button variant="primary" type="submit">Criar edição</flux:button>
        </div>
    </form>
</flux:modal>
