<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailVerifyReplayTest extends TestCase
{
    use RefreshDatabase;

    private function registerAndCaptureToken(string $email = 'replay@example.com'): string
    {
        Mail::fake();

        $this->postJson('/api/register', [
            'name' => 'Replay User',
            'email' => $email,
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ])->assertStatus(201);

        $captured = null;
        Mail::assertSent(\App\Mail\VerifyEmailMail::class, function ($mail) use (&$captured) {
            $captured = $mail->token ?? null;

            return true;
        });

        $this->assertNotEmpty($captured, 'verification token must be captured from mail');

        return $captured;
    }

    public function test_token_used_returns_410(): void
    {
        $token = $this->registerAndCaptureToken();

        $this->getJson('/api/verify-email?token='.urlencode($token))->assertOk();

        $this->getJson('/api/verify-email?token='.urlencode($token))
            ->assertStatus(410)
            ->assertJson(['error_code' => 'token_used']);
    }

    public function test_expired_token_returns_410(): void
    {
        $token = $this->registerAndCaptureToken('expired@example.com');

        DB::table('email_verifications')
            ->where('email', 'expired@example.com')
            ->update(['expires_at' => now()->subMinute()]);

        $this->getJson('/api/verify-email?token='.urlencode($token))
            ->assertStatus(410)
            ->assertJson(['error_code' => 'token_expired']);
    }

    public function test_resend_invalidates_prior_token(): void
    {
        $first = $this->registerAndCaptureToken('resend@example.com');

        Mail::fake();

        $this->postJson('/api/resend-verification', ['email' => 'resend@example.com'])
            ->assertOk();

        $this->getJson('/api/verify-email?token='.urlencode($first))
            ->assertStatus(410)
            ->assertJson(['error_code' => 'token_used']);

        $second = null;
        Mail::assertSent(\App\Mail\VerifyEmailMail::class, function ($mail) use (&$second) {
            $second = $mail->token ?? null;

            return true;
        });

        $this->assertNotEmpty($second);
        $this->assertNotSame($first, $second);

        $this->getJson('/api/verify-email?token='.urlencode($second))->assertOk();
    }

    public function test_invalid_token_returns_400(): void
    {
        $this->getJson('/api/verify-email?token=garbage')
            ->assertStatus(400);
    }

    public function test_token_stored_as_sha256_not_bcrypt(): void
    {
        $token = $this->registerAndCaptureToken('hash@example.com');

        $row = DB::table('email_verifications')->where('email', 'hash@example.com')->first();

        $this->assertSame(hash('sha256', $token), $row->token);
        $this->assertNotNull($row->expires_at);
        $this->assertNull($row->used_at);
    }
}
