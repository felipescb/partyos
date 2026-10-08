<div class="broker-task-notebook">
    <header class="broker-task-notebook-head">
        <div class="broker-task-notebook-head-main">
            <a href="{{ route('events.show', $event) }}" wire:navigate class="broker-task-notebook-back">← Quadro</a>
            <div>
                <x-event-category-symbol category="checklist" />
                <h1 class="broker-task-notebook-title">Caderno de tarefas</h1>
                <p class="broker-task-notebook-sub">{{ $openCount }} abertas · {{ $event->name }}</p>
            </div>
        </div>
        <div class="broker-task-notebook-filters" role="tablist" aria-label="Filtrar tarefas">
            <button
                type="button"
                role="tab"
                @class(['broker-task-notebook-filter', 'broker-task-notebook-filter-active' => $filter === 'open'])
                wire:click="$set('filter', 'open')"
                aria-selected="{{ $filter === 'open' ? 'true' : 'false' }}"
            >
                Abertas
            </button>
            <button
                type="button"
                role="tab"
                @class(['broker-task-notebook-filter', 'broker-task-notebook-filter-active' => $filter === 'all'])
                wire:click="$set('filter', 'all')"
                aria-selected="{{ $filter === 'all' ? 'true' : 'false' }}"
            >
                Todas
            </button>
        </div>
    </header>

    <div class="broker-task-notebook-sheet broker-card" role="region" aria-label="Lista de tarefas">
        <div class="broker-task-notebook-columns">
            <span class="broker-task-notebook-col-check" aria-hidden="true"></span>
            @foreach ([
                'title' => 'Tarefa',
                'status' => 'Status',
                'priority' => 'Prioridade',
                'due_on' => 'Prazo',
                'category' => 'Área',
            ] as $column => $label)
                <button
                    type="button"
                    @class([
                        'broker-task-notebook-sort',
                        'broker-task-notebook-sort-active' => $sortColumn === $column,
                    ])
                    wire:click="sort('{{ $column }}')"
                    aria-sort="{{ $sortColumn === $column ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                >
                    <span>{{ $label }}</span>
                    @if ($sortColumn === $column)
                        <flux:icon.chevron-up variant="micro" @class(['broker-task-notebook-sort-icon', 'broker-task-notebook-sort-icon-desc' => $sortDirection === 'desc']) />
                    @endif
                </button>
            @endforeach
            <span class="broker-task-notebook-col-actions" aria-hidden="true"></span>
        </div>

        <div class="broker-task-notebook-list" role="list">
            @forelse ($tasks as $task)
                @php($isDone = $task->status === \App\Enums\TaskStatus::Done)
                @php($expanded = $expandedId === $task->id)
                <div
                    @class(['broker-task-notebook-row', 'broker-task-notebook-row-done' => $isDone, 'broker-task-notebook-row-expanded' => $expanded])
                    role="listitem"
                    wire:key="task-row-{{ $task->id }}"
                >
                    <div class="broker-task-notebook-row-main">
                        <div class="broker-task-notebook-cell broker-task-notebook-cell-check">
                            @if ($canEdit)
                                <button
                                    type="button"
                                    @class(['broker-task-check', 'broker-task-check-done' => $isDone])
                                    wire:click="toggleDone({{ $task->id }})"
                                    aria-label="{{ $isDone ? 'Reabrir tarefa' : 'Marcar concluída' }}"
                                >
                                    @if ($isDone)
                                        <flux:icon.check variant="micro" class="size-3" />
                                    @endif
                                </button>
                            @else
                                <span @class(['broker-task-check', 'broker-task-check-done' => $isDone]) aria-hidden="true">
                                    @if ($isDone)
                                        <flux:icon.check variant="micro" class="size-3" />
                                    @endif
                                </span>
                            @endif
                        </div>

                        <div class="broker-task-notebook-cell broker-task-notebook-cell-title">
                            @if ($canEdit)
                                <input
                                    type="text"
                                    class="broker-task-notebook-input broker-task-notebook-input-title"
                                    value="{{ $task->title }}"
                                    wire:blur="updateTaskTitle({{ $task->id }}, $event.target.value)"
                                    aria-label="Título da tarefa"
                                />
                            @else
                                <span class="broker-task-notebook-read">{{ $task->title }}</span>
                            @endif
                        </div>

                        <div class="broker-task-notebook-cell broker-task-notebook-cell-status">
                            @if ($canEdit)
                                <select
                                    class="broker-task-notebook-select"
                                    wire:change="updateTaskStatus({{ $task->id }}, $event.target.value)"
                                    aria-label="Status"
                                >
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status->value }}" @selected($task->status === $status)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            @else
                                <span class="broker-task-notebook-read">{{ $task->status->label() }}</span>
                            @endif
                        </div>

                        <div class="broker-task-notebook-cell broker-task-notebook-cell-priority">
                            @if ($canEdit)
                                <select
                                    class="broker-task-notebook-select"
                                    wire:change="updateTaskPriority({{ $task->id }}, $event.target.value)"
                                    aria-label="Prioridade"
                                >
                                    @foreach ($priorities as $priority)
                                        <option value="{{ $priority->value }}" @selected($task->priority === $priority)>{{ $priority->label() }}</option>
                                    @endforeach
                                </select>
                            @else
                                <span class="broker-task-notebook-read">{{ $task->priority->label() }}</span>
                            @endif
                        </div>

                        <div class="broker-task-notebook-cell broker-task-notebook-cell-due">
                            @if ($canEdit)
                                <input
                                    type="date"
                                    class="broker-task-notebook-input"
                                    value="{{ $task->due_on?->toDateString() }}"
                                    wire:change="updateTaskDueOn({{ $task->id }}, $event.target.value)"
                                    aria-label="Prazo"
                                />
                            @else
                                <span class="broker-task-notebook-read">{{ $task->due_on?->format('d/m/Y') ?? '—' }}</span>
                            @endif
                        </div>

                        <div class="broker-task-notebook-cell broker-task-notebook-cell-category">
                            @if ($canEdit)
                                <input
                                    type="text"
                                    class="broker-task-notebook-input"
                                    value="{{ $task->category }}"
                                    placeholder="produção, marketing…"
                                    wire:blur="updateTaskCategory({{ $task->id }}, $event.target.value)"
                                    aria-label="Área"
                                />
                            @else
                                <span class="broker-task-notebook-read">{{ $task->category ?? '—' }}</span>
                            @endif
                        </div>

                        <div class="broker-task-notebook-cell broker-task-notebook-cell-actions">
                            <button
                                type="button"
                                class="broker-task-notebook-icon-btn"
                                wire:click="toggleExpand({{ $task->id }})"
                                aria-expanded="{{ $expanded ? 'true' : 'false' }}"
                                aria-label="Detalhes"
                            >
                                <flux:icon.chevron-down variant="micro" @class(['size-4 transition', 'rotate-180' => $expanded]) />
                            </button>
                            @if ($canEdit)
                                <button
                                    type="button"
                                    class="broker-task-notebook-icon-btn broker-task-notebook-icon-btn-danger"
                                    wire:click="destroy({{ $task->id }})"
                                    wire:confirm="Excluir esta tarefa?"
                                    aria-label="Excluir"
                                >
                                    <flux:icon.trash variant="micro" class="size-4" />
                                </button>
                            @endif
                        </div>
                    </div>

                    @if ($expanded)
                        <div class="broker-task-notebook-row-detail">
                            @if ($task->dependsOn)
                                <p class="broker-task-notebook-meta">Depende de: {{ $task->dependsOn->title }}</p>
                            @endif
                            @if ($canEdit)
                                <label class="broker-task-notebook-detail-label">
                                    Descrição
                                    <textarea
                                        class="broker-task-notebook-textarea"
                                        rows="3"
                                        wire:blur="updateTaskDescription({{ $task->id }}, $event.target.value)"
                                        aria-label="Descrição"
                                    >{{ $task->description }}</textarea>
                                </label>
                                <label class="broker-task-notebook-detail-label">
                                    Depende de
                                    <select
                                        class="broker-task-notebook-select broker-task-notebook-select-wide"
                                        wire:change="updateTaskDependsOn({{ $task->id }}, $event.target.value)"
                                    >
                                        <option value="">Nenhuma</option>
                                        @foreach ($allTasks as $candidate)
                                            @continue($candidate->id === $task->id)
                                            <option value="{{ $candidate->id }}" @selected($task->depends_on_task_id === $candidate->id)>{{ $candidate->title }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            @elseif ($task->description)
                                <p class="broker-task-notebook-meta">{{ $task->description }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="broker-task-notebook-empty">
                    <p>Nenhuma tarefa nesta visão.</p>
                    <p class="broker-task-notebook-empty-hint">Escreva a primeira linha no fim do caderno.</p>
                </div>
            @endforelse
        </div>

        @if ($canEdit)
            <form wire:submit="createFromDraft" class="broker-task-notebook-compose">
                <span class="broker-task-check broker-task-check-draft" aria-hidden="true"></span>
                <input
                    type="text"
                    class="broker-task-notebook-input broker-task-notebook-input-title"
                    wire:model="newTaskTitle"
                    placeholder="Nova tarefa… Enter para adicionar"
                    aria-label="Nova tarefa"
                />
                <flux:button variant="primary" type="submit" size="sm">Adicionar</flux:button>
                <flux:error name="newTaskTitle" />
            </form>
        @endif
    </div>
</div>
