<?php

namespace App\Domain\Accounts;

use App\Enums\AccountKind;
use App\Models\User;
use Illuminate\Support\Str;

final class CreatePlatformAccount
{
    public function handle(string $name, string $email, string $password, AccountKind $kind): User
    {
        $user = User::query()->create([
            'name' => trim($name),
            'email' => Str::lower(trim($email)),
            'password' => $password,
            'account_kind' => $kind,
        ]);

        $user->markEmailAsVerified();

        return $user;
    }
}
