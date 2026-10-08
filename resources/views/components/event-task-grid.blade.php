@props([
    'event',
    'tasks',
    'openCount' => 0,
])

<article {{ $attributes->class(['broker-card', 'broker-task-panel']) }} role="listitem">
    <header class="broker-task-panel-head">
        <div>
            <x-event-category-symbol category="checklist" />
            <h2 class="broker-task-panel-title">Tarefas</h2>
        </div>
        <a href="{{ route('events.tasks', $event) }}" wire:navigate class="broker-task-panel-link">
            Ver todas{{ $openCount > 0 ? ' · '.$openCount : '' }}
        </a>
    </header>

    <div class="broker-task-list" role="list" aria-label="Tarefas abertas">
        @forelse ($tasks as $task)
            @php($isDone = $task->status === \App\Enums\TaskStatus::Done)
            <a
                href="{{ route('events.tasks', $event) }}"
                wire:navigate
                @class(['broker-task-row', 'broker-task-row-done' => $isDone])
                role="listitem"
            >
                <span @class(['broker-task-check', 'broker-task-check-done' => $isDone]) aria-hidden="true">
                    @if ($isDone)
                        <flux:icon.check variant="micro" class="size-3" />
                    @endif
                </span>
                <span class="broker-task-row-body">
                    <span class="broker-task-title">{{ $task->title }}</span>
                    <span class="broker-task-sub">
                        {{ $task->status->label() }}
                        @if ($task->due_on)
                            · {{ $task->due_on->format('d/m') }}
                        @endif
                        @if ($task->priority === \App\Enums\TaskPriority::High)
                            · {{ $task->priority->label() }}
                        @endif
                    </span>
                </span>
            </a>
        @empty
            <div class="broker-task-empty">
                <p>Nenhuma tarefa aberta.</p>
                <a href="{{ route('events.tasks', $event) }}" wire:navigate class="broker-task-panel-link">Abrir checklist</a>
            </div>
        @endforelse
    </div>
</article>
