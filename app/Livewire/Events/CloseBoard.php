<?php

namespace App\Livewire\Events;

use App\Domain\Finance\EventFinanceReader;
use App\Domain\Finance\StatementBuilder;
use App\Enums\EventStatus;
use App\Livewire\Concerns\InteractsWithEvent;
use App\Models\Event;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CloseBoard extends Component
{
    use InteractsWithEvent;

    public function mount(Event $event): void
    {
        $this->mountEvent($event, 'viewFinance');
    }

    public function finish(): void
    {
        $this->authorize('manageFinance', $this->event);
        $this->event->update(['status' => EventStatus::Finished]);
        $this->reloadEvent();
        Flux::toast(variant: 'success', text: 'Evento marcado como finalizado.');
    }

    public function render(EventFinanceReader $reader, StatementBuilder $builder): View
    {
        $input = $reader->read($this->event);

        return view('livewire.events.close', [
            'statement' => $builder->statement($input),
            'audits' => $this->event->audits()->with('user')->limit(12)->get(),
            'canEdit' => auth()->user()->can('manageFinance', $this->event),
        ])->title('Fechamento · '.$this->event->name);
    }
}
