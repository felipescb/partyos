<?php

namespace App\Policies;

use App\Enums\EventRole;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function view(User $user, Event $event): bool
    {
        return $this->role($user, $event) instanceof EventRole;
    }

    public function viewFinance(User $user, Event $event): bool
    {
        return (bool) $this->role($user, $event)?->canViewFinance();
    }

    public function manageFinance(User $user, Event $event): bool
    {
        return (bool) $this->role($user, $event)?->canManageFinance();
    }

    public function manageOperations(User $user, Event $event): bool
    {
        return (bool) $this->role($user, $event)?->canManageOperations();
    }

    public function viewGuests(User $user, Event $event): bool
    {
        return (bool) $this->role($user, $event)?->canViewGuests();
    }

    public function manageTeam(User $user, Event $event): bool
    {
        return (bool) $this->role($user, $event)?->canManageTeam();
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->role($user, $event) === EventRole::Owner;
    }

    private function role(User $user, Event $event): ?EventRole
    {
        return $event->roleFor($user);
    }
}
