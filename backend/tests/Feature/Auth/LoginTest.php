<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in(): void
    {
        $user = User::factory()->create(['email' => 'omar@example.com']);

        $this->postJson('/api/login', ['email' => 'Omar@Example.com', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonMissingPath('data.password');

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'omar@example.com']);

        $this->postJson('/api/login', ['email' => 'omar@example.com', 'password' => 'wrong-password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_sixth_attempt_in_a_minute_is_throttled(): void
    {
        User::factory()->create(['email' => 'omar@example.com']);

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/login', ['email' => 'omar@example.com', 'password' => 'wrong-password'])
                ->assertUnprocessable();
        }

        $this->postJson('/api/login', ['email' => 'omar@example.com', 'password' => 'password'])
            ->assertTooManyRequests();
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
        $this->postJson('/api/logout')->assertNoContent();

        $this->assertGuest('web');
    }

    public function test_current_user_requires_a_session(): void
    {
        $this->getJson('/api/user')->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_unauthenticated_request_without_json_accept_header_still_gets_401(): void
    {
        $this->get('/api/user')->assertUnauthorized();
    }
}
