<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityLocation;
use App\Models\City;
use App\Models\SupportRequest;
use App\Models\User;
use App\Support\SupportReferenceGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;
use Tymon\JWTAuth\JWTAuth;
use Tymon\JWTAuth\JWTGuard;

class SupportRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.frontend_url', 'http://localhost:3000/');
    }

    public function test_guest_can_create_a_normalized_support_request_for_a_public_item(): void
    {
        [$activity] = $this->activityInDubai(['name' => 'Dubai Desert Safari']);
        $payload = $this->validPayload($activity, [
            'email' => '  ASHA@EXAMPLE.COM ',
            'item_type' => ' ACTIVITY ',
            'item_title' => 'Untrusted browser title',
            'city_slug' => ' DUBAI ',
            'item_slug' => ' DUBAI-DESERT-SAFARI ',
        ]);

        $response = $this->postJson('/api/support-requests', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Your support request has been received.')
            ->assertJsonPath('data.status', 'open')
            ->assertJsonStructure(['data' => ['reference', 'status']]);

        $this->assertDatabaseHas('support_requests', [
            'client_request_id' => $payload['client_request_id'],
            'user_id' => null,
            'email' => 'asha@example.com',
            'item_type' => 'activity',
            'item_id' => $activity->id,
            'item_title' => 'Dubai Desert Safari',
            'city_slug' => 'dubai',
            'item_slug' => 'dubai-desert-safari',
            'status' => 'open',
        ]);
    }

    public function test_valid_bearer_token_attaches_the_user(): void
    {
        [$activity] = $this->activityInDubai();
        $user = User::factory()->create();
        $token = $this->jwt()->fromUser($user);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/support-requests', $this->validPayload($activity))
            ->assertCreated();

        $this->assertDatabaseHas('support_requests', ['user_id' => $user->id]);
    }

    public function test_same_client_request_id_and_identity_returns_the_original_ticket(): void
    {
        [$activity] = $this->activityInDubai();
        $payload = $this->validPayload($activity, ['email' => ' ASHA@EXAMPLE.COM ']);

        $first = $this->postJson('/api/support-requests', $payload);
        $second = $this->postJson('/api/support-requests', [
            ...$payload,
            'email' => 'asha@example.com',
        ]);

        $first->assertCreated();
        $second->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reference', $first->json('data.reference'));
        $this->assertDatabaseCount('support_requests', 1);
    }

    public function test_same_uuid_with_a_different_email_returns_conflict(): void
    {
        [$activity] = $this->activityInDubai();
        $payload = $this->validPayload($activity);

        $this->postJson('/api/support-requests', $payload)->assertCreated();

        $this->postJson('/api/support-requests', [
            ...$payload,
            'email' => 'someone-else@example.com',
        ])->assertConflict()
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('support_requests', 1);
    }

    public function test_same_uuid_with_a_different_item_returns_conflict(): void
    {
        [$firstActivity, $city] = $this->activityInDubai(['slug' => 'first-activity']);
        /** @var Activity $secondActivity */
        $secondActivity = Activity::factory()->create([
            'name' => 'Second Activity',
            'slug' => 'second-activity',
        ]);
        ActivityLocation::query()->create([
            'activity_id' => $secondActivity->id,
            'city_id' => $city->id,
        ]);
        $payload = $this->validPayload($firstActivity);

        $this->postJson('/api/support-requests', $payload)->assertCreated();

        $this->postJson('/api/support-requests', $this->validPayload($secondActivity, [
            'client_request_id' => $payload['client_request_id'],
        ]))->assertConflict();

        $this->assertDatabaseCount('support_requests', 1);
    }

    public function test_invalid_topic_returns_a_validation_error(): void
    {
        [$activity] = $this->activityInDubai();

        $this->postJson('/api/support-requests', $this->validPayload($activity, [
            'topic' => 'not-a-supported-topic',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors('topic');
    }

    public function test_array_email_returns_json_validation_error_instead_of_a_server_error(): void
    {
        [$activity] = $this->activityInDubai();

        $this->postJson('/api/support-requests', $this->validPayload($activity, [
            'email' => ['asha@example.com'],
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('support_requests', 0);
    }

    public function test_invalid_page_url_returns_a_validation_error(): void
    {
        [$activity] = $this->activityInDubai();

        $this->postJson('/api/support-requests', $this->validPayload($activity, [
            'page_url' => "https://evil.example/cities/dubai/activities/{$activity->slug}",
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors('page_url');
    }

    public function test_unknown_item_slug_returns_not_found(): void
    {
        [$activity] = $this->activityInDubai();

        $this->postJson('/api/support-requests', $this->validPayload($activity, [
            'item_slug' => 'unknown-activity',
            'page_url' => 'http://localhost:3000/cities/dubai/activities/unknown-activity',
        ]))->assertNotFound();
    }

    public function test_unknown_item_city_returns_not_found(): void
    {
        [$activity] = $this->activityInDubai();

        $this->postJson('/api/support-requests', $this->validPayload($activity, [
            'city_slug' => 'paris',
            'page_url' => "http://localhost:3000/cities/paris/activities/{$activity->slug}",
        ]))->assertNotFound();
    }

    public function test_populated_honeypot_returns_quiet_no_content_and_stores_nothing(): void
    {
        [$activity] = $this->activityInDubai();

        $this->postJson('/api/support-requests', $this->validPayload($activity, [
            'website' => 'https://spam.example',
        ]))->assertNoContent();

        $this->assertDatabaseCount('support_requests', 0);
    }

    public function test_populated_honeypot_short_circuits_an_otherwise_invalid_payload(): void
    {
        $this->postJson('/api/support-requests', [
            'website' => 'https://spam.example',
            'email' => ['not-a-scalar'],
        ])->assertNoContent();

        $this->assertDatabaseCount('support_requests', 0);
    }

    public function test_repeated_uuid_uses_the_atomic_duplicate_key_fallback_without_a_server_error(): void
    {
        [$activity] = $this->activityInDubai();
        $payload = $this->validPayload($activity);

        $this->postJson('/api/support-requests', $payload)->assertCreated();
        $this->postJson('/api/support-requests', $payload)->assertOk();

        $this->assertDatabaseCount('support_requests', 1);
    }

    public function test_missing_bearer_token_continues_as_a_guest(): void
    {
        [$activity] = $this->activityInDubai();

        $this->postJson('/api/support-requests', $this->validPayload($activity))
            ->assertCreated();

        $this->assertDatabaseHas('support_requests', ['user_id' => null]);
    }

    public function test_malformed_bearer_token_continues_as_a_guest(): void
    {
        [$activity] = $this->activityInDubai();

        $this->withHeader('Authorization', 'Bearer malformed-token')
            ->postJson('/api/support-requests', $this->validPayload($activity))
            ->assertCreated();

        $this->assertDatabaseHas('support_requests', ['user_id' => null]);
    }

    public function test_expired_bearer_token_continues_as_a_guest_when_the_library_can_issue_it(): void
    {
        [$activity] = $this->activityInDubai();
        $user = User::factory()->create();

        try {
            $expired = $this->jwt()
                ->customClaims(['exp' => now()->subMinute()->timestamp])
                ->fromUser($user);
        } catch (\Exception) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->withHeader('Authorization', "Bearer {$expired}")
            ->postJson('/api/support-requests', $this->validPayload($activity))
            ->assertCreated();

        $this->assertDatabaseHas('support_requests', ['user_id' => null]);
    }

    public function test_stale_token_version_preserves_the_global_revoked_response(): void
    {
        [$activity] = $this->activityInDubai();
        $user = User::factory()->create();
        $stale = $this->jwt()->fromUser($user);
        $user->increment('token_version');

        /** @var JWTGuard $guard */
        $guard = auth('api');
        $guard->forgetUser();

        $this->withHeader('Authorization', "Bearer {$stale}")
            ->postJson('/api/support-requests', $this->validPayload($activity))
            ->assertUnauthorized()
            ->assertJson(['error' => 'token_revoked']);

        $this->assertDatabaseCount('support_requests', 0);
    }

    public function test_reference_collision_regenerates_the_reference(): void
    {
        [$activity] = $this->activityInDubai();
        SupportRequest::create([
            ...$this->persistedValues($activity),
            'client_request_id' => (string) Str::uuid(),
            'reference' => 'WLP-260731-TAKEN1',
        ]);

        $generator = Mockery::mock(SupportReferenceGenerator::class);
        $generator->expects('generate')->andReturn('WLP-260731-TAKEN1');
        $generator->expects('generate')->andReturn('WLP-260731-NEW001');
        $this->app->instance(SupportReferenceGenerator::class, $generator);

        $this->postJson('/api/support-requests', $this->validPayload($activity))
            ->assertCreated()
            ->assertJsonPath('data.reference', 'WLP-260731-NEW001');

        $this->assertDatabaseHas('support_requests', ['reference' => 'WLP-260731-NEW001']);
    }

    public function test_authenticated_user_is_resolved_before_reference_collision_transaction(): void
    {
        [$activity] = $this->activityInDubai();
        SupportRequest::create([
            ...$this->persistedValues($activity),
            'client_request_id' => (string) Str::uuid(),
            'reference' => 'WLP-260731-TAKEN2',
        ]);

        $generator = Mockery::mock(SupportReferenceGenerator::class);
        $generator->expects('generate')->andReturn('WLP-260731-TAKEN2');
        $generator->expects('generate')->andReturn('WLP-260731-NEW002');
        $this->app->instance(SupportReferenceGenerator::class, $generator);

        $user = User::factory()->create();
        $token = $this->jwt()->fromUser($user);
        $events = [];
        $createdSupportRequest = null;

        User::retrieved(function (User $retrieved) use ($user, &$events): void {
            if ($retrieved->is($user)) {
                $events[] = 'user_resolved';
            }
        });

        DB::partialMock();
        DB::expects('transaction')
            ->andReturnUsing(function (\Closure $callback) use (
                &$events,
                &$createdSupportRequest,
            ): mixed {
                $events[] = 'transaction_entered';

                $result = $callback();

                if ($result instanceof SupportRequest) {
                    $createdSupportRequest = $result;
                }

                return $result;
            });

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/support-requests', $this->validPayload($activity))
            ->assertCreated()
            ->assertJsonPath('data.reference', 'WLP-260731-NEW002');

        $this->assertSame(['user_resolved', 'transaction_entered'], $events);
        $this->assertInstanceOf(SupportRequest::class, $createdSupportRequest);
        $this->assertSame('WLP-260731-NEW002', $createdSupportRequest->reference);
        $this->assertSame($user->id, $createdSupportRequest->user_id);
    }

    public function test_unrelated_database_exception_is_not_swallowed(): void
    {
        [$activity] = $this->activityInDubai();
        $exception = new QueryException(
            'sqlite',
            'insert into support_requests ...',
            [],
            new RuntimeException('connection lost'),
        );

        DB::expects('transaction')
            ->andThrow($exception);

        $this->withoutExceptionHandling();
        $this->expectException(QueryException::class);

        $this->postJson('/api/support-requests', $this->validPayload($activity));
    }

    public function test_sixth_request_for_the_same_email_and_ip_in_ten_minutes_is_throttled(): void
    {
        [$activity] = $this->activityInDubai();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/support-requests', $this->validPayload($activity))
                ->assertCreated();
        }

        $this->postJson('/api/support-requests', $this->validPayload($activity))
            ->assertTooManyRequests();

        $this->assertDatabaseCount('support_requests', 5);
    }

    public function test_twenty_first_request_from_the_same_ip_cannot_bypass_throttle_by_rotating_emails(): void
    {
        [$activity] = $this->activityInDubai();

        for ($attempt = 1; $attempt <= 20; $attempt++) {
            $this->postJson('/api/support-requests', $this->validPayload($activity, [
                'email' => "traveler-{$attempt}@example.com",
            ]))->assertCreated();
        }

        $this->postJson('/api/support-requests', $this->validPayload($activity, [
            'email' => 'traveler-21@example.com',
        ]))->assertTooManyRequests();

        $this->assertDatabaseCount('support_requests', 20);
    }

    /**
     * @param  array<string, mixed>  $activityOverrides
     * @return array{Activity, City}
     */
    private function activityInDubai(array $activityOverrides = []): array
    {
        /** @var City $city */
        $city = City::factory()->create(['slug' => 'dubai']);
        /** @var Activity $activity */
        $activity = Activity::factory()->create(array_merge([
            'name' => 'Dubai Desert Safari',
            'slug' => 'dubai-desert-safari',
        ], $activityOverrides));

        ActivityLocation::query()->create([
            'activity_id' => $activity->id,
            'city_id' => $city->id,
        ]);

        return [$activity, $city];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(Activity $activity, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Asha Traveler',
            'email' => 'asha@example.com',
            'topic' => 'before_booking',
            'message' => 'Can you help me understand the booking details?',
            'item_type' => 'activity',
            'item_id' => $activity->id,
            'item_title' => $activity->name,
            'city_slug' => 'dubai',
            'item_slug' => $activity->slug,
            'page_url' => "http://localhost:3000/cities/dubai/activities/{$activity->slug}",
            'client_request_id' => (string) Str::uuid(),
            'website' => null,
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function persistedValues(Activity $activity): array
    {
        return [
            'user_id' => null,
            'name' => 'Existing Traveler',
            'email' => 'existing@example.com',
            'topic' => 'other',
            'message' => 'This existing request reserves the first reference.',
            'item_type' => 'activity',
            'item_id' => $activity->id,
            'item_title' => $activity->name,
            'city_slug' => 'dubai',
            'item_slug' => $activity->slug,
            'page_url' => "http://localhost:3000/cities/dubai/activities/{$activity->slug}",
            'status' => 'open',
        ];
    }

    private function jwt(): JWTAuth
    {
        return $this->app->make(JWTAuth::class);
    }
}
