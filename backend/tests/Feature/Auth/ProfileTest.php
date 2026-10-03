<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_name(): void
    {
        $user = Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/profile', ['name' => 'New Name', 'email' => 'hacker@example.com'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_name_is_required(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/profile', ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_user_can_change_password(): void
    {
        $user = Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/profile/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertNoContent();

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_current_password_must_match(): void
    {
        $user = Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/profile/password', [
            'current_password' => 'not-my-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_profile_requires_authentication(): void
    {
        $this->putJson('/api/profile', ['name' => 'X'])->assertUnauthorized();
        $this->putJson('/api/profile/password', [])->assertUnauthorized();
    }
}
