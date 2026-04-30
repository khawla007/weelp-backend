<?php

namespace Tests\Feature\Payment;

use App\Models\Activity;
use App\Models\Order;
use App\Models\OrderEmergencyContact;
use App\Models\OrderPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function validOrderPayload(User $user, Activity $activity): array
    {
        return [
            'order_type' => 'activity',
            'orderable_id' => $activity->id,
            'travel_date' => now()->addWeek()->format('Y-m-d'),
            'preferred_time' => '10:00',
            'number_of_adults' => 2,
            'number_of_children' => 1,
            'special_requirements' => 'None',
            'user_id' => $user->id,
            'customer_email' => 'customer@example.com',
            'amount' => 150.00,
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

    public function test_create_order_for_activity(): void
    {
        Mail::fake();

        $user = User::factory()->customer()->create();
        $this->actingAs($user, "api");
        $activity = Activity::factory()->create();
        $payload = $this->validOrderPayload($user, $activity);

        $response = $this->postJson('/api/stripe/create-order', $payload);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'order_id']);

        // Verify order was created in DB
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'orderable_type' => 'App\\Models\\Activity',
            'orderable_id' => $activity->id,
            'number_of_adults' => 2,
            'number_of_children' => 1,
        ]);

        // Verify emergency contact was created
        $orderId = $response->json('order_id');
        $this->assertDatabaseHas('order_emergency_contacts', [
            'order_id' => $orderId,
            'contact_name' => 'Emergency Contact',
            'contact_phone' => '+1234567890',
            'relationship' => 'spouse',
        ]);

        // Verify payment record was created
        $this->assertDatabaseHas('order_payments', [
            'order_id' => $orderId,
            'payment_status' => 'pending',
            'payment_method' => 'credit_card',
            'amount' => 150.00,
            'currency' => 'USD',
            'payment_intent_id' => $payload['payment_intent_id'],
        ]);
    }

    public function test_create_order_fails_with_invalid_data(): void
    {
        $response = $this->postJson('/api/stripe/create-order', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'order_type',
                'orderable_id',
                'travel_date',
                'preferred_time',
                'number_of_adults',
                'number_of_children',
                'user_id',
                'customer_email',
                'amount',
                'is_custom_amount',
                'currency',
                'payment_intent_id',
                'emergency_contact.name',
                'emergency_contact.phone',
                'emergency_contact.relationship',
            ]);
    }

    public function test_create_order_fails_with_nonexistent_orderable(): void
    {
        $user = User::factory()->customer()->create();
        $this->actingAs($user, "api");
        $activity = Activity::factory()->create();
        $payload = $this->validOrderPayload($user, $activity);
        $payload['orderable_id'] = 99999;

        $response = $this->postJson('/api/stripe/create-order', $payload);

        // findOrFail throws ModelNotFoundException -> 404
        $response->assertNotFound();
    }

    /**
     * createCheckoutSession calls Stripe SDK directly.
     * We mock \Stripe\Checkout\Session::create to avoid real API calls.
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function test_create_checkout_session_for_activity(): void
    {
        Mail::fake();

        $user = User::factory()->customer()->create();
        $this->actingAs($user, "api");
        $activity = Activity::factory()->create();

        // Mock Stripe SDK
        $mockSession = new \stdClass();
        $mockSession->id = 'cs_test_mock_session_123';
        $mockSession->url = 'https://checkout.stripe.com/pay/cs_test_mock_session_123';

        $stripeMock = \Mockery::mock('alias:\Stripe\Checkout\Session');
        $stripeMock->shouldReceive('create')->once()->andReturn($mockSession);

        $stripeSdkMock = \Mockery::mock('alias:\Stripe\Stripe');
        $stripeSdkMock->shouldReceive('setApiKey')->once();

        $payload = [
            'order_type' => 'activity',
            'orderable_id' => $activity->id,
            'travel_date' => now()->addWeek()->format('Y-m-d'),
            'preferred_time' => '10:00',
            'number_of_adults' => 2,
            'number_of_children' => 1,
            'special_requirements' => 'None',
            'user_id' => $user->id,
            'customer_email' => 'customer@example.com',
            'amount' => 150.00,
            'is_custom_amount' => false,
            'custom_amount' => null,
            'currency' => 'USD',
            'emergency_contact' => [
                'name' => 'Emergency Contact',
                'phone' => '+1234567890',
                'relationship' => 'spouse',
            ],
        ];

        $response = $this->postJson('/api/create-checkout-session', $payload);

        $response->assertOk()
            ->assertJson([
                'id' => 'cs_test_mock_session_123',
                'url' => 'https://checkout.stripe.com/pay/cs_test_mock_session_123',
            ]);

        // Verify order and payment were created in DB
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'orderable_type' => 'App\\Models\\Activity',
            'orderable_id' => $activity->id,
        ]);

        $order = Order::where('user_id', $user->id)->first();
        $this->assertDatabaseHas('order_payments', [
            'order_id' => $order->id,
            'stripe_session_id' => 'cs_test_mock_session_123',
            'payment_status' => 'pending',
        ]);
    }

    public function test_create_checkout_session_fails_with_missing_fields(): void
    {
        $response = $this->postJson('/api/create-checkout-session', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'order_type',
                'orderable_id',
                'travel_date',
                'preferred_time',
                'number_of_adults',
                'number_of_children',
                'user_id',
                'customer_email',
                'amount',
                'is_custom_amount',
                'currency',
                'emergency_contact.name',
                'emergency_contact.phone',
                'emergency_contact.relationship',
            ]);
    }
}
