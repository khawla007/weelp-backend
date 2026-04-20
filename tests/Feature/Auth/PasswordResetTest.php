<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_password_reset(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->postJson('/api/password/forgot', [
            'email' => $user->email,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_password_reset_fails_with_invalid_token(): void
    {
        $response = $this->postJson('/api/password/reset', [
            'token' => 'invalid-token-value',
            'password' => 'NewPassword@123',
            'password_confirmation' => 'NewPassword@123',
        ]);

        $this->assertNotEquals(200, $response->status());
    }

    public function test_password_forgot_fails_with_missing_email(): void
    {
        $response = $this->postJson('/api/password/forgot', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }
}
