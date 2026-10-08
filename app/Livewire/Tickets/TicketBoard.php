<?php

namespace App\Livewire\Tickets;

use App\Domain\Finance\Money;
use App\Livewire\Concerns\InteractsWithEvent;
use App\Models\Event;
use App\Models\TicketTier;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TicketBoard extends Component
{
    use InteractsWithEvent;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $price = '';

    public string $quantity = '';

    public string $goal = '';

    public string $sold = '';

    public string $startsAt = '';

    public string $endsAt = '';

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
        $tier = $this->tier($id);
        $this->editingId = $tier->id;
        $this->name = $tier->name;
        $this->price = $this->money($tier->price);
        $this->quantity = (string) $tier->quantity;
        $this->goal = (string) $tier->goal;
        $this->sold = (string) $tier->sold_quantity;
        $this->startsAt = $tier->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->endsAt = $tier->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('manageFinance', $this->event);
        $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'price' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:0'],
            'goal' => ['required', 'integer', 'min:0'],
            'sold' => ['required', 'integer', 'min:0'],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date'],
        ], [
            'name.required' => 'Dê um nome para o lote.',
            'price.required' => 'Qual é o preço?',
            'goal.required' => 'Qual é a meta de vendas desse lote?',
        ]);

        $data = [
            'event_id' => $this->event->id,
            'name' => $this->name,
            'price' => Money::parse($this->price),
            'quantity' => (int) $this->quantity,
            'goal' => (int) $this->goal,
            'sold_quantity' => (int) $this->sold,
            'starts_at' => $this->startsAt !== '' ? $this->startsAt : null,
            'ends_at' => $this->endsAt !== '' ? $this->endsAt : null,
            'sort_order' => $this->editingId
                ? $this->tier($this->editingId)->sort_order
                : ((int) $this->event->ticketTiers()->max('sort_order')) + 1,
        ];

        if ($this->editingId) {
            $this->tier($this->editingId)->update($data);
        } else {
            $this->event->ticketTiers()->create($data);
        }

        $this->showForm = false;
        Flux::toast(variant: 'success', text: 'Lote salvo.');
    }

    public function destroy(int $id): void
    {
        $this->authorize('manageFinance', $this->event);
        $this->tier($id)->delete();
        $this->showForm = false;
        Flux::toast(variant: 'success', text: 'Lote removido.');
    }

    public function render(): View
    {
        $tiers = $this->event->ticketTiers()->get();

        return view('livewire.tickets.board', [
            'tiers' => $tiers,
            'canEdit' => auth()->user()->can('manageFinance', $this->event),
            'sold' => (int) $tiers->sum('sold_quantity'),
            'goal' => (int) $tiers->sum('goal'),
            'revenue' => (int) $tiers->sum(fn (TicketTier $tier): int => $tier->revenue()),
        ])->title('Ingressos · '.$this->event->name);
    }

    private function tier(int $id): TicketTier
    {
        return $this->event->ticketTiers()->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->price = '';
        $this->quantity = '';
        $this->goal = '';
        $this->sold = '0';
        $this->startsAt = '';
        $this->endsAt = '';
    }

    private function money(int $cents): string
    {
        return number_format(intdiv($cents, 100), 0, ',', '.').','.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
