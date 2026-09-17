<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => '12345',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_deactivated_users_can_not_authenticate(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post('/login', [
            'username' => $user->username,
            'password' => '12345',
        ]);

        $this->assertGuest();
    }

    public function test_account_is_locked_after_three_failed_attempts(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 3) as $attempt) {
            $this->post('/login', [
                'username' => $user->username,
                'password' => 'wrong-password',
            ]);
        }

        $this->assertTrue($user->fresh()->isLocked());

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => '12345',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
