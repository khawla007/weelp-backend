<?php

namespace Tests\Feature;

use App\Models\Itinerary;
use App\Models\ItineraryMeta;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatorPublicLeakTest extends TestCase
{
    use RefreshDatabase;

    private const BANNED_KEYS = [
        'email',
        'phone',
        'address_line_1',
        'address',
        'dob',
        'stripe_customer_id',
        'password',
        'remember_token',
        'failed_login_attempts',
        'locked_until',
        'token_version',
    ];

    private function seedApprovedCreatorItinerary(): array
    {
        $creator = User::factory()->create([
            'email' => 'leaktest-creator@example.com',
        ]);

        UserProfile::create([
            'user_id' => $creator->id,
            'phone' => '+1-555-LEAKED',
            'address_line_1' => '742 Evergreen Terrace',
            'city' => 'Springfield',
        ]);

        $itinerary = Itinerary::factory()->create();
        ItineraryMeta::create([
            'itinerary_id' => $itinerary->id,
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);

        return [$creator, $itinerary];
    }

    private function assertNoBannedKeys(array $payload, string $context): void
    {
        $walker = function ($node) use (&$walker, $context) {
            if (is_array($node)) {
                foreach ($node as $key => $value) {
                    if (is_string($key)) {
                        $this->assertNotContains(
                            strtolower($key),
                            self::BANNED_KEYS,
                            "PII key '{$key}' leaked in {$context}"
                        );
                    }
                    $walker($value);
                }
            }
        };
        $walker($payload);
    }

    public function test_explore_index_does_not_leak_creator_email_or_pii(): void
    {
        [$creator] = $this->seedApprovedCreatorItinerary();

        $response = $this->getJson('/api/creator/explore');
        $response->assertOk();

        $this->assertNoBannedKeys($response->json(), 'GET /api/creator/explore');

        $body = json_encode($response->json());
        $this->assertStringNotContainsString($creator->email, $body);
        $this->assertStringNotContainsString('+1-555-LEAKED', $body);
        $this->assertStringNotContainsString('742 Evergreen Terrace', $body);
    }

    public function test_explore_show_does_not_leak_creator_email_or_pii(): void
    {
        [$creator, $itinerary] = $this->seedApprovedCreatorItinerary();

        $response = $this->getJson("/api/creator/explore/{$itinerary->id}");
        $response->assertOk();

        $this->assertNoBannedKeys($response->json(), "GET /api/creator/explore/{$itinerary->id}");

        $body = json_encode($response->json());
        $this->assertStringNotContainsString($creator->email, $body);
        $this->assertStringNotContainsString('+1-555-LEAKED', $body);
        $this->assertStringNotContainsString('742 Evergreen Terrace', $body);
    }

    public function test_explore_index_still_returns_creator_id_and_name(): void
    {
        [$creator] = $this->seedApprovedCreatorItinerary();

        $response = $this->getJson('/api/creator/explore');
        $response->assertOk();

        $data = collect($response->json('data'));
        $this->assertNotEmpty($data, 'explore index returned empty data');

        $first = $data->first();
        $this->assertArrayHasKey('creator', $first);
        $this->assertSame($creator->id, $first['creator']['id'] ?? null);
        $this->assertSame($creator->name, $first['creator']['name'] ?? null);
    }
}
