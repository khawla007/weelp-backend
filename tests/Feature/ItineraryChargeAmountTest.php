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

class ItineraryChargeAmountTest extends TestCase
{
    use RefreshDatabase;

    private function seedItinerary(float $activityTotal, float $transferTotal): Itinerary
    {
        $itinerary = Itinerary::factory()->create();

        // Required to survive the snapshot block in StripeController@createOrder
        // (reads $itinerary->basePricing->priceVariations without null-safe chain).
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

    public function test_createOrder_uses_server_computed_total_for_itinerary_ignoring_tampered_amount(): void
    {
        $user = User::factory()->create();
        $itinerary = $this->seedItinerary(200.00, 50.00); // server total = 250.00

        $payload = [
            'order_type' => 'itinerary',
            'orderable_id' => $itinerary->id,
            'travel_date' => now()->addDays(10)->toDateString(),
            'preferred_time' => '10:00',
            'number_of_adults' => 2,
            'number_of_children' => 0,
            'special_requirements' => null,
            'user_id' => $user->id,
            'customer_email' => $user->email,
            'amount' => 1.00, // tampered — attacker tries $1
            'is_custom_amount' => false,
            'custom_amount' => null,
            'currency' => 'usd',
            'payment_intent_id' => 'pi_test_tamper_12345',
            'emergency_contact' => [
                'name' => 'Jane Doe',
                'phone' => '+14155550199',
                'relationship' => 'spouse',
            ],
        ];

        $response = $this->postJson('/api/stripe/create-order', $payload);

        $response->assertSuccessful();
        $orderId = $response->json('order_id');
        $this->assertNotNull($orderId);

        $order = Order::with('payment')->find($orderId);
        $this->assertNotNull($order);
        $this->assertNotNull($order->payment, 'Expected OrderPayment row');
        $this->assertSame(250.00, (float) $order->payment->total_amount,
            'Server-computed schedule_total_price must override client-supplied amount for itineraries');
    }

    public function test_createOrder_keeps_client_amount_for_non_itinerary_products(): void
    {
        $user = User::factory()->create();
        $activity = Activity::create([
            'name' => 'Bungee jump',
            'slug' => 'bungee-jump-' . uniqid(),
        ]);

        $payload = [
            'order_type' => 'activity',
            'orderable_id' => $activity->id,
            'travel_date' => now()->addDays(10)->toDateString(),
            'preferred_time' => '10:00',
            'number_of_adults' => 1,
            'number_of_children' => 0,
            'special_requirements' => null,
            'user_id' => $user->id,
            'customer_email' => $user->email,
            'amount' => 99.00, // legitimate client amount
            'is_custom_amount' => false,
            'custom_amount' => null,
            'currency' => 'usd',
            'payment_intent_id' => 'pi_test_activity_67890',
            'emergency_contact' => [
                'name' => 'Jane Doe',
                'phone' => '+14155550199',
                'relationship' => 'spouse',
            ],
        ];

        $response = $this->postJson('/api/stripe/create-order', $payload);

        $response->assertSuccessful();
        $orderId = $response->json('order_id');
        $order = Order::with('payment')->find($orderId);
        $this->assertSame(99.00, (float) $order->payment->total_amount,
            'Non-itinerary orders must keep the client-supplied amount');
    }
}
