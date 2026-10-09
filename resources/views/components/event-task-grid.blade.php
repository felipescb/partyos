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
            @php($isLate = $task->due_on && ! $isDone && $task->due_on->isBefore(today()))
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
                <span class="broker-task-title">{{ $task->title }}</span>
                <span class="broker-task-marks">
                    <span @class(['broker-task-mark', 'broker-task-status-'.$task->status->value]) title="{{ $task->status->label() }}" aria-label="{{ $task->status->label() }}">
                        @switch ($task->status)
                            @case (\App\Enums\TaskStatus::Backlog)
                                <flux:icon.inbox variant="micro" />
                                @break
                            @case (\App\Enums\TaskStatus::Todo)
                                <flux:icon.queue-list variant="micro" />
                                @break
                            @case (\App\Enums\TaskStatus::Doing)
                                <flux:icon.play variant="micro" />
                                @break
                            @case (\App\Enums\TaskStatus::Blocked)
                                <flux:icon.no-symbol variant="micro" />
                                @break
                            @default
                                <flux:icon.check-circle variant="micro" />
                        @endswitch
                    </span>
                    <span @class(['broker-task-mark', 'broker-task-priority-'.$task->priority->value]) title="Prioridade {{ $task->priority->label() }}" aria-label="Prioridade {{ $task->priority->label() }}">
                        <flux:icon.flag variant="micro" />
                    </span>
                    @if ($task->due_on)
                        <span @class(['broker-task-due', 'broker-task-due-late' => $isLate]) title="Prazo {{ $task->due_on->format('d/m/Y') }}" aria-label="Prazo {{ $task->due_on->format('d/m') }}{{ $isLate ? ', atrasada' : '' }}">
                            <flux:icon.calendar variant="micro" />
                            <span>{{ $task->due_on->format('d/m') }}</span>
                        </span>
                    @endif
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
