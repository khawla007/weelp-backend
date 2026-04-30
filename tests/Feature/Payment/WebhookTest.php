<?php

namespace Tests\Feature\Payment;

use App\Models\Activity;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to create an order with payment record for webhook tests.
     */
    private function createOrderWithPayment(string $paymentIntentId, string $status = 'pending'): array
    {
        $user = User::factory()->customer()->create();
        $this->actingAs($user, "api");
        $activity = Activity::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'orderable_type' => 'App\\Models\\Activity',
            'orderable_id' => $activity->id,
            'travel_date' => now()->addWeek()->format('Y-m-d'),
            'preferred_time' => '10:00',
            'number_of_adults' => 2,
            'number_of_children' => 0,
            'status' => $status,
        ]);

        $payment = OrderPayment::create([
            'order_id' => $order->id,
            'payment_status' => 'pending',
            'payment_method' => 'credit_card',
            'amount' => 100.00,
            'total_amount' => 100.00,
            'currency' => 'USD',
            'payment_intent_id' => $paymentIntentId,
        ]);

        return ['order' => $order, 'payment' => $payment, 'user' => $user];
    }

    /**
     * Build a webhook payload that matches what Stripe sends.
     * The controller json_decodes the payload when no webhook secret is configured.
     */
    private function buildWebhookPayload(string $type, string $paymentIntentId): string
    {
        $data = [
            'type' => $type,
            'data' => [
                'object' => [
                    'id' => $paymentIntentId,
                ],
            ],
        ];

        // For charge.refunded events, the payment_intent is nested differently
        if ($type === 'charge.refunded') {
            $data['data']['object'] = [
                'id' => 'ch_test_' . uniqid(),
                'payment_intent' => $paymentIntentId,
            ];
        }

        return json_encode($data);
    }

    /**
     * The webhook handler accepts raw JSON when no webhook secret is configured.
     * In testing, we don't set STRIPE_WEBHOOK_SECRET so it falls through
     * to the json_decode path (lines 153-155 of StripeController).
     */
    public function test_webhook_handles_payment_success(): void
    {
        Mail::fake();

        $intentId = 'pi_test_' . uniqid();
        $data = $this->createOrderWithPayment($intentId);
        $payload = $this->buildWebhookPayload('payment_intent.succeeded', $intentId);

        $response = $this->call(
            'POST',
            '/api/stripe/webhook',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $response->assertOk();

        // Verify payment status updated
        $this->assertDatabaseHas('order_payments', [
            'id' => $data['payment']->id,
            'payment_status' => 'paid',
        ]);

        // Verify order status updated
        $this->assertDatabaseHas('orders', [
            'id' => $data['order']->id,
            'status' => 'processing',
        ]);
    }

    /**
     * Known issue: The payment_status enum in order_payments only allows
     * ['pending', 'partial', 'paid', 'refunded']. The webhook handler
     * tries to set 'failed' which violates the DB constraint.
     * On MySQL this may silently work; on SQLite it raises a CHECK error.
     * This test documents the bug by asserting the 500 response.
     */
    public function test_webhook_handles_payment_failure(): void
    {
        Mail::fake();

        $intentId = 'pi_test_' . uniqid();
        $data = $this->createOrderWithPayment($intentId);
        $payload = $this->buildWebhookPayload('payment_intent.payment_failed', $intentId);

        $response = $this->call(
            'POST',
            '/api/stripe/webhook',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $payload
        );

        // BUG: 'failed' is not in the order_payments.payment_status enum.
        // MySQL may silently accept it; SQLite rejects it with CHECK constraint.
        // When the DB schema is fixed to include 'failed', change this to assertOk().
        $response->assertServerError();
    }

    /**
     * When Stripe signature header is present but webhook secret is set,
     * an invalid signature should return 400.
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function test_webhook_rejects_invalid_signature(): void
    {
        // Mock the Stripe\Webhook class to throw SignatureVerificationException
        $mockWebhook = \Mockery::mock('alias:\Stripe\Webhook');
        $mockWebhook->shouldReceive('constructEvent')
            ->andThrow(new \Stripe\Exception\SignatureVerificationException('Invalid signature'));

        // Set a webhook secret so the signature verification path is triggered
        config(['services.stripe.webhook_secret' => 'whsec_test_secret']);

        $payload = json_encode([
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_test_fake']],
        ]);

        $response = $this->call(
            'POST',
            '/api/stripe/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => 'invalid_signature_here',
            ],
            $payload
        );

        $response->assertStatus(400);
    }

    public function test_webhook_handles_unknown_event_type(): void
    {
        Mail::fake();

        $payload = $this->buildWebhookPayload('some.unknown.event', 'pi_test_unknown');

        $response = $this->call(
            'POST',
            '/api/stripe/webhook',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $payload
        );

        // Controller returns 200 for unhandled event types (falls through all elseif)
        $response->assertOk();
    }

    public function test_webhook_is_idempotent(): void
    {
        Mail::fake();

        $intentId = 'pi_test_' . uniqid();
        $data = $this->createOrderWithPayment($intentId);
        $payload = $this->buildWebhookPayload('payment_intent.succeeded', $intentId);

        // Send first time
        $response1 = $this->call(
            'POST',
            '/api/stripe/webhook',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $payload
        );
        $response1->assertOk();

        // Send second time - should not cause errors
        $response2 = $this->call(
            'POST',
            '/api/stripe/webhook',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $payload
        );
        $response2->assertOk();

        // Order should still be in processing state
        $this->assertDatabaseHas('orders', [
            'id' => $data['order']->id,
            'status' => 'processing',
        ]);

        // Payment should still be paid
        $this->assertDatabaseHas('order_payments', [
            'id' => $data['payment']->id,
            'payment_status' => 'paid',
        ]);
    }

    public function test_webhook_handles_charge_refunded(): void
    {
        Mail::fake();

        $intentId = 'pi_test_' . uniqid();
        $data = $this->createOrderWithPayment($intentId);

        // First mark as paid
        $data['payment']->update(['payment_status' => 'paid']);
        $data['order']->update(['status' => 'processing']);

        $payload = $this->buildWebhookPayload('charge.refunded', $intentId);

        $response = $this->call(
            'POST',
            '/api/stripe/webhook',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $response->assertOk();

        $this->assertDatabaseHas('order_payments', [
            'id' => $data['payment']->id,
            'payment_status' => 'refunded',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $data['order']->id,
            'status' => 'refunded',
        ]);
    }

    /**
     * Known issue: Same as test_webhook_handles_payment_failure.
     * The payment_status enum does not include 'cancelled'.
     * This test documents the bug.
     */
    public function test_webhook_handles_payment_canceled(): void
    {
        Mail::fake();

        $intentId = 'pi_test_' . uniqid();
        $data = $this->createOrderWithPayment($intentId);
        $payload = $this->buildWebhookPayload('payment_intent.canceled', $intentId);

        $response = $this->call(
            'POST',
            '/api/stripe/webhook',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $payload
        );

        // BUG: 'cancelled' is not in the order_payments.payment_status enum.
        // When the DB schema is fixed to include 'cancelled', change this to assertOk().
        $response->assertServerError();
    }

    public function test_webhook_returns_404_for_unknown_payment_intent(): void
    {
        Mail::fake();

        $payload = $this->buildWebhookPayload('payment_intent.succeeded', 'pi_test_nonexistent');

        $response = $this->call(
            'POST',
            '/api/stripe/webhook',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $response->assertNotFound()
            ->assertJson(['error' => 'Payment record not found']);
    }
}
