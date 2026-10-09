<?php

namespace Tests\Feature;

use App\Enums\OrgRole;
use App\Models\CostCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BootstrapPartyOsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_verified_owner_and_syncs_the_catalog(): void
    {
        config([
            'partyos.admin_name' => 'Admin',
            'partyos.admin_email' => 'admin@partyos.local',
            'partyos.admin_password' => 'uma-senha-bem-longa',
        ]);

        $this->artisan('partyos:bootstrap')->assertSuccessful();

        $user = User::query()->where('email', 'admin@partyos.local')->first();

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('uma-senha-bem-longa', $user->password));
        $this->assertSame(OrgRole::Owner, $user->organization?->roleFor($user));
        $this->assertTrue(CostCategory::query()->where('is_system', true)->exists());

        $this->post(route('login.store'), [
            'email' => 'admin@partyos.local',
            'password' => 'uma-senha-bem-longa',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_it_does_not_replace_an_existing_admin_password(): void
    {
        config([
            'partyos.admin_name' => 'Admin',
            'partyos.admin_email' => 'admin@partyos.local',
            'partyos.admin_password' => 'uma-senha-bem-longa',
        ]);

        $this->artisan('partyos:bootstrap')->assertSuccessful();

        $password = User::query()->where('email', 'admin@partyos.local')->value('password');

        config(['partyos.admin_password' => 'outra-senha-diferente-123']);

        $this->artisan('partyos:bootstrap')->assertSuccessful();

        $this->assertSame(1, User::query()->count());
        $this->assertSame($password, User::query()->where('email', 'admin@partyos.local')->value('password'));
    }

    public function test_it_stores_a_generated_password_when_none_is_configured(): void
    {
        $path = storage_path('app/private/initial-admin-password.txt');
        File::delete($path);

        config([
            'partyos.admin_name' => 'Admin',
            'partyos.admin_email' => 'admin@partyos.local',
            'partyos.admin_password' => '',
        ]);

        $this->artisan('partyos:bootstrap')->assertSuccessful();

        $password = trim((string) File::get($path));
        $user = User::query()->where('email', 'admin@partyos.local')->first();

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotSame('', $password);
        $this->assertTrue(Hash::check($password, $user->password));

        File::delete($path);
    }
}
