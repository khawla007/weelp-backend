<?php

namespace Tests\Feature\Payment;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderThankYouOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private function makePayment(Order $order, string $piSuffix): OrderPayment
    {
        return OrderPayment::factory()->for($order)->create([
            'payment_intent_id' => 'pi_test_'.$piSuffix.'_'.uniqid(),
            'payment_method' => 'credit_card',
        ]);
    }

    public function test_owner_can_view_order(): void
    {
        $user = User::factory()->customer()->create();
        $order = Order::factory()->for($user)->create();
        $payment = $this->makePayment($order, 'owner');

        $this->actingAs($user, 'api')
            ->getJson('/api/order/thankyou?payment_intent='.$payment->payment_intent_id)
            ->assertOk();
    }

    public function test_other_user_is_forbidden(): void
    {
        $owner = User::factory()->customer()->create();
        $stranger = User::factory()->customer()->create();
        $order = Order::factory()->for($owner)->create();
        $payment = $this->makePayment($order, 'stranger');

        $this->actingAs($stranger, 'api')
            ->getJson('/api/order/thankyou?payment_intent='.$payment->payment_intent_id)
            ->assertForbidden();
    }

    public function test_unauthenticated_is_unauthorized(): void
    {
        $owner = User::factory()->customer()->create();
        $order = Order::factory()->for($owner)->create();
        $payment = $this->makePayment($order, 'unauth');

        $this->getJson('/api/order/thankyou?payment_intent='.$payment->payment_intent_id)
            ->assertUnauthorized();
    }
}
