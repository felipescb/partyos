<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_is_the_login_screen(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(__('Log in to your account'), false);
    }

    public function test_authenticated_users_are_sent_from_home_to_the_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertRedirect(route('dashboard'));
    }
}
