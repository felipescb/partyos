<?php

namespace App\Livewire\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Livewire\Concerns\InteractsWithEvent;
use App\Models\Event;
use App\Models\Task;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TaskBoard extends Component
{
    use InteractsWithEvent;

    public string $filter = 'open';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $description = '';

    public string $dueOn = '';

    public string $status = 'todo';

    public string $priority = 'medium';

    public string $category = '';

    public string $dependsOn = '';

    public function mount(Event $event): void
    {
        $this->mountEvent($event);
    }

    public function create(): void
    {
        $this->authorize('manageOperations', $this->event);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('manageOperations', $this->event);
        $task = $this->task($id);
        $this->editingId = $task->id;
        $this->title = $task->title;
        $this->description = (string) $task->description;
        $this->dueOn = $task->due_on?->toDateString() ?? '';
        $this->status = $task->status->value;
        $this->priority = $task->priority->value;
        $this->category = (string) $task->category;
        $this->dependsOn = $task->depends_on_task_id ? (string) $task->depends_on_task_id : '';
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('manageOperations', $this->event);
        $this->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'dueOn' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'category' => ['nullable', 'string', 'max:40'],
            'dependsOn' => ['nullable', 'integer'],
        ], [
            'title.required' => 'O que precisa ser feito?',
        ]);

        if ($this->dependsOn !== '' && (int) $this->dependsOn === $this->editingId) {
            $this->addError('dependsOn', 'Uma tarefa não pode depender dela mesma.');

            return;
        }

        $data = [
            'event_id' => $this->event->id,
            'title' => $this->title,
            'description' => $this->description !== '' ? $this->description : null,
            'due_on' => $this->dueOn !== '' ? $this->dueOn : null,
            'status' => $this->status,
            'priority' => $this->priority,
            'category' => $this->category !== '' ? $this->category : null,
            'depends_on_task_id' => $this->dependsOn !== '' ? (int) $this->dependsOn : null,
            'assignee_id' => auth()->id(),
        ];

        if ($this->editingId) {
            $this->task($this->editingId)->update($data);
        } else {
            $this->event->tasks()->create($data);
        }

        $this->showForm = false;
        Flux::toast(variant: 'success', text: 'Tarefa salva.');
    }

    public function advance(int $id): void
    {
        $this->authorize('manageOperations', $this->event);
        $task = $this->task($id);
        $next = match ($task->status) {
            TaskStatus::Backlog => TaskStatus::Todo,
            TaskStatus::Todo => TaskStatus::Doing,
            TaskStatus::Doing => TaskStatus::Done,
            TaskStatus::Blocked => TaskStatus::Doing,
            TaskStatus::Done => TaskStatus::Todo,
        };
        $task->update(['status' => $next]);
    }

    public function destroy(int $id): void
    {
        $this->authorize('manageOperations', $this->event);
        $this->task($id)->delete();
        $this->showForm = false;
    }

    public function render(): View
    {
        $tasks = $this->event->tasks()->with('dependsOn')->orderByRaw('due_on is null')->orderBy('due_on')->get();

        if ($this->filter === 'open') {
            $tasks = $tasks->reject(fn (Task $task): bool => $task->status === TaskStatus::Done)->values();
        }

        return view('livewire.tasks.board', [
            'tasks' => $tasks,
            'allTasks' => $this->event->tasks()->orderBy('title')->get(),
            'statuses' => TaskStatus::cases(),
            'priorities' => TaskPriority::cases(),
            'canEdit' => auth()->user()->can('manageOperations', $this->event),
        ])->title('Tarefas · '.$this->event->name);
    }

    private function task(int $id): Task
    {
        return $this->event->tasks()->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->description = '';
        $this->dueOn = '';
        $this->status = TaskStatus::Todo->value;
        $this->priority = TaskPriority::Medium->value;
        $this->category = '';
        $this->dependsOn = '';
    }
}
