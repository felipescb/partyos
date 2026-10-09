<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationDisabledTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('PARTYOS_ALLOW_REGISTRATION=false');
        $_ENV['PARTYOS_ALLOW_REGISTRATION'] = 'false';
        $_SERVER['PARTYOS_ALLOW_REGISTRATION'] = 'false';

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        putenv('PARTYOS_ALLOW_REGISTRATION');
        unset($_ENV['PARTYOS_ALLOW_REGISTRATION'], $_SERVER['PARTYOS_ALLOW_REGISTRATION']);
    }

    public function test_login_screen_hides_sign_up_when_registration_is_disabled(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Criar conta');

        $this->get('/register')->assertNotFound();
    }
}
