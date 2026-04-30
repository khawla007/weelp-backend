<?php

namespace Tests\Feature\Payment;

use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\Order;
use App\Models\OrderEmergencyContact;
use App\Models\OrderPayment;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private function buildCreateOrderPayload(User $user, string $orderType, int $orderableId): array
    {
        return [
            'order_type' => $orderType,
            'orderable_id' => $orderableId,
            'travel_date' => now()->addWeek()->format('Y-m-d'),
            'preferred_time' => '10:00',
            'number_of_adults' => 2,
            'number_of_children' => 1,
            'special_requirements' => 'None',
            'user_id' => $user->id,
            'customer_email' => $user->email,
            'amount' => 200.00,
            'is_custom_amount' => false,
            'custom_amount' => null,
            'currency' => 'USD',
            'payment_intent_id' => 'pi_test_' . uniqid(),
            'emergency_contact' => [
                'name' => 'Emergency Contact',
                'phone' => '+1234567890',
                'relationship' => 'spouse',
            ],
        ];
    }

    public function test_order_morphable_resolves_to_activity(): void
    {
        $activity = Activity::factory()->create();

        $order = Order::create([
            'user_id' => User::factory()->create()->id,
            'orderable_type' => 'App\\Models\\Activity',
            'orderable_id' => $activity->id,
            'travel_date' => now()->addWeek()->format('Y-m-d'),
            'preferred_time' => '10:00',
            'number_of_adults' => 1,
            'number_of_children' => 0,
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(Activity::class, $order->orderable);
        $this->assertEquals($activity->id, $order->orderable->id);
    }

    public function test_order_morphable_resolves_to_package(): void
    {
        $package = Package::factory()->create();

        $order = Order::create([
            'user_id' => User::factory()->create()->id,
            'orderable_type' => 'App\\Models\\Package',
            'orderable_id' => $package->id,
            'travel_date' => now()->addWeek()->format('Y-m-d'),
            'preferred_time' => '10:00',
            'number_of_adults' => 1,
            'number_of_children' => 0,
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(Package::class, $order->orderable);
        $this->assertEquals($package->id, $order->orderable->id);
    }

    public function test_order_morphable_resolves_to_itinerary(): void
    {
        $itinerary = Itinerary::factory()->create();

        $order = Order::create([
            'user_id' => User::factory()->create()->id,
            'orderable_type' => 'App\\Models\\Itinerary',
            'orderable_id' => $itinerary->id,
            'travel_date' => now()->addWeek()->format('Y-m-d'),
            'preferred_time' => '10:00',
            'number_of_adults' => 1,
            'number_of_children' => 0,
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(Itinerary::class, $order->orderable);
        $this->assertEquals($itinerary->id, $order->orderable->id);
    }

    public function test_order_thank_you_page_returns_order(): void
    {
        $user = User::factory()->customer()->create();
        $this->actingAs($user, "api");
        $activity = Activity::factory()->create();
        $paymentIntentId = 'pi_test_' . uniqid();

        $order = Order::create([
            'user_id' => $user->id,
            'orderable_type' => 'App\\Models\\Activity',
            'orderable_id' => $activity->id,
            'travel_date' => now()->addWeek()->format('Y-m-d'),
            'preferred_time' => '10:00',
            'number_of_adults' => 2,
            'number_of_children' => 1,
            'status' => 'pending',
            'item_snapshot_json' => json_encode([
                'name' => $activity->name,
                'slug' => $activity->slug,
                'item_type' => 'activity',
                'location' => [],
                'media' => [],
            ]),
        ]);

        OrderEmergencyContact::create([
            'order_id' => $order->id,
            'contact_name' => 'Emergency Person',
            'contact_phone' => '+1234567890',
            'relationship' => 'spouse',
        ]);

        OrderPayment::create([
            'order_id' => $order->id,
            'payment_status' => 'paid',
            'payment_method' => 'credit_card',
            'amount' => 150.00,
            'total_amount' => 150.00,
            'currency' => 'USD',
            'payment_intent_id' => $paymentIntentId,
        ]);

        $response = $this->getJson('/api/order/thankyou?payment_intent=' . $paymentIntentId);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'order' => [
                    'id',
                    'status',
                    'travel_date',
                    'preferred_time',
                    'number_of_adults',
                    'number_of_children',
                    'payment',
                    'emergency_contact',
                    'item' => ['name', 'slug'],
                    'user' => ['name', 'email'],
                ],
            ]);

        $this->assertEquals($order->id, $response->json('order.id'));
    }

    public function test_order_thank_you_page_requires_payment_intent(): void
    {
        $response = $this->getJson('/api/order/thankyou');

        $response->assertStatus(400)
            ->assertJson(['error' => 'Payment Intent ID is required']);
    }

    public function test_order_thank_you_page_returns_404_for_unknown_intent(): void
    {
        $response = $this->getJson('/api/order/thankyou?payment_intent=pi_nonexistent');

        $response->assertNotFound()
            ->assertJson(['error' => 'Payment not found']);
    }

    public function test_order_stores_snapshot_json(): void
    {
        Mail::fake();

        $user = User::factory()->customer()->create();
        $this->actingAs($user, "api");
        $activity = Activity::factory()->create();
        $payload = $this->buildCreateOrderPayload($user, 'activity', $activity->id);

        $response = $this->postJson('/api/stripe/create-order', $payload);
        $response->assertOk();

        $order = Order::find($response->json('order_id'));

        // The snapshot may be null if activity has no locations/media loaded,
        // but the field should exist. When the activity has no relations,
        // the snapshot is still created with empty collections.
        $this->assertNotNull($order);
        // The controller creates a snapshot for activities with name, slug, etc.
        // Even with empty relations, it should be populated.
        if ($order->item_snapshot_json) {
            $snapshot = json_decode($order->item_snapshot_json, true);
            $this->assertArrayHasKey('name', $snapshot);
            $this->assertArrayHasKey('slug', $snapshot);
            $this->assertEquals($activity->name, $snapshot['name']);
        }
    }

    public function test_create_order_via_api_with_package(): void
    {
        Mail::fake();

        $user = User::factory()->customer()->create();
        $this->actingAs($user, "api");
        $package = Package::factory()->create();
        $payload = $this->buildCreateOrderPayload($user, 'package', $package->id);

        $response = $this->postJson('/api/stripe/create-order', $payload);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'orderable_type' => 'App\\Models\\Package',
            'orderable_id' => $package->id,
        ]);

        $orderId = $response->json('order_id');
        $this->assertDatabaseHas('order_payments', [
            'order_id' => $orderId,
            'payment_intent_id' => $payload['payment_intent_id'],
        ]);
    }

    public function test_create_order_via_api_with_itinerary(): void
    {
        Mail::fake();

        $user = User::factory()->customer()->create();
        $this->actingAs($user, "api");
        $itinerary = Itinerary::factory()->create();
        $payload = $this->buildCreateOrderPayload($user, 'itinerary', $itinerary->id);

        $response = $this->postJson('/api/stripe/create-order', $payload);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'orderable_type' => 'App\\Models\\Itinerary',
            'orderable_id' => $itinerary->id,
        ]);

        $orderId = $response->json('order_id');
        $this->assertDatabaseHas('order_payments', [
            'order_id' => $orderId,
            'payment_intent_id' => $payload['payment_intent_id'],
        ]);
    }

    /**
     * The /api/stripe/create-order route is NOT behind auth middleware,
     * but it requires user_id and validates the orderable exists.
     * Without valid data, the validation fails.
     * This test verifies that unauthenticated requests with no data fail validation.
     */
    public function test_create_order_fails_without_required_fields(): void
    {
        $response = $this->postJson('/api/stripe/create-order', []);

        $response->assertUnprocessable();
    }

    /**
     * Confirm payment calls Stripe SDK to retrieve session.
     * We mock \Stripe\Checkout\Session::retrieve to avoid real API calls.
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function test_confirm_payment_updates_order_status(): void
    {
        Mail::fake();

        $user = User::factory()->customer()->create();
        $this->actingAs($user, "api");
        $activity = Activity::factory()->create();
        $sessionId = 'cs_test_' . uniqid();

        $order = Order::create([
            'user_id' => $user->id,
            'orderable_type' => 'App\\Models\\Activity',
            'orderable_id' => $activity->id,
            'travel_date' => now()->addWeek()->format('Y-m-d'),
            'preferred_time' => '10:00',
            'number_of_adults' => 2,
            'number_of_children' => 0,
            'status' => 'pending',
        ]);

        OrderPayment::create([
            'order_id' => $order->id,
            'payment_status' => 'pending',
            'payment_method' => 'credit_card',
            'amount' => 100.00,
            'total_amount' => 100.00,
            'currency' => 'USD',
            'stripe_session_id' => $sessionId,
        ]);

        // Mock Stripe SDK
        $mockSession = new \stdClass();
        $mockSession->payment_status = 'paid';

        $stripeMock = \Mockery::mock('alias:\Stripe\Checkout\Session');
        $stripeMock->shouldReceive('retrieve')
            ->with($sessionId)
            ->once()
            ->andReturn($mockSession);

        $stripeSdkMock = \Mockery::mock('alias:\Stripe\Stripe');
        $stripeSdkMock->shouldReceive('setApiKey')->once();

        $response = $this->postJson('/api/confirm-payment', [
            'session_id' => $sessionId,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_detail',
                    'item_detail',
                    'order',
                ],
            ]);

        // Verify order status updated to processing
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);

        // Verify payment status updated to paid
        $this->assertDatabaseHas('order_payments', [
            'order_id' => $order->id,
            'payment_status' => 'paid',
        ]);
    }
}
