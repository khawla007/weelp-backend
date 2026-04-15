<?php

namespace Tests\Feature;

use App\Models\Itinerary;
use App\Models\ItinerarySchedule;
use App\Models\ItineraryActivity;
use App\Models\ItineraryTransfer;
use App\Models\ItineraryBasePricing;
use App\Models\Activity;
use App\Models\Transfer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminItineraryOrderTest extends TestCase
{
    use RefreshDatabase;

    private function seedItinerary(float $activityTotal, float $transferTotal): Itinerary
    {
        $itinerary = Itinerary::factory()->create();

        ItineraryBasePricing::create([
            'itinerary_id' => $itinerary->id,
            'currency' => 'USD',
            'availability' => 'year_round',
        ]);

        $schedule = ItinerarySchedule::create([
            'itinerary_id' => $itinerary->id,
            'day' => 1,
            'title' => 'Day 1',
        ]);

        $activity = Activity::create([
            'name' => 'Test activity ' . $itinerary->id,
            'slug' => 'test-activity-' . $itinerary->id . '-' . uniqid(),
        ]);
        $transfer = Transfer::create([
            'name' => 'Test transfer ' . $itinerary->id,
            'slug' => 'test-transfer-' . $itinerary->id . '-' . uniqid(),
            'description' => 'test',
            'transfer_type' => 'car',
        ]);

        ItineraryActivity::create([
            'schedule_id' => $schedule->id,
            'activity_id' => $activity->id,
            'price' => $activityTotal,
            'included' => true,
        ]);
        ItineraryTransfer::create([
            'schedule_id' => $schedule->id,
            'transfer_id' => $transfer->id,
            'price' => $transferTotal,
            'included' => true,
        ]);

        return $itinerary->fresh(['schedules.activities', 'schedules.transfers']);
    }

    public function test_admin_order_store_overrides_total_amount_for_itinerary(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $itinerary = $this->seedItinerary(120.00, 30.00); // server total = 150.00

        $payload = [
            'user_id' => $customer->id,
            'orderable_type' => 'itinerary',
            'orderable_id' => $itinerary->id,
            'travel_date' => now()->addDays(10)->toDateString(),
            'preferred_time' => '10:00:00',
            'number_of_adults' => 2,
            'number_of_children' => 0,
            'status' => 'pending',
            'special_requirements' => null,
            'payment' => [
                'payment_status' => 'pending',
                'payment_method' => 'credit_card',
                'total_amount' => 5.00, // tampered
                'is_custom_amount' => false,
                'custom_amount' => 0,
            ],
            'emergency_contact' => [
                'contact_name' => 'Jane Doe',
                'contact_phone' => '+14155550199',
                'relationship' => 'spouse',
            ],
        ];

        $response = $this->actingAs($admin, 'api')->postJson('/api/admin/orders', $payload);

        $response->assertStatus(201);

        $order = Order::with('payment')->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertNotNull($order->payment);
        $this->assertSame(150.00, (float) $order->payment->total_amount,
            'Admin-created itinerary orders must use server-computed schedule_total_price');
    }
}
