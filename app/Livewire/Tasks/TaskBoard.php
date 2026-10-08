<?php

namespace App\Livewire\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Livewire\Concerns\InteractsWithEvent;
use App\Models\Event;
use App\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app.dashboard')]
class TaskBoard extends Component
{
    use InteractsWithEvent;

    public string $filter = 'all';

    public string $sortColumn = 'due_on';

    public string $sortDirection = 'asc';

    public string $newTaskTitle = '';

    public ?int $expandedId = null;

    public function mount(Event $event): void
    {
        $this->mountEvent($event);
    }

    public function createFromDraft(): void
    {
        $this->authorize('manageOperations', $this->event);

        $this->validate([
            'newTaskTitle' => ['required', 'string', 'max:160'],
        ], [
            'newTaskTitle.required' => 'Escreva o que precisa ser feito.',
        ]);

        $this->event->tasks()->create([
            'title' => trim($this->newTaskTitle),
            'status' => TaskStatus::Todo,
            'priority' => TaskPriority::Medium,
            'assignee_id' => auth()->id(),
        ]);

        $this->reset('newTaskTitle');
    }

    public function toggleDone(int $id): void
    {
        $this->authorize('manageOperations', $this->event);
        $task = $this->task($id);
        $next = $task->status === TaskStatus::Done ? TaskStatus::Todo : TaskStatus::Done;
        $task->update(['status' => $next]);
    }

    public function updateTaskTitle(int $id, string $title): void
    {
        $this->authorize('manageOperations', $this->event);
        $title = trim($title);

        if ($title === '') {
            return;
        }

        validator(['title' => $title], [
            'title' => ['required', 'string', 'max:160'],
        ])->validate();

        $this->task($id)->update(['title' => $title]);
    }

    public function updateTaskStatus(int $id, string $status): void
    {
        $this->authorize('manageOperations', $this->event);

        if (! TaskStatus::tryFrom($status) instanceof TaskStatus) {
            return;
        }

        $this->task($id)->update(['status' => $status]);
    }

    public function updateTaskPriority(int $id, string $priority): void
    {
        $this->authorize('manageOperations', $this->event);

        if (! TaskPriority::tryFrom($priority) instanceof TaskPriority) {
            return;
        }

        $this->task($id)->update(['priority' => $priority]);
    }

    public function updateTaskDueOn(int $id, string $dueOn): void
    {
        $this->authorize('manageOperations', $this->event);

        if ($dueOn !== '') {
            validator(['dueOn' => $dueOn], ['dueOn' => ['date']])->validate();
        }

        $this->task($id)->update([
            'due_on' => $dueOn !== '' ? $dueOn : null,
        ]);
    }

    public function updateTaskCategory(int $id, string $category): void
    {
        $this->authorize('manageOperations', $this->event);

        validator(['category' => $category], [
            'category' => ['nullable', 'string', 'max:40'],
        ])->validate();

        $this->task($id)->update([
            'category' => trim($category) !== '' ? trim($category) : null,
        ]);
    }

    public function updateTaskDescription(int $id, string $description): void
    {
        $this->authorize('manageOperations', $this->event);

        validator(['description' => $description], [
            'description' => ['nullable', 'string', 'max:2000'],
        ])->validate();

        $this->task($id)->update([
            'description' => trim($description) !== '' ? trim($description) : null,
        ]);
    }

    public function updateTaskDependsOn(int $id, string $dependsOn): void
    {
        $this->authorize('manageOperations', $this->event);

        if ($dependsOn !== '' && (int) $dependsOn === $id) {
            return;
        }

        $this->task($id)->update([
            'depends_on_task_id' => $dependsOn !== '' ? (int) $dependsOn : null,
        ]);
    }

    public function toggleExpand(int $id): void
    {
        $this->expandedId = $this->expandedId === $id ? null : $id;
    }

    public function sort(string $column): void
    {
        $allowed = ['title', 'status', 'priority', 'due_on', 'category'];

        if (! in_array($column, $allowed, true)) {
            return;
        }

        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function destroy(int $id): void
    {
        $this->authorize('manageOperations', $this->event);
        $this->task($id)->delete();

        if ($this->expandedId === $id) {
            $this->expandedId = null;
        }
    }

    public function render(): View
    {
        $tasks = $this->event->tasks()->with('dependsOn')->get();

        if ($this->filter === 'open') {
            $tasks = $tasks->reject(fn (Task $task): bool => $task->status === TaskStatus::Done)->values();
        }

        $tasks = $this->sortedTasks($tasks);

        return view('livewire.tasks.board', [
            'tasks' => $tasks,
            'allTasks' => $this->event->tasks()->orderBy('title')->get(),
            'statuses' => TaskStatus::cases(),
            'priorities' => TaskPriority::cases(),
            'canEdit' => auth()->user()->can('manageOperations', $this->event),
            'openCount' => $this->event->tasks()->where('status', '!=', TaskStatus::Done)->count(),
        ])->title('Tarefas · '.$this->event->name);
    }

    private function task(int $id): Task
    {
        return $this->event->tasks()->findOrFail($id);
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @return Collection<int, Task>
     */
    private function sortedTasks(Collection $tasks): Collection
    {
        $desc = $this->sortDirection === 'desc';

        $sorted = match ($this->sortColumn) {
            'title' => $tasks->sortBy(
                fn (Task $task): string => mb_strtolower($task->title),
                SORT_REGULAR,
                $desc,
            ),
            'status' => $tasks->sortBy(
                fn (Task $task): int => $this->statusSortKey($task->status),
                SORT_REGULAR,
                $desc,
            ),
            'priority' => $tasks->sortBy(
                fn (Task $task): int => $this->prioritySortKey($task->priority),
                SORT_REGULAR,
                $desc,
            ),
            'category' => $tasks->sortBy(
                fn (Task $task): string => mb_strtolower($task->category ?? ''),
                SORT_REGULAR,
                $desc,
            ),
            default => $tasks->sortBy(
                fn (Task $task): array => [
                    $task->due_on === null ? 1 : 0,
                    $task->due_on?->timestamp ?? 0,
                ],
                SORT_REGULAR,
                $desc,
            ),
        };

        return $sorted->values();
    }

    private function statusSortKey(TaskStatus $status): int
    {
        return match ($status) {
            TaskStatus::Backlog => 0,
            TaskStatus::Todo => 1,
            TaskStatus::Doing => 2,
            TaskStatus::Blocked => 3,
            TaskStatus::Done => 4,
        };
    }

    private function prioritySortKey(TaskPriority $priority): int
    {
        return match ($priority) {
            TaskPriority::High => 0,
            TaskPriority::Medium => 1,
            TaskPriority::Low => 2,
        };
    }
}
