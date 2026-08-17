<?php

namespace Tests\Feature\Admin;

use App\Models\CreatorApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CreatorApplicationDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_an_approved_application_removes_creator_access_and_revokes_sessions(): void
    {
        $admin = User::factory()->admin()->create();
        $creator = User::factory()->creator()->create(['token_version' => 4]);
        $staleAccessToken = JWTAuth::customClaims([
            'type' => 'access',
            'tv' => 4,
            'exp' => now()->addMinutes((int) config('jwt.ttl'))->timestamp,
        ])->fromUser($creator);
        $application = CreatorApplication::create([
            'user_id' => $creator->id,
            'name' => $creator->name,
            'email' => $creator->email,
            'gender' => 'other',
            'instagram' => '@creator',
            'phone' => '+15555550123',
            'status' => 'approved',
        ]);

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/admin/creator-applications/{$application->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Application deleted and creator access removed successfully.');

        $creator->refresh();

        $this->assertFalse((bool) $creator->is_creator);
        $this->assertSame(5, (int) $creator->token_version);
        $this->assertDatabaseMissing('creator_applications', ['id' => $application->id]);

        $this->withHeader('Authorization', 'Bearer '.$staleAccessToken)
            ->postJson('/api/creator/itineraries/create', [])
            ->assertUnauthorized()
            ->assertJson(['error' => 'token_revoked']);
    }

    public function test_deleting_a_non_approved_application_does_not_remove_existing_creator_access(): void
    {
        $admin = User::factory()->admin()->create();
        $creator = User::factory()->creator()->create(['token_version' => 7]);
        $application = CreatorApplication::create([
            'user_id' => $creator->id,
            'name' => $creator->name,
            'email' => $creator->email,
            'gender' => 'other',
            'instagram' => '@creator',
            'phone' => '+15555550123',
            'status' => 'rejected',
        ]);

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/admin/creator-applications/{$application->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Application deleted successfully.');

        $creator->refresh();

        $this->assertTrue((bool) $creator->is_creator);
        $this->assertSame(7, (int) $creator->token_version);
    }
}
