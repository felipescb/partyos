<?php

namespace App\Observers;

use App\Domain\Events\ProvisionOrganization;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        app(ProvisionOrganization::class)->handle($user);
    }
}
