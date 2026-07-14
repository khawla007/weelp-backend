<?php

namespace Tests\Feature;

use App\Contracts\StripePaymentIntentGateway;
use App\Models\Activity;
use App\Models\ActivityEarlyBirdDiscount;
use App\Models\ActivityGroupDiscount;
use App\Models\ActivityLastMinuteDiscount;
use App\Models\ActivityPricing;
use App\Models\OrderPayment;
use App\Models\User;
use App\Services\CheckoutQuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StripeOrderDiscountTest extends TestCase
{
    use RefreshDatabase;

    private function activity(float $regularPrice = 100): Activity
    {
        $activity = Activity::factory()->create();
        ActivityPricing::factory()->create([
            'activity_id' => $activity->id,
            'regular_price' => $regularPrice,
            'currency' => 'USD',
        ]);

        return $activity;
    }

    private function selection(Activity $activity, int $adults, string $travelDate): array
    {
        return [
            'order_type' => 'activity',
            'orderable_id' => $activity->id,
            'travel_date' => $travelDate,
            'preferred_time' => '10:00',
            'number_of_adults' => $adults,
            'number_of_children' => 0,
        ];
    }

    private function allowIntent(User $user, array $selection, string $paymentIntentId): float
    {
        $quote = app(CheckoutQuoteService::class)->quote($selection);
        $hash = hash('sha256', json_encode($selection, JSON_THROW_ON_ERROR));
        $gateway = Mockery::mock(StripePaymentIntentGateway::class);
        $gateway->shouldReceive('retrieve')->once()->with($paymentIntentId)->andReturn((object) [
            'id' => $paymentIntentId,
            'amount' => (int) round($quote['amount'] * 100),
            'currency' => strtolower($quote['currency']),
            'status' => 'requires_payment_method',
            'metadata' => (object) [
                'user_id' => (string) $user->id,
                'selection_hash' => $hash,
            ],
        ]);
        $this->app->instance(StripePaymentIntentGateway::class, $gateway);

        return $quote['amount'];
    }

    private function payload(array $selection, string $paymentIntentId): array
    {
        return $selection + [
            'payment_intent_id' => $paymentIntentId,
            'amount' => 0.01,
            'base_amount' => 0.01,
            'emergency_contact' => [
                'name' => 'Test Contact',
                'phone' => '+1234567890',
                'relationship' => 'Friend',
            ],
        ];
    }

    public function test_create_order_ignores_tampered_client_price_and_stores_group_discount_quote(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $activity = $this->activity();
        ActivityGroupDiscount::factory()->create([
            'activity_id' => $activity->id,
            'min_people' => 5,
            'discount_amount' => 5,
            'discount_type' => 'fixed',
        ]);
        $selection = $this->selection($activity, 15, now()->addWeek()->toDateString());
        $paymentIntentId = 'pi_test_group_discount';
        $expected = $this->allowIntent($user, $selection, $paymentIntentId);

        $this->postJson('/api/stripe/create-order', $this->payload($selection, $paymentIntentId))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame($expected, (float) OrderPayment::firstOrFail()->amount);
        $this->assertNotSame(0.01, (float) OrderPayment::firstOrFail()->amount);
    }

    public function test_create_order_uses_authoritative_early_and_last_minute_discounts(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $activity = $this->activity();
        ActivityEarlyBirdDiscount::factory()->create([
            'activity_id' => $activity->id,
            'enabled' => true,
            'days_before_start' => 30,
            'discount_amount' => 10,
            'discount_type' => 'percentage',
        ]);
        ActivityLastMinuteDiscount::factory()->create([
            'activity_id' => $activity->id,
            'enabled' => true,
            'days_before_start' => 7,
            'discount_amount' => 5,
            'discount_type' => 'percentage',
        ]);
        $selection = $this->selection($activity, 2, now()->addDays(60)->toDateString());
        $paymentIntentId = 'pi_test_time_discount';
        $expected = $this->allowIntent($user, $selection, $paymentIntentId);

        $this->postJson('/api/stripe/create-order', $this->payload($selection, $paymentIntentId))->assertOk();

        $this->assertSame($expected, (float) OrderPayment::firstOrFail()->amount);
    }

    public function test_create_order_uses_server_fixed_early_bird_rule_instead_of_per_pax_tampering(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $activity = $this->activity();
        ActivityEarlyBirdDiscount::factory()->create([
            'activity_id' => $activity->id,
            'enabled' => true,
            'days_before_start' => 30,
            'discount_amount' => 10,
            'discount_type' => 'fixed',
        ]);
        $selection = $this->selection($activity, 4, now()->addDays(60)->toDateString());
        $paymentIntentId = 'pi_test_fixed_early_bird';
        $this->allowIntent($user, $selection, $paymentIntentId);

        $this->postJson('/api/stripe/create-order', $this->payload($selection, $paymentIntentId))->assertOk();

        $this->assertSame(390.0, (float) OrderPayment::firstOrFail()->amount);
    }

    public function test_create_order_stores_stacked_group_and_last_minute_quote(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $activity = $this->activity();
        ActivityGroupDiscount::factory()->create([
            'activity_id' => $activity->id,
            'min_people' => 5,
            'discount_amount' => 20,
            'discount_type' => 'percentage',
        ]);
        ActivityLastMinuteDiscount::factory()->create([
            'activity_id' => $activity->id,
            'enabled' => true,
            'days_before_start' => 7,
            'discount_amount' => 10,
            'discount_type' => 'percentage',
        ]);
        $selection = $this->selection($activity, 6, now()->addDays(3)->toDateString());
        $paymentIntentId = 'pi_test_stacked_discount';
        $this->allowIntent($user, $selection, $paymentIntentId);

        $this->postJson('/api/stripe/create-order', $this->payload($selection, $paymentIntentId))->assertOk();

        $this->assertSame(440.0, (float) OrderPayment::firstOrFail()->amount);
    }

    public function test_create_order_rejects_zero_headcount_before_payment_lookup(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $activity = $this->activity();
        $selection = $this->selection($activity, 0, now()->addWeek()->toDateString());

        $this->postJson('/api/stripe/create-order', $this->payload($selection, 'pi_test_zero_guests'))
            ->assertUnprocessable()
            ->assertJson(['error' => 'invalid_guests']);
    }

    public function test_create_order_rejects_missing_activity_pricing_before_payment_lookup(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $activity = Activity::factory()->create();
        $selection = $this->selection($activity, 1, now()->addWeek()->toDateString());

        $this->postJson('/api/stripe/create-order', $this->payload($selection, 'pi_test_missing_pricing'))
            ->assertUnprocessable()
            ->assertJson(['error' => 'activity_pricing_missing']);
    }
}
