<?php

namespace App\Livewire\Events;

use App\Enums\EventRole;
use App\Livewire\Concerns\InteractsWithEvent;
use App\Livewire\Concerns\ManagesEventTeam;
use App\Models\Event;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TeamBoard extends Component
{
    use InteractsWithEvent;
    use ManagesEventTeam;

    public string $email = '';

    public string $role = 'production';

    public function mount(Event $event): void
    {
        $this->mountEvent($event);
    }

    public function invite(): void
    {
        if (! $this->addEventMemberByEmail($this->email, $this->role, 'email')) {
            return;
        }

        $this->reset('email');
        Flux::toast(variant: 'success', text: 'Pessoa adicionada ao evento.');
    }

    public function remove(int $userId): void
    {
        $this->authorize('manageTeam', $this->event);

        if ($userId === auth()->id() && $this->event->roleFor(auth()->user()) === EventRole::Owner) {
            $this->addError('email', 'Você não pode sair sendo o único owner por aqui.');

            return;
        }

        $this->event->members()->detach($userId);
    }

    public function render(): View
    {
        return view('livewire.events.team', [
            'members' => $this->event->members()->orderBy('name')->get(),
            'roles' => EventRole::cases(),
            'canEdit' => auth()->user()->can('manageTeam', $this->event),
        ])->title('Equipe · '.$this->event->name);
    }
}
