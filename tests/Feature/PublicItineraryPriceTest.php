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

class PublicItineraryPriceTest extends TestCase
{
    use RefreshDatabase;

    private function seedItineraryWithScheduleSum(float $activitySum, float $transferSum): Itinerary
    {
        $itinerary = Itinerary::factory()->create();

        $schedule = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 1,
            'title' => 'Day 1',
        ]);

        $activity = Activity::create([
            'name' => 'Public Test Activity',
            'slug' => 'public-test-activity-' . uniqid(),
        ]);

        $transfer = Transfer::create([
            'name' => 'Public Test Transfer',
            'slug' => 'public-test-transfer-' . uniqid(),
            'description' => 'A test transfer for public itinerary',
            'transfer_type' => 'private',
        ]);

        ItineraryActivity::create([
            'schedule_id' => $schedule->id,
            'activity_id' => $activity->id,
            'price' => $activitySum,
            'included' => true,
        ]);

        ItineraryTransfer::create([
            'schedule_id' => $schedule->id,
            'transfer_id' => $transfer->id,
            'price' => $transferSum,
            'included' => true,
        ]);

        return $itinerary->fresh();
    }

    public function test_public_show_returns_schedule_total_price(): void
    {
        $itinerary = $this->seedItineraryWithScheduleSum(100.00, 40.00);

        $response = $this->getJson('/api/itineraries/' . $itinerary->slug);

        $response->assertOk();
        $payload = $response->json('data');
        $this->assertArrayHasKey('schedule_total_price', $payload);
        $this->assertSame(140.00, (float) $payload['schedule_total_price']);
    }

    public function test_public_index_includes_schedule_total_price(): void
    {
        $itinerary = $this->seedItineraryWithScheduleSum(60.00, 20.00);

        $response = $this->getJson('/api/itineraries');

        $response->assertOk();
        $items = $response->json('data');
        $this->assertNotEmpty($items);
        $found = collect($items)->firstWhere('id', $itinerary->id);
        $this->assertNotNull($found, 'Seeded itinerary missing from public index response');
        $this->assertArrayHasKey('schedule_total_price', $found);
        $this->assertSame(80.00, (float) $found['schedule_total_price']);
    }
}
