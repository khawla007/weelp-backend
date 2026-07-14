<?php

namespace Tests\Feature\Payment;

use App\Models\Activity;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutSessionConfirmationOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private function createPayment(User $owner, string $sessionId): array
    {
        $activity = Activity::factory()->create();
        $order = Order::create([
            'user_id' => $owner->id,
            'orderable_type' => Activity::class,
            'orderable_id' => $activity->id,
            'travel_date' => now()->addWeek()->toDateString(),
            'preferred_time' => '10:00',
            'number_of_adults' => 1,
            'number_of_children' => 0,
            'status' => 'pending',
        ]);
        $payment = OrderPayment::create([
            'order_id' => $order->id,
            'payment_status' => 'pending',
            'payment_method' => 'credit_card',
            'amount' => 100,
            'total_amount' => 100,
            'currency' => 'USD',
            'stripe_session_id' => $sessionId,
        ]);

        return [$order, $payment];
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function test_foreign_session_is_indistinguishable_from_not_found_and_is_not_retrieved(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        [$order, $payment] = $this->createPayment($owner, 'cs_test_foreign');
        $this->actingAs($otherUser, 'api');

        $stripeSession = \Mockery::mock('alias:\Stripe\Checkout\Session');
        $stripeSession->shouldNotReceive('retrieve');
        $stripeSdk = \Mockery::mock('alias:\Stripe\Stripe');
        $stripeSdk->shouldNotReceive('setApiKey');

        $response = $this->postJson('/api/confirm-payment', ['session_id' => 'cs_test_foreign']);

        $response->assertNotFound()->assertExactJson(['error' => 'Payment confirmation not found']);
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('pending', $payment->fresh()->payment_status);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function test_owner_can_confirm_paid_session(): void
    {
        $owner = User::factory()->create();
        [$order] = $this->createPayment($owner, 'cs_test_owner');
        $this->actingAs($owner, 'api');
        $session = (object) ['payment_status' => 'paid'];
        $stripeSession = \Mockery::mock('alias:\Stripe\Checkout\Session');
        $stripeSession->shouldReceive('retrieve')->once()->with('cs_test_owner')->andReturn($session);
        $stripeSdk = \Mockery::mock('alias:\Stripe\Stripe');
        $stripeSdk->shouldReceive('setApiKey')->once();

        $response = $this->postJson('/api/confirm-payment', ['session_id' => 'cs_test_owner']);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSame('processing', $order->fresh()->status);
        $this->assertSame('paid', $order->payment->fresh()->payment_status);
    }
}
