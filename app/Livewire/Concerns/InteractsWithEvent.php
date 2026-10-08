<?php

namespace App\Livewire\Concerns;

use App\Models\Event;
use Livewire\Attributes\Locked;

trait InteractsWithEvent
{
    #[Locked]
    public Event $event;

    #[Locked]
    public string $requiredAbility = 'view';

    public function mountEvent(Event $event, string $ability = 'view'): void
    {
        $this->requiredAbility = $ability;
        $this->authorize($ability, $event);
        $this->event = $event;
    }

    public function hydrateInteractsWithEvent(): void
    {
        $this->authorize($this->requiredAbility, $this->event);
    }

    protected function reloadEvent(): void
    {
        $fresh = $this->event->fresh();

        if ($fresh instanceof Event) {
            $this->event = $fresh;
        }
    }
}
