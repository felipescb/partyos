<?php

namespace App\Observers;

use App\Domain\Events\ProvisionOrganization;
use App\Enums\AccountKind;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        if ($user->account_kind === AccountKind::Participant) {
            return;
        }

        app(ProvisionOrganization::class)->handle($user);
    }
}
