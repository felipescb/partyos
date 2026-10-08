<?php

namespace App\Livewire\Concerns;

use App\Enums\EventRole;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

trait ManagesEventTeam
{
    public bool $showTeamModal = false;

    public string $teamInviteEmail = '';

    public string $teamInviteName = '';

    public string $teamInviteRole = 'production';

    public function openTeamModal(): void
    {
        $this->authorize('manageTeam', $this->event);
        $this->resetValidation();
        $this->teamInviteRole = EventRole::Production->value;
        $this->showTeamModal = true;
    }

    public function closeTeamModal(): void
    {
        $this->showTeamModal = false;
        $this->reset('teamInviteEmail', 'teamInviteName');
        $this->teamInviteRole = EventRole::Production->value;
    }

    public function updatedTeamInviteEmail(): void
    {
        $this->teamInviteName = '';
        $email = mb_strtolower(trim($this->teamInviteEmail));

        if ($email === '') {
            return;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user instanceof User) {
            $this->teamInviteName = $user->name;
        }
    }

    public function inviteTeamMember(): void
    {
        if (! $this->addEventMemberByEmail($this->teamInviteEmail, $this->teamInviteRole, 'teamInviteEmail')) {
            return;
        }

        $this->closeTeamModal();
        Flux::toast(variant: 'success', text: 'Pessoa adicionada ao evento.');
    }

    public function updateTeamMemberRole(int $userId, string $role): void
    {
        $this->authorize('manageTeam', $this->event);

        if (! EventRole::tryFrom($role) instanceof EventRole) {
            return;
        }

        if (! $this->event->members()->whereKey($userId)->exists()) {
            return;
        }

        $this->event->members()->updateExistingPivot($userId, ['role' => $role]);
    }

    protected function addEventMemberByEmail(string $email, string $role, string $errorKey = 'teamInviteEmail'): bool
    {
        $this->authorize('manageTeam', $this->event);

        $email = mb_strtolower(trim($email));

        $validator = Validator::make(
            ['email' => $email, 'role' => $role],
            [
                'email' => ['required', 'email'],
                'role' => ['required', Rule::enum(EventRole::class)],
            ],
            [
                'email.required' => 'Qual é o e-mail de quem vai entrar?',
            ],
            [
                'email' => 'e-mail',
                'role' => 'atribuição',
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->addError($errorKey, $message);
            }

            return false;
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User) {
            $this->addError($errorKey, 'Essa pessoa ainda não tem conta no PartyOS.');

            return false;
        }

        $this->event->members()->syncWithoutDetaching([
            $user->id => ['role' => $role],
        ]);
        $this->event->members()->updateExistingPivot($user->id, ['role' => $role]);

        return true;
    }
}
