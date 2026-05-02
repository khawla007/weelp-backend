<?php

namespace Tests\Feature\Payment;

use App\Models\Activity;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StripeWebhookReplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.webhook_secret' => 'whsec_test_replay']);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    private function createOrderWithPayment(string $paymentIntentId): array
    {
        $user = User::factory()->customer()->create();
        $activity = Activity::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'orderable_type' => Activity::class,
            'orderable_id' => $activity->id,
            'travel_date' => now()->addWeek()->format('Y-m-d'),
            'preferred_time' => '10:00',
            'number_of_adults' => 2,
            'number_of_children' => 0,
            'status' => 'pending',
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

    private function mockStripeEvent(string $eventId, string $type, string $intentId): void
    {
        $event = (object) [
            'id' => $eventId,
            'type' => $type,
            'data' => (object) [
                'object' => (object) ['id' => $intentId],
            ],
        ];

        $mock = \Mockery::mock('alias:\Stripe\Webhook');
        $mock->shouldReceive('constructEvent')->andReturn($event);
    }

    private function postWebhook(string $eventId, string $type, string $intentId)
    {
        return $this->call(
            'POST',
            '/api/stripe/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => 'sig_'.$eventId,
            ],
            json_encode([
                'id' => $eventId,
                'type' => $type,
                'data' => ['object' => ['id' => $intentId]],
            ])
        );
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function test_replayed_webhook_returns_already_processed_without_remutation(): void
    {
        Mail::fake();

        $intentId = 'pi_replay_'.uniqid();
        $eventId = 'evt_replay_'.uniqid();
        $data = $this->createOrderWithPayment($intentId);

        $this->mockStripeEvent($eventId, 'payment_intent.succeeded', $intentId);

        $first = $this->postWebhook($eventId, 'payment_intent.succeeded', $intentId);
        $first->assertOk();

        $this->assertDatabaseHas('order_payments', [
            'id' => $data['payment']->id,
            'payment_status' => 'paid',
        ]);
        $this->assertSame(1, DB::table('stripe_webhook_events')->where('id', $eventId)->count());

        Mail::fake();

        $data['payment']->refresh();
        $data['payment']->update(['payment_status' => 'pending']);
        $data['order']->refresh();
        $data['order']->update(['status' => 'pending']);

        $second = $this->postWebhook($eventId, 'payment_intent.succeeded', $intentId);
        $second->assertOk();
        $second->assertJson(['already_processed' => true]);

        $this->assertDatabaseHas('order_payments', [
            'id' => $data['payment']->id,
            'payment_status' => 'pending',
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $data['order']->id,
            'status' => 'pending',
        ]);
        $this->assertSame(1, DB::table('stripe_webhook_events')->where('id', $eventId)->count());
    }

    public function test_unique_index_prevents_duplicate_payment_intent_id(): void
    {
        $base = $this->createOrderWithPayment('pi_unique_'.uniqid());

        $this->expectException(\Illuminate\Database\QueryException::class);

        OrderPayment::create([
            'order_id' => $base['order']->id,
            'payment_status' => 'pending',
            'payment_method' => 'credit_card',
            'amount' => 50.00,
            'total_amount' => 50.00,
            'currency' => 'USD',
            'payment_intent_id' => $base['payment']->payment_intent_id,
        ]);
    }
}
