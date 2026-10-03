<?php

namespace Tests\Feature\Auth;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Sara Ali',
            'email' => 'Sara@Example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ], $overrides);
    }

    public function test_user_can_register_and_is_logged_in(): void
    {
        $response = $this->postJson('/api/register', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Sara Ali')
            ->assertJsonPath('data.email', 'sara@example.com')
            ->assertJsonMissingPath('data.password');

        $this->assertAuthenticated();

        // Next request resolves the user from the session, like the SPA would.
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/user')->assertOk()->assertJsonPath('data.email', 'sara@example.com');
    }

    public function test_registration_creates_the_twelve_default_categories(): void
    {
        $this->postJson('/api/register', $this->payload())->assertCreated();

        $user = User::firstWhere('email', 'sara@example.com');

        $this->assertSame(4, $user->categories()->where('type', 'income')->count());
        $this->assertSame(8, $user->categories()->where('type', 'expense')->count());
        $this->assertEqualsCanonicalizing(
            [...Category::DEFAULTS['income'], ...Category::DEFAULTS['expense']],
            $user->categories()->pluck('name')->all(),
        );
    }

    public function test_email_must_be_unique_ignoring_case(): void
    {
        User::factory()->create(['email' => 'sara@example.com']);

        $this->postJson('/api/register', $this->payload(['email' => 'SARA@example.com']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_password_must_be_at_least_eight_characters_and_confirmed(): void
    {
        $this->postJson('/api/register', $this->payload(['password' => 'short', 'password_confirmation' => 'short']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->postJson('/api/register', $this->payload(['password_confirmation' => 'different1']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_required_fields(): void
    {
        $this->postJson('/api/register', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }
}
