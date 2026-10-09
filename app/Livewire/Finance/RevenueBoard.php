<?php

namespace App\Livewire\Finance;

use App\Domain\Finance\Money;
use App\Enums\RevenueCategory;
use App\Enums\RevenueStatus;
use App\Livewire\Concerns\InteractsWithEvent;
use App\Models\Event;
use App\Models\Revenue;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app.dashboard')]
class RevenueBoard extends Component
{
    use InteractsWithEvent;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $category = 'bar';

    public string $description = '';

    public string $expected = '';

    public string $actual = '';

    public string $status = 'planned';

    public string $occurredOn = '';

    public string $notes = '';

    public function mount(Event $event): void
    {
        $this->mountEvent($event, 'viewFinance');
    }

    public function create(): void
    {
        $this->authorize('manageFinance', $this->event);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('manageFinance', $this->event);
        $revenue = $this->revenue($id);
        $this->editingId = $revenue->id;
        $this->category = $revenue->category->value;
        $this->description = $revenue->description;
        $this->expected = $this->money($revenue->expected_amount);
        $this->actual = $this->money($revenue->actual_amount);
        $this->status = $revenue->status->value;
        $this->occurredOn = $revenue->occurred_on?->toDateString() ?? '';
        $this->notes = (string) $revenue->notes;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('manageFinance', $this->event);
        $this->validate([
            'category' => ['required', Rule::in(array_map(fn (RevenueCategory $category): string => $category->value, $this->categories()))],
            'description' => ['required', 'string', 'max:160'],
            'expected' => ['required', 'string'],
            'actual' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(RevenueStatus::class)],
            'occurredOn' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'description.required' => 'Diga de onde vem esse dinheiro.',
            'expected.required' => 'Quanto você espera receber?',
        ]);

        $data = [
            'event_id' => $this->event->id,
            'category' => $this->category,
            'description' => $this->description,
            'expected_amount' => Money::parse($this->expected),
            'actual_amount' => trim($this->actual) === '' ? 0 : Money::parse($this->actual),
            'status' => $this->status,
            'occurred_on' => $this->occurredOn !== '' ? $this->occurredOn : null,
            'notes' => $this->notes !== '' ? $this->notes : null,
        ];

        if ($this->editingId) {
            $this->revenue($this->editingId)->update($data);
        } else {
            $this->event->revenues()->create($data);
        }

        $this->showForm = false;
        Flux::toast(variant: 'success', text: 'Receita salva.');
    }

    public function destroy(int $id): void
    {
        $this->authorize('manageFinance', $this->event);
        $this->revenue($id)->delete();
        $this->showForm = false;
        Flux::toast(variant: 'success', text: 'Receita removida.');
    }

    public function render(): View
    {
        $revenues = $this->event->revenues()->orderBy('category')->orderBy('description')->get();
        $active = $revenues->reject(fn (Revenue $revenue): bool => $revenue->status === RevenueStatus::Cancelled);
        $expected = (int) $active->sum('expected_amount');
        $actual = (int) $active->sum('actual_amount');

        return view('livewire.finance.revenues', [
            'revenues' => $revenues,
            'categories' => $this->categories(),
            'statuses' => RevenueStatus::cases(),
            'canEdit' => auth()->user()->can('manageFinance', $this->event),
            'summary' => [
                'expected' => $expected,
                'actual' => $actual,
                'remaining' => max(0, $expected - $actual),
                'count' => $active->count(),
            ],
        ])->title('Receitas · '.$this->event->name);
    }

    /**
     * @return list<RevenueCategory>
     */
    private function categories(): array
    {
        return array_values(array_filter(
            RevenueCategory::cases(),
            fn (RevenueCategory $category): bool => $category->isManual(),
        ));
    }

    private function revenue(int $id): Revenue
    {
        return $this->event->revenues()->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->category = RevenueCategory::Bar->value;
        $this->description = '';
        $this->expected = '';
        $this->actual = '';
        $this->status = RevenueStatus::Planned->value;
        $this->occurredOn = '';
        $this->notes = '';
    }

    private function money(int $cents): string
    {
        return number_format(intdiv($cents, 100), 0, ',', '.').','.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
