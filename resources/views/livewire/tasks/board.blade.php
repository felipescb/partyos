<div class="flex flex-col gap-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Tarefas</flux:heading>
            <flux:text>O que precisa acontecer antes da casa abrir.</flux:text>
        </div>
        @if ($canEdit)
            <flux:button variant="primary" wire:click="create">Nova tarefa</flux:button>
        @endif
    </div>
    <div class="tile-grid tile-grid-3">
        <x-tile type="button" size="sm" :current="$filter === 'open'" wire:click="$set('filter', 'open')">
            <span class="tile-title">Abertas</span>
        </x-tile>
        <x-tile type="button" size="sm" :current="$filter === 'all'" wire:click="$set('filter', 'all')">
            <span class="tile-title">Todas</span>
        </x-tile>
    </div>
    @if ($tasks->isEmpty())
        <x-empty-state title="Nenhuma tarefa nesta visão." body="Um template de festa já deixa o checklist pronto. Você também pode criar a próxima agora.">
            @if ($canEdit)
                <flux:button variant="primary" wire:click="create">Nova tarefa</flux:button>
            @endif
        </x-empty-state>
    @else
        <div class="tile-grid">
            @foreach ($tasks as $task)
                <x-tile as="div">
                    <button type="button" class="tile-fill" wire:click="edit({{ $task->id }})" aria-label="Editar {{ $task->title }}"></button>
                    <span class="tile-kicker">{{ $task->status->label() }} · {{ $task->priority->label() }}</span>
                    <span class="tile-title">{{ $task->title }}</span>
                    <span class="tile-meta">
                        {{ $task->due_on?->format('d/m') ?? 'Sem prazo' }}
                        @if ($task->dependsOn) · depende de {{ $task->dependsOn->title }} @endif
                    </span>
                    @if ($canEdit)
                        <button type="button" class="tile-corner" wire:click="advance({{ $task->id }})">{{ $task->status === \App\Enums\TaskStatus::Done ? 'Reabrir' : 'Avançar' }}</button>
                    @endif
                </x-tile>
            @endforeach
        </div>
    @endif
    <flux:modal wire:model="showForm" class="max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Editar tarefa' : 'Nova tarefa' }}</flux:heading>
            <flux:input wire:model="title" label="Título" />
            <flux:textarea wire:model="description" label="Descrição" rows="2" />
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="dueOn" type="date" label="Prazo" />
                <flux:input wire:model="category" label="Categoria" placeholder="produção, marketing" />
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <flux:select wire:model="status" label="Status">
                    @foreach ($statuses as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="priority" label="Prioridade">
                    @foreach ($priorities as $priority)
                        <flux:select.option value="{{ $priority->value }}">{{ $priority->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <flux:select wire:model="dependsOn" label="Depende de" placeholder="Nenhuma">
                <flux:select.option value="">Nenhuma</flux:select.option>
                @foreach ($allTasks as $task)
                    @continue($task->id === $editingId)
                    <flux:select.option value="{{ $task->id }}">{{ $task->title }}</flux:select.option>
                @endforeach
            </flux:select>
            <div class="flex justify-between">
                @if ($editingId)
                    <flux:button type="button" variant="danger" wire:click="destroy({{ $editingId }})">Excluir</flux:button>
                @endif
                <flux:button variant="primary" type="submit">Salvar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
