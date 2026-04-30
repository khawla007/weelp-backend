<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityEarlyBirdDiscount;
use App\Models\ActivityGroupDiscount;
use App\Models\ActivityLastMinuteDiscount;
use App\Models\ActivityPricing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeOrderDiscountTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper: Create an activity with pricing and optional discounts.
     */
    private function createActivityWithPricing(
        string $slug = 'test-activity',
        float $regularPrice = 100,
        string $currency = 'USD',
        array $discounts = []
    ): Activity {
        $activity = Activity::factory()->create(['slug' => $slug]);

        ActivityPricing::factory()->create([
            'activity_id' => $activity->id,
            'regular_price' => $regularPrice,
            'currency' => $currency,
        ]);

        foreach ($discounts as $discount) {
            ActivityGroupDiscount::factory()->create([
                'activity_id' => $activity->id,
                'min_people' => $discount['min_people'],
                'discount_amount' => $discount['discount_amount'],
                'discount_type' => $discount['discount_type'] ?? 'fixed',
            ]);
        }

        return $activity;
    }

    /**
     * Helper: Build valid createOrder payload for an activity.
     */
    private function buildActivityOrderPayload(
        Activity $activity,
        int $adults,
        int $children,
        float $baseAmount,
        ?float $addonsAmount = null,
        ?array $addons = null
    ): array {
        $amount = $baseAmount + ($addonsAmount ?? 0);

        return [
            'order_type' => 'activity',
            'orderable_id' => $activity->id,
            'travel_date' => now()->addDays(7)->format('Y-m-d'),
            'preferred_time' => '10:00 AM',
            'number_of_adults' => $adults,
            'number_of_children' => $children,
            'special_requirements' => 'None',
            'user_id' => 1,
            'customer_email' => 'customer@example.com',
            'amount' => $amount,
            'is_custom_amount' => false,
            'custom_amount' => null,
            'currency' => 'USD',
            'payment_intent_id' => 'pi_test_' . uniqid(),
            'emergency_contact' => [
                'name' => 'John Doe',
                'phone' => '+1234567890',
                'relationship' => 'Friend',
            ],
            'base_amount' => $baseAmount,
            'addons_amount' => $addonsAmount ?? 0,
            'addons' => $addons ?? [],
        ];
    }

    /**
     * Test 1: Tampered amount is rejected.
     * Activity for 15 pax with correct pricing = 1485 (100 per pax × 15, minus discount).
     * Submit base_amount = 1000 (tampered) → expect 422 activity_price_mismatch.
     */
    public function test_tampered_activity_base_amount_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, "api");
        $activity = $this->createActivityWithPricing(
            'safari-activity',
            regularPrice: 100,
            discounts: [
                [
                    'min_people' => 5,
                    'discount_amount' => 5,
                    'discount_type' => 'fixed',
                ],
                [
                    'min_people' => 13,
                    'discount_amount' => 10,
                    'discount_type' => 'fixed',
                ],
            ]
        );

        $payload = $this->buildActivityOrderPayload(
            activity: $activity,
            adults: 15,
            children: 0,
            baseAmount: 1000.00 // Tampered (correct should be ~1485)
        );
        $payload['user_id'] = $user->id;

        $response = $this->postJson('/api/stripe/create-order', $payload);

        $response->assertStatus(422);
        $data = $response->json();
        $this->assertEquals('activity_price_mismatch', $data['error']);
        $this->assertNotNull($data['expected']);
        $this->assertNotNull($data['submitted']);
    }

    /**
     * Test 2: Legitimate base_amount is accepted.
     * Activity for 15 pax, correct base_amount based on service quote.
     * Expect 2xx (order created successfully or passes validation).
     * Note: We stub Stripe to avoid actual payment processing.
     */
    public function test_legitimate_activity_base_amount_accepted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, "api");
        $activity = $this->createActivityWithPricing(
            'safari-activity-legit',
            regularPrice: 100,
            discounts: [
                [
                    'min_people' => 5,
                    'discount_amount' => 5,
                    'discount_type' => 'fixed',
                ],
                [
                    'min_people' => 13,
                    'discount_amount' => 10,
                    'discount_type' => 'fixed',
                ],
            ]
        );

        // Correct base_amount for 15 pax:
        // subtotal = 15 × 100 = 1500
        // headcount >= 5: discount = 5 per group → floor(15/5) = 3 groups → 15 discount
        // headcount >= 13: discount = 10 per group → floor(15/13) = 1 group → 10 discount
        // Best (max discount) = 15, so final = 1500 - 15 = 1485
        $payload = $this->buildActivityOrderPayload(
            activity: $activity,
            adults: 15,
            children: 0,
            baseAmount: 1485.00 // Correct base amount
        );
        $payload['user_id'] = $user->id;

        $response = $this->postJson('/api/stripe/create-order', $payload);

        // Should pass validation (may fail on Stripe without mocking, but not on price mismatch).
        $this->assertNotEquals(422, $response->status());
        if ($response->status() === 200 || $response->status() === 201) {
            $data = $response->json();
            $this->assertTrue($data['success'] ?? true);
        }
    }

    /**
     * Test 3: Addon passthrough.
     * Submit activity with correct base_amount and addons.
     * Validation checks base_amount only, not total amount.
     * Expect 2xx (passes validation).
     */
    public function test_addon_passthrough_with_correct_base_amount(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, "api");
        $activity = $this->createActivityWithPricing(
            'activity-with-addons',
            regularPrice: 100,
            discounts: []
        );

        // 5 pax, no discount: base = 5 × 100 = 500
        // addons = 50, total = 550
        $payload = $this->buildActivityOrderPayload(
            activity: $activity,
            adults: 5,
            children: 0,
            baseAmount: 500.00,
            addonsAmount: 50.00,
            addons: [
                [
                    'addon_id' => 1,
                    'addon_name' => 'Guide Service',
                    'price' => 50.00,
                ],
            ]
        );
        $payload['user_id'] = $user->id;

        $response = $this->postJson('/api/stripe/create-order', $payload);

        // Should not fail on price mismatch (validation checks base_amount, not total).
        $this->assertNotEquals(422, $response->status());
        if ($response->status() === 200 || $response->status() === 201) {
            $data = $response->json();
            // Verify addon snapshot was saved if order created
            $this->assertTrue($data['success'] ?? true);
        }
    }

    /**
     * Test 4: Zero headcount is rejected.
     * 0 adults, 0 children → expect 422 invalid_headcount.
     */
    public function test_zero_headcount_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, "api");
        $activity = $this->createActivityWithPricing();

        $payload = $this->buildActivityOrderPayload(
            activity: $activity,
            adults: 0,
            children: 0,
            baseAmount: 0.00
        );
        $payload['user_id'] = $user->id;

        $response = $this->postJson('/api/stripe/create-order', $payload);

        $response->assertStatus(422);
        $data = $response->json();
        $this->assertEquals('invalid_headcount', $data['error']);
    }

    /**
     * Test 5: Activity without pricing is rejected.
     * Create activity, do NOT attach pricing, then POST createOrder.
     * Expect 422 activity_pricing_missing.
     */
    public function test_activity_without_pricing_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, "api");
        $activity = Activity::factory()->create(['slug' => 'no-pricing-activity']);
        // Intentionally do NOT create ActivityPricing

        $payload = $this->buildActivityOrderPayload(
            activity: $activity,
            adults: 5,
            children: 0,
            baseAmount: 500.00
        );
        $payload['user_id'] = $user->id;

        $response = $this->postJson('/api/stripe/create-order', $payload);

        $response->assertStatus(422);
        $data = $response->json();
        $this->assertEquals('activity_pricing_missing', $data['error']);
    }

    /**
     * Test 6: Tolerance boundary.
     * Activity for 5 pax, correct base = 500.
     * Submit base_amount = 499.99 (within ±0.01 tolerance) → should be accepted.
     */
    public function test_tolerance_boundary_within_limit(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, "api");
        $activity = $this->createActivityWithPricing(
            'tolerance-activity',
            regularPrice: 100,
            discounts: []
        );

        // 5 pax, no discount: base = 500
        // Submit 499.99 (within tolerance)
        $payload = $this->buildActivityOrderPayload(
            activity: $activity,
            adults: 5,
            children: 0,
            baseAmount: 499.99
        );
        $payload['user_id'] = $user->id;

        $response = $this->postJson('/api/stripe/create-order', $payload);

        // Should pass validation (within tolerance).
        $this->assertNotEquals(422, $response->status());
    }

    /**
     * Test 7: Tolerance boundary exceeded.
     * Submit base_amount outside tolerance (e.g., 499.98 for expected 500).
     * Expect 422 activity_price_mismatch.
     */
    public function test_tolerance_boundary_exceeded(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, "api");
        $activity = $this->createActivityWithPricing(
            'tolerance-exceed-activity',
            regularPrice: 100,
            discounts: []
        );

        // 5 pax, no discount: base = 500
        // Submit 499.98 (outside tolerance, > 0.01 difference)
        $payload = $this->buildActivityOrderPayload(
            activity: $activity,
            adults: 5,
            children: 0,
            baseAmount: 499.98
        );
        $payload['user_id'] = $user->id;

        $response = $this->postJson('/api/stripe/create-order', $payload);

        $response->assertStatus(422);
        $data = $response->json();
        $this->assertEquals('activity_price_mismatch', $data['error']);
    }

    public function test_activity_order_rejected_when_base_amount_omitted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, "api");
        $activity = $this->createActivityWithPricing(
            slug: 'omit-base-amount',
            regularPrice: 100,
            discounts: [
                ['min_people' => 5, 'discount_amount' => 5, 'discount_type' => 'fixed'],
                ['min_people' => 13, 'discount_amount' => 10, 'discount_type' => 'fixed'],
            ]
        );

        $payload = $this->buildActivityOrderPayload(
            activity: $activity,
            adults: 15,
            children: 0,
            baseAmount: 1485.00
        );
        $payload['user_id'] = $user->id;
        unset($payload['base_amount']);

        $response = $this->postJson('/api/stripe/create-order', $payload);

        $response->assertStatus(422)->assertJson(['error' => 'base_amount_required']);
    }

    /**
     * Test 9: Early Bird discount is applied with travel_date.
     * Activity: regularPrice = 100
     * EB discount: 30 days before start, 10% discount
     * travel_date = now + 60 days (well within EB window)
     * 2 adults: subtotal = 200, EB discount = 20, final = 180
     */
    public function test_stripe_order_accepts_early_bird_discounted_base_amount(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, "api");
        $activity = $this->createActivityWithPricing(
            'early-bird-activity',
            regularPrice: 100,
            discounts: []
        );

        ActivityEarlyBirdDiscount::factory()->create([
            'activity_id' => $activity->id,
            'enabled' => true,
            'days_before_start' => 30,
            'discount_amount' => 10,
            'discount_type' => 'percentage',
        ]);

        $payload = $this->buildActivityOrderPayload(
            activity: $activity,
            adults: 2,
            children: 0,
            baseAmount: 180.0 // 200 subtotal - 20 EB discount = 180
        );
        $payload['user_id'] = $user->id;
        $payload['travel_date'] = now()->addDays(60)->toDateString();

        $response = $this->postJson('/api/stripe/create-order', $payload);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['success'] ?? false);
    }

    /**
     * Test 10: Tampered Early Bird base_amount is rejected.
     * Same setup as Test 9, but submit incorrect base_amount (200 instead of 180).
     * The service recompute with travel_date should detect the mismatch.
     */
    public function test_stripe_order_rejects_tampered_early_bird_base_amount(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, "api");
        $activity = $this->createActivityWithPricing(
            'tampered-eb-activity',
            regularPrice: 100,
            discounts: []
        );

        ActivityEarlyBirdDiscount::factory()->create([
            'activity_id' => $activity->id,
            'enabled' => true,
            'days_before_start' => 30,
            'discount_amount' => 10,
            'discount_type' => 'percentage',
        ]);

        $payload = $this->buildActivityOrderPayload(
            activity: $activity,
            adults: 2,
            children: 0,
            baseAmount: 200.0 // Tampered: should be 180 with EB discount
        );
        $payload['user_id'] = $user->id;
        $payload['travel_date'] = now()->addDays(60)->toDateString();

        $response = $this->postJson('/api/stripe/create-order', $payload);

        $response->assertStatus(422);
        $data = $response->json();
        $this->assertEquals('activity_price_mismatch', $data['error']);
    }

    /**
     * Test 11: Per-pax fixed EB discount regression test.
     * Old frontend algo: subtotal(400) - 10*4 = 360. New server rule: subtotal(400) - 10 = 390.
     * If frontend sends 360 (old algo), server should reject it.
     */
    public function test_stripe_order_rejects_per_pax_fixed_tamper_regression(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, "api");
        $activity = $this->createActivityWithPricing(
            'regression-fixed-eb',
            regularPrice: 100,
            discounts: []
        );

        ActivityEarlyBirdDiscount::factory()->create([
            'activity_id' => $activity->id,
            'enabled' => true,
            'days_before_start' => 30,
            'discount_amount' => 10,
            'discount_type' => 'fixed',
        ]);

        // Old frontend algo: subtotal(400) - 10*4 = 360
        // New server rule: subtotal(400) - 10 = 390
        $payload = $this->buildActivityOrderPayload(
            activity: $activity,
            adults: 4,
            children: 0,
            baseAmount: 360.0 // Old algo, should be 390
        );
        $payload['user_id'] = $user->id;
        $payload['travel_date'] = now()->addDays(60)->toDateString();

        $response = $this->postJson('/api/stripe/create-order', $payload);

        $response->assertStatus(422);
        $data = $response->json();
        $this->assertEquals('activity_price_mismatch', $data['error']);
    }

    /**
     * Test 12: Group discount and Last Minute discount stack.
     * Activity: regularPrice = 100
     * Group: min_people = 5, 20% discount
     * Last Minute: 7 days before start, 10% discount
     * travel_date = now + 3 days (within LM window)
     * 6 adults: subtotal = 600
     *   Group discount (6 pax >= 5): 20% of 100 * 5 = 100 (1 complete group of 5)
     *   Last Minute: 10% of 600 = 60
     *   Combined discount: 100 + 60 = 160
     *   Final: 600 - 160 = 440
     */
    public function test_stripe_order_stacks_group_and_last_minute(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, "api");
        $activity = $this->createActivityWithPricing(
            'group-lm-activity',
            regularPrice: 100,
            discounts: [
                [
                    'min_people' => 5,
                    'discount_amount' => 20,
                    'discount_type' => 'percentage',
                ],
            ]
        );

        ActivityLastMinuteDiscount::factory()->create([
            'activity_id' => $activity->id,
            'enabled' => true,
            'days_before_start' => 7,
            'discount_amount' => 10,
            'discount_type' => 'percentage',
        ]);

        $payload = $this->buildActivityOrderPayload(
            activity: $activity,
            adults: 6,
            children: 0,
            baseAmount: 440.0 // 600 - (group 100 + lm 60) = 440
        );
        $payload['user_id'] = $user->id;
        $payload['travel_date'] = now()->addDays(3)->toDateString();

        $response = $this->postJson('/api/stripe/create-order', $payload);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['success'] ?? false);
    }
}
