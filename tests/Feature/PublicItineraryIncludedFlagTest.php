<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\ItineraryActivity;
use App\Models\ItinerarySchedule;
use App\Models\ItineraryTransfer;
use App\Models\Transfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicItineraryIncludedFlagTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Transfer::clearZonePriceCache();
    }

    public function test_included_flag_maps_to_include_in_package_api_key(): void
    {
        // Create itinerary
        $itinerary = Itinerary::factory()->create();

        // Create schedule
        $schedule = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 1,
            'title' => 'Day 1',
        ]);

        // Create activity and transfer
        $activity = Activity::create([
            'name' => 'Test Activity',
            'slug' => 'test-activity-' . uniqid(),
        ]);

        $transfer = Transfer::create([
            'name' => 'Test Transfer',
            'slug' => 'test-transfer-' . uniqid(),
            'description' => 'A test transfer',
            'transfer_type' => 'private',
        ]);

        // Seed: activity included=true, transfer included=false
        ItineraryActivity::create([
            'schedule_id' => $schedule->id,
            'activity_id' => $activity->id,
            'price' => 100.00,
            'included' => true,
        ]);

        ItineraryTransfer::create([
            'schedule_id' => $schedule->id,
            'transfer_id' => $transfer->id,
            'price' => 50.00,
            'included' => false,
        ]);

        // Hit GET /api/itineraries/{slug}
        $response = $this->getJson('/api/itineraries/' . $itinerary->slug);

        $response->assertOk();
        $payload = $response->json('data');

        // Assert schedules contain activities and transfers with include_in_package mirroring included
        $this->assertNotEmpty($payload['schedules']);
        $firstSchedule = $payload['schedules'][0];

        // Check activity
        $this->assertNotEmpty($firstSchedule['activities']);
        $activityPayload = $firstSchedule['activities'][0];
        $this->assertTrue($activityPayload['include_in_package'], 'Activity include_in_package should reflect included=true');

        // Check transfer
        $this->assertNotEmpty($firstSchedule['transfers']);
        $transferPayload = $firstSchedule['transfers'][0];
        $this->assertFalse($transferPayload['include_in_package'], 'Transfer include_in_package should reflect included=false');
    }
}
