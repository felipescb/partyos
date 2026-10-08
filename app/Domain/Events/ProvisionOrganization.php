<?php

namespace App\Domain\Events;

use App\Enums\OrgRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Str;

final class ProvisionOrganization
{
    public function handle(User $user): Organization
    {
        if ($user->current_organization_id) {
            $existing = Organization::query()->find($user->current_organization_id);

            if ($existing instanceof Organization) {
                return $existing;
            }
        }

        $base = Str::slug($user->name);
        $base = $base !== '' ? $base : 'produtora';

        $organization = Organization::query()->create([
            'name' => $user->name,
            'slug' => $base.'-'.Str::lower(Str::random(6)),
        ]);

        $organization->members()->attach($user->id, ['role' => OrgRole::Owner->value]);
        $user->forceFill(['current_organization_id' => $organization->id])->saveQuietly();

        return $organization;
    }
}
