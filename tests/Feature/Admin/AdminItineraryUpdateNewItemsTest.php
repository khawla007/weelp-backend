<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\ItineraryController;
use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\ItineraryActivity;
use App\Models\ItinerarySchedule;
use App\Models\ItineraryTransfer;
use App\Models\Transfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AdminItineraryUpdateNewItemsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression: PUT /api/admin/itineraries/{id} must persist newly added
     * schedule days, activities, and transfers (rows without an `id` field).
     * Earlier `$updateOrCreateSimple` only updated existing rows, silently
     * dropping new ones.
     */
    public function test_update_creates_newly_added_day_activity_and_transfer(): void
    {
        $itinerary = Itinerary::factory()->create();

        $existingSchedule = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 1,
            'title' => 'Day 1',
        ]);

        $catalogActivity = Activity::create([
            'name' => 'Catalog activity ' . uniqid(),
            'slug' => 'catalog-activity-' . uniqid(),
            'item_type' => 'activity',
        ]);
        $catalogTransfer = Transfer::create([
            'name' => 'Catalog transfer ' . uniqid(),
            'slug' => 'catalog-transfer-' . uniqid(),
            'description' => 'desc',
            'item_type' => 'transfer',
            'transfer_type' => 'private',
        ]);

        // Existing activity and transfer on day 1 (will be retained)
        $existingActivity = ItineraryActivity::create([
            'schedule_id' => $existingSchedule->id,
            'activity_id' => $catalogActivity->id,
            'price' => 50.00,
            'included' => true,
        ]);
        $existingTransfer = ItineraryTransfer::create([
            'schedule_id' => $existingSchedule->id,
            'transfer_id' => $catalogTransfer->id,
            'price' => 30.00,
            'included' => true,
        ]);

        // Build the form payload that mimics what EditItineraryForm submits:
        // - Existing schedule (id present) on day 1 + brand new schedule (no id) on day 2
        // - Existing activity (id present) + brand new activity (no id) on day 2
        // - Existing transfer (id present) + brand new transfer (no id) on day 2
        $payload = [
            'name' => $itinerary->name,
            'slug' => $itinerary->slug,
            'schedules' => [
                ['id' => $existingSchedule->id, 'day' => 1, 'title' => 'Day 1'],
                ['day' => 2, 'title' => 'Day 2'], // NEW
            ],
            'activities' => [
                [
                    'id' => $existingActivity->id,
                    'day' => 1,
                    'activity_id' => $catalogActivity->id,
                    'price' => 50.00,
                    'included' => true,
                ],
                [
                    // NEW — no id
                    'day' => 2,
                    'activity_id' => $catalogActivity->id,
                    'price' => 75.00,
                    'included' => true,
                    'activitydata' => ['id' => $catalogActivity->id, 'name' => 'blob'],
                ],
            ],
            'transfers' => [
                [
                    'id' => $existingTransfer->id,
                    'day' => 1,
                    'transfer_id' => $catalogTransfer->id,
                    'price' => 30.00,
                    'included' => true,
                ],
                [
                    // NEW — no id
                    'day' => 2,
                    'transfer_id' => $catalogTransfer->id,
                    'price' => 45.00,
                    'included' => true,
                    'transferData' => ['id' => $catalogTransfer->id, 'name' => 'blob'],
                ],
            ],
        ];

        $request = Request::create(
            "/api/admin/itineraries/{$itinerary->id}",
            'PUT',
            $payload,
        );

        $response = (new ItineraryController())->update($request, $itinerary->id);

        $this->assertSame(200, $response->getStatusCode(), 'Update should succeed');

        $itinerary->refresh()->load('schedules.activities', 'schedules.transfers');

        $this->assertCount(2, $itinerary->schedules, 'New day should be persisted');
        $allActivities = $itinerary->schedules->flatMap->activities;
        $allTransfers = $itinerary->schedules->flatMap->transfers;
        $this->assertCount(2, $allActivities, 'New activity should be persisted');
        $this->assertCount(2, $allTransfers, 'New transfer should be persisted');

        $day2 = $itinerary->schedules->firstWhere('day', 2);
        $this->assertNotNull($day2);
        $this->assertSame(1, $day2->activities->count());
        $this->assertSame(1, $day2->transfers->count());
        $this->assertSame(75.00, (float) $day2->activities->first()->price);
        $this->assertSame(45.00, (float) $day2->transfers->first()->price);
    }
}
