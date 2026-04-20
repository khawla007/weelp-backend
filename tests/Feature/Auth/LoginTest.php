<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password@123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Password@123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['success', 'accessToken', 'id', 'email', 'name', 'role']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password@123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'WrongPassword@1',
        ]);

        $response->assertUnauthorized();
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nobody@example.com',
            'password' => 'Password@123',
        ]);

        $response->assertUnauthorized();
    }

    public function test_login_succeeds_even_with_unverified_email(): void
    {
        // The AuthController does NOT check email_verified_at —
        // it authenticates any user with valid credentials.
        $user = User::factory()->unverified()->create([
            'password' => Hash::make('Password@123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Password@123',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_login_is_throttled_after_5_attempts(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password@123'),
            'email_verified_at' => now(),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'WrongPassword@1',
            ]);
        }

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'WrongPassword@1',
        ]);

        $response->assertStatus(429);
    }
}
