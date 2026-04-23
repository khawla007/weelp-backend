<?php

namespace Tests\Unit;

use App\Models\Activity;
use App\Models\ActivityGroupDiscount;
use App\Models\ActivityPricing;
use App\Models\ActivityEarlyBirdDiscount;
use App\Models\ActivityLastMinuteDiscount;
use App\Services\ActivityDiscountService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ActivityDiscountServiceTest extends TestCase
{
    private ActivityDiscountService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ActivityDiscountService();
    }

    // Fixed discount tiers
    public function testReturnsZeroDiscountForHeadcountZero(): void
    {
        $activity = new Activity(['id' => 1, 'name' => 'Test Activity', 'slug' => 'test']);
        $activity->setRelation('pricing', new ActivityPricing([
            'id' => 1,
            'regular_price' => 100.00,
            'currency' => 'USD',
        ]));
        $activity->setRelation('groupDiscounts', collect([]));

        $result = $this->service->quote($activity, 0);

        $this->assertSame(0, $result['headcount']);
        $this->assertSame(0.0, $result['subtotal']);
        $this->assertNull($result['selected_tier']);
        $this->assertSame(0.0, $result['discount_total']);
        $this->assertSame(0.0, $result['final_amount']);
    }

    public function testNoDiscountForHeadcountOne(): void
    {
        $activity = new Activity(['id' => 1, 'name' => 'Test Activity', 'slug' => 'test']);
        $activity->setRelation('pricing', new ActivityPricing(['id' => 1, 'regular_price' => 100.00, 'currency' => 'USD']));
        $tier5 = (new ActivityGroupDiscount())->forceFill(['id' => 1, 'activity_id' => 1, 'min_people' => 5, 'discount_amount' => 5.00, 'discount_type' => 'fixed']);
        $activity->setRelation('groupDiscounts', collect([$tier5]));

        $result = $this->service->quote($activity, 1);

        $this->assertSame(1, $result['headcount']);
        $this->assertSame(100.0, $result['subtotal']);
        $this->assertNull($result['selected_tier']);
        $this->assertSame(0.0, $result['discount_total']);
        $this->assertSame(100.0, $result['final_amount']);
    }

    public function testNoDiscountForHeadcountFour(): void
    {
        $activity = new Activity(['id' => 1, 'name' => 'Test Activity', 'slug' => 'test']);
        $activity->setRelation('pricing', new ActivityPricing(['id' => 1, 'regular_price' => 100.00, 'currency' => 'USD']));
        $tier5 = (new ActivityGroupDiscount())->forceFill(['id' => 1, 'activity_id' => 1, 'min_people' => 5, 'discount_amount' => 5.00, 'discount_type' => 'fixed']);
        $activity->setRelation('groupDiscounts', collect([$tier5]));

        $result = $this->service->quote($activity, 4);

        $this->assertSame(400.0, $result['subtotal']);
        $this->assertNull($result['selected_tier']);
        $this->assertSame(400.0, $result['final_amount']);
    }

    public function testSelectsFiveTierForHeadcountFive(): void
    {
        $activity = new Activity(['id' => 1, 'name' => 'Test Activity', 'slug' => 'test']);
        $activity->setRelation('pricing', new ActivityPricing(['id' => 1, 'regular_price' => 100.00, 'currency' => 'USD']));
        $tier5 = (new ActivityGroupDiscount())->forceFill(['id' => 1, 'activity_id' => 1, 'min_people' => 5, 'discount_amount' => 5.00, 'discount_type' => 'fixed']);
        $tier13 = (new ActivityGroupDiscount())->forceFill(['id' => 2, 'activity_id' => 1, 'min_people' => 13, 'discount_amount' => 10.00, 'discount_type' => 'fixed']);
        $activity->setRelation('groupDiscounts', collect([$tier5, $tier13]));

        $result = $this->service->quote($activity, 5);

        $this->assertSame(500.0, $result['subtotal']);
        $this->assertNotNull($result['selected_tier']);
        $this->assertSame(5, $result['selected_tier']->min_people);
        $this->assertSame(5.0, $result['discount_total']);
        $this->assertSame(495.0, $result['final_amount']);
    }

    public function testTiebreakerFiveTierWinsForHeadcountThirteen(): void
    {
        $activity = new Activity(['id' => 1, 'name' => 'Test Activity', 'slug' => 'test']);
        $activity->setRelation('pricing', new ActivityPricing(['id' => 1, 'regular_price' => 100.00, 'currency' => 'USD']));
        $tier5 = (new ActivityGroupDiscount())->forceFill(['id' => 1, 'activity_id' => 1, 'min_people' => 5, 'discount_amount' => 5.00, 'discount_type' => 'fixed']);
        $tier13 = (new ActivityGroupDiscount())->forceFill(['id' => 2, 'activity_id' => 1, 'min_people' => 13, 'discount_amount' => 10.00, 'discount_type' => 'fixed']);
        $activity->setRelation('groupDiscounts', collect([$tier5, $tier13]));

        $result = $this->service->quote($activity, 13);

        $this->assertSame(1300.0, $result['subtotal']);
        $this->assertSame(5, $result['selected_tier']->min_people);
        $this->assertSame(10.0, $result['discount_total']);
        $this->assertSame(1290.0, $result['final_amount']);
    }

    public function testSelectsFiveTierForHeadcountFifteenBugCase(): void
    {
        $activity = new Activity(['id' => 1, 'name' => 'Test Activity', 'slug' => 'test']);
        $activity->setRelation('pricing', new ActivityPricing(['id' => 1, 'regular_price' => 100.00, 'currency' => 'USD']));
        $tier5 = (new ActivityGroupDiscount())->forceFill(['id' => 1, 'activity_id' => 1, 'min_people' => 5, 'discount_amount' => 5.00, 'discount_type' => 'fixed']);
        $tier13 = (new ActivityGroupDiscount())->forceFill(['id' => 2, 'activity_id' => 1, 'min_people' => 13, 'discount_amount' => 10.00, 'discount_type' => 'fixed']);
        $activity->setRelation('groupDiscounts', collect([$tier5, $tier13]));

        $result = $this->service->quote($activity, 15);

        $this->assertSame(1500.0, $result['subtotal']);
        $this->assertSame(5, $result['selected_tier']->min_people);
        $this->assertSame(15.0, $result['discount_total']);
        $this->assertSame(1485.0, $result['final_amount']);
    }

    public function testSelectsFiveTierForHeadcountTwentySix(): void
    {
        $activity = new Activity(['id' => 1, 'name' => 'Test Activity', 'slug' => 'test']);
        $activity->setRelation('pricing', new ActivityPricing(['id' => 1, 'regular_price' => 100.00, 'currency' => 'USD']));
        $tier5 = (new ActivityGroupDiscount())->forceFill(['id' => 1, 'activity_id' => 1, 'min_people' => 5, 'discount_amount' => 5.00, 'discount_type' => 'fixed']);
        $tier13 = (new ActivityGroupDiscount())->forceFill(['id' => 2, 'activity_id' => 1, 'min_people' => 13, 'discount_amount' => 10.00, 'discount_type' => 'fixed']);
        $activity->setRelation('groupDiscounts', collect([$tier5, $tier13]));

        $result = $this->service->quote($activity, 26);

        $this->assertSame(25.0, $result['discount_total']);
        $this->assertSame(5, $result['selected_tier']->min_people);
        $this->assertSame(2575.0, $result['final_amount']);
    }

    public function testSelectsFiveTierForHeadcountTwentyEight(): void
    {
        $activity = new Activity(['id' => 1, 'name' => 'Test Activity', 'slug' => 'test']);
        $activity->setRelation('pricing', new ActivityPricing(['id' => 1, 'regular_price' => 100.00, 'currency' => 'USD']));
        $tier5 = (new ActivityGroupDiscount())->forceFill(['id' => 1, 'activity_id' => 1, 'min_people' => 5, 'discount_amount' => 5.00, 'discount_type' => 'fixed']);
        $tier13 = (new ActivityGroupDiscount())->forceFill(['id' => 2, 'activity_id' => 1, 'min_people' => 13, 'discount_amount' => 10.00, 'discount_type' => 'fixed']);
        $activity->setRelation('groupDiscounts', collect([$tier5, $tier13]));

        $result = $this->service->quote($activity, 28);

        $this->assertSame(25.0, $result['discount_total']);
        $this->assertSame(5, $result['selected_tier']->min_people);
        $this->assertSame(2775.0, $result['final_amount']);
    }

    public function testSelectsFiveTierForHeadcountThirtyNine(): void
    {
        $activity = new Activity(['id' => 1, 'name' => 'Test Activity', 'slug' => 'test']);
        $activity->setRelation('pricing', new ActivityPricing(['id' => 1, 'regular_price' => 100.00, 'currency' => 'USD']));
        $tier5 = (new ActivityGroupDiscount())->forceFill(['id' => 1, 'activity_id' => 1, 'min_people' => 5, 'discount_amount' => 5.00, 'discount_type' => 'fixed']);
        $tier13 = (new ActivityGroupDiscount())->forceFill(['id' => 2, 'activity_id' => 1, 'min_people' => 13, 'discount_amount' => 10.00, 'discount_type' => 'fixed']);
        $activity->setRelation('groupDiscounts', collect([$tier5, $tier13]));

        $result = $this->service->quote($activity, 39);

        $this->assertSame(35.0, $result['discount_total']);
        $this->assertSame(3865.0, $result['final_amount']);
    }

    public function testSelectsFiveTierForHeadcountSixtyFive(): void
    {
        $activity = new Activity(['id' => 1, 'name' => 'Test Activity', 'slug' => 'test']);
        $activity->setRelation('pricing', new ActivityPricing(['id' => 1, 'regular_price' => 100.00, 'currency' => 'USD']));
        $tier5 = (new ActivityGroupDiscount())->forceFill(['id' => 1, 'activity_id' => 1, 'min_people' => 5, 'discount_amount' => 5.00, 'discount_type' => 'fixed']);
        $tier13 = (new ActivityGroupDiscount())->forceFill(['id' => 2, 'activity_id' => 1, 'min_people' => 13, 'discount_amount' => 10.00, 'discount_type' => 'fixed']);
        $activity->setRelation('groupDiscounts', collect([$tier5, $tier13]));

        $result = $this->service->quote($activity, 65);

        $this->assertSame(65.0, $result['discount_total']);
        $this->assertSame(6435.0, $result['final_amount']);
    }

    // Percentage discount tiers
    public function testPercentageFiveTierForHeadcountFive(): void
    {
        $activity = new Activity(['id' => 2, 'name' => 'Test Activity 2', 'slug' => 'test2']);
        $activity->setRelation('pricing', new ActivityPricing(['id' => 2, 'regular_price' => 100.00, 'currency' => 'USD']));
        $tier5 = (new ActivityGroupDiscount())->forceFill(['id' => 3, 'activity_id' => 2, 'min_people' => 5, 'discount_amount' => 3.00, 'discount_type' => 'percentage']);
        $tier13 = (new ActivityGroupDiscount())->forceFill(['id' => 4, 'activity_id' => 2, 'min_people' => 13, 'discount_amount' => 10.00, 'discount_type' => 'percentage']);
        $activity->setRelation('groupDiscounts', collect([$tier5, $tier13]));

        $result = $this->service->quote($activity, 5);

        $this->assertSame(500.0, $result['subtotal']);
        $this->assertSame(5, $result['selected_tier']->min_people);
        $this->assertSame(15.0, $result['discount_total']);
        $this->assertSame(485.0, $result['final_amount']);
    }

    public function testPercentageThirteenTierForHeadcountThirteen(): void
    {
        $activity = new Activity(['id' => 2, 'name' => 'Test Activity 2', 'slug' => 'test2']);
        $activity->setRelation('pricing', new ActivityPricing(['id' => 2, 'regular_price' => 100.00, 'currency' => 'USD']));
        $tier5 = (new ActivityGroupDiscount())->forceFill(['id' => 3, 'activity_id' => 2, 'min_people' => 5, 'discount_amount' => 3.00, 'discount_type' => 'percentage']);
        $tier13 = (new ActivityGroupDiscount())->forceFill(['id' => 4, 'activity_id' => 2, 'min_people' => 13, 'discount_amount' => 10.00, 'discount_type' => 'percentage']);
        $activity->setRelation('groupDiscounts', collect([$tier5, $tier13]));

        $result = $this->service->quote($activity, 13);

        $this->assertSame(1300.0, $result['subtotal']);
        $this->assertSame(13, $result['selected_tier']->min_people);
        $this->assertSame(130.0, $result['discount_total']);
        $this->assertSame(1170.0, $result['final_amount']);
    }

    public function testPercentageThirteenTierForHeadcountFifteen(): void
    {
        $activity = new Activity(['id' => 2, 'name' => 'Test Activity 2', 'slug' => 'test2']);
        $activity->setRelation('pricing', new ActivityPricing(['id' => 2, 'regular_price' => 100.00, 'currency' => 'USD']));
        $tier5 = (new ActivityGroupDiscount())->forceFill(['id' => 3, 'activity_id' => 2, 'min_people' => 5, 'discount_amount' => 3.00, 'discount_type' => 'percentage']);
        $tier13 = (new ActivityGroupDiscount())->forceFill(['id' => 4, 'activity_id' => 2, 'min_people' => 13, 'discount_amount' => 10.00, 'discount_type' => 'percentage']);
        $activity->setRelation('groupDiscounts', collect([$tier5, $tier13]));

        $result = $this->service->quote($activity, 15);

        // 5-tier: complete=3, disc=3% × 100 × 15 = 45
        // 13-tier: complete=1, disc=10% × 100 × 13 = 130 (2 pax pay regular)
        // 13-tier wins.
        $this->assertSame(1500.0, $result['subtotal']);
        $this->assertSame(13, $result['selected_tier']->min_people);
        $this->assertSame(130.0, $result['discount_total']);
        $this->assertSame(1370.0, $result['final_amount']);
    }

    public function testPercentageSixPaxAppliesOnlyToCompleteGroup(): void
    {
        $activity = new Activity(['id' => 20, 'name' => 'Six pax case', 'slug' => 'test20']);
        $activity->setRelation('pricing', new ActivityPricing(['id' => 20, 'regular_price' => 211.00, 'currency' => 'USD']));
        $tier5 = (new ActivityGroupDiscount())->forceFill(['id' => 30, 'activity_id' => 20, 'min_people' => 5, 'discount_amount' => 35.00, 'discount_type' => 'percentage']);
        $activity->setRelation('groupDiscounts', collect([$tier5]));

        $result = $this->service->quote($activity, 6);

        // complete=1, discounted_pax=5, discount=0.35 × 211 × 5 = 369.25
        // final = 6×211 - 369.25 = 1266 - 369.25 = 896.75
        $this->assertSame(1266.0, $result['subtotal']);
        $this->assertSame(1, $result['complete_groups']);
        $this->assertSame(369.25, $result['discount_total']);
        $this->assertSame(896.75, $result['final_amount']);
    }

    public function testPercentageThirteenTierForHeadcountTwentySix(): void
    {
        $activity = new Activity(['id' => 2, 'name' => 'Test Activity 2', 'slug' => 'test2']);
        $activity->setRelation('pricing', new ActivityPricing(['id' => 2, 'regular_price' => 100.00, 'currency' => 'USD']));
        $tier5 = (new ActivityGroupDiscount())->forceFill(['id' => 3, 'activity_id' => 2, 'min_people' => 5, 'discount_amount' => 3.00, 'discount_type' => 'percentage']);
        $tier13 = (new ActivityGroupDiscount())->forceFill(['id' => 4, 'activity_id' => 2, 'min_people' => 13, 'discount_amount' => 10.00, 'discount_type' => 'percentage']);
        $activity->setRelation('groupDiscounts', collect([$tier5, $tier13]));

        $result = $this->service->quote($activity, 26);

        $this->assertSame(13, $result['selected_tier']->min_people);
        $this->assertSame(260.0, $result['discount_total']);
    }

    // Tie-break cases
    public function testPercentageWinsOverFixedInTie(): void
    {
        $activity = new Activity(['id' => 3, 'name' => 'Test Activity 3', 'slug' => 'test3']);
        $activity->setRelation('pricing', new ActivityPricing(['id' => 3, 'regular_price' => 100.00, 'currency' => 'USD']));
        $tierFixed = (new ActivityGroupDiscount())->forceFill(['id' => 5, 'activity_id' => 3, 'min_people' => 10, 'discount_amount' => 100.00, 'discount_type' => 'fixed']);
        $tierPerc = (new ActivityGroupDiscount())->forceFill(['id' => 6, 'activity_id' => 3, 'min_people' => 10, 'discount_amount' => 10.00, 'discount_type' => 'percentage']);
        $activity->setRelation('groupDiscounts', collect([$tierFixed, $tierPerc]));

        $result = $this->service->quote($activity, 10);

        $this->assertSame('percentage', $result['selected_tier']->discount_type);
        $this->assertSame(100.0, $result['discount_total']);
    }

    public function testHigherPercentageWinsInPercentageTie(): void
    {
        $activity = new Activity(['id' => 4, 'name' => 'Test Activity 4', 'slug' => 'test4']);
        $activity->setRelation('pricing', new ActivityPricing(['id' => 4, 'regular_price' => 100.00, 'currency' => 'USD']));
        $tier1 = (new ActivityGroupDiscount())->forceFill(['id' => 7, 'activity_id' => 4, 'min_people' => 10, 'discount_amount' => 10.00, 'discount_type' => 'percentage']);
        $tier2 = (new ActivityGroupDiscount())->forceFill(['id' => 8, 'activity_id' => 4, 'min_people' => 10, 'discount_amount' => 5.00, 'discount_type' => 'percentage']);
        $activity->setRelation('groupDiscounts', collect([$tier1, $tier2]));

        $result = $this->service->quote($activity, 20);

        $this->assertSame(7, $result['selected_tier']->id);
        $this->assertSame(200.0, $result['discount_total']);
    }

    public function testSmallerMinPeopleWinsInFixedTie(): void
    {
        $activity = new Activity(['id' => 5, 'name' => 'Test Activity 5', 'slug' => 'test5']);
        $activity->setRelation('pricing', new ActivityPricing(['id' => 5, 'regular_price' => 100.00, 'currency' => 'USD']));
        $tier1 = (new ActivityGroupDiscount())->forceFill(['id' => 9, 'activity_id' => 5, 'min_people' => 10, 'discount_amount' => 10.00, 'discount_type' => 'fixed']);
        $tier2 = (new ActivityGroupDiscount())->forceFill(['id' => 10, 'activity_id' => 5, 'min_people' => 5, 'discount_amount' => 10.00, 'discount_type' => 'fixed']);
        $activity->setRelation('groupDiscounts', collect([$tier1, $tier2]));

        $result = $this->service->quote($activity, 20);

        $this->assertSame(10, $result['selected_tier']->id);
        $this->assertSame(40.0, $result['discount_total']);
    }

    // Edge cases
    public function testEmptyTierCollection(): void
    {
        $activity = new Activity(['id' => 6, 'name' => 'Test Activity 6', 'slug' => 'test6']);
        $activity->setRelation('pricing', new ActivityPricing(['id' => 6, 'regular_price' => 100.00, 'currency' => 'USD']));
        $activity->setRelation('groupDiscounts', collect([]));

        $result = $this->service->quote($activity, 10);

        $this->assertNull($result['selected_tier']);
        $this->assertSame(0.0, $result['discount_total']);
        $this->assertSame(1000.0, $result['final_amount']);
    }

    public function testOrderIndependence(): void
    {
        $tier5 = new ActivityGroupDiscount(['id' => 11, 'activity_id' => 7, 'min_people' => 5, 'discount_amount' => 5.00, 'discount_type' => 'fixed']);
        $tier13 = (new ActivityGroupDiscount())->forceFill(['id' => 12, 'activity_id' => 7, 'min_people' => 13, 'discount_amount' => 10.00, 'discount_type' => 'fixed']);

        $activity1 = new Activity(['id' => 7, 'name' => 'Test', 'slug' => 'test7']);
        $activity1->setRelation('pricing', new ActivityPricing(['id' => 7, 'regular_price' => 100.00, 'currency' => 'USD']));
        $activity1->setRelation('groupDiscounts', collect([$tier5, $tier13]));

        $activity2 = new Activity(['id' => 8, 'name' => 'Test', 'slug' => 'test8']);
        $activity2->setRelation('pricing', new ActivityPricing(['id' => 8, 'regular_price' => 100.00, 'currency' => 'USD']));
        $activity2->setRelation('groupDiscounts', collect([$tier13, $tier5]));

        $result1 = $this->service->quote($activity1, 15);
        $result2 = $this->service->quote($activity2, 15);

        $this->assertSame($result1['selected_tier']->id, $result2['selected_tier']->id);
        $this->assertSame($result1['discount_total'], $result2['discount_total']);
        $this->assertSame($result1['final_amount'], $result2['final_amount']);
    }

    public function testClampPercentageAboveHundred(): void
    {
        $activity = new Activity(['id' => 9, 'name' => 'Test Activity 9', 'slug' => 'test9']);
        $activity->setRelation('pricing', new ActivityPricing(['id' => 9, 'regular_price' => 100.00, 'currency' => 'USD']));
        $tierBad = (new ActivityGroupDiscount())->forceFill(['id' => 13, 'activity_id' => 9, 'min_people' => 5, 'discount_amount' => 150.00, 'discount_type' => 'percentage']);
        $activity->setRelation('groupDiscounts', collect([$tierBad]));

        $result = $this->service->quote($activity, 5);

        $this->assertSame(500.0, $result['discount_total']);
        $this->assertSame(0.0, $result['final_amount']);
    }

    public function testNeverNegativeFinalAmount(): void
    {
        $activity = new Activity(['id' => 10, 'name' => 'Test Activity 10', 'slug' => 'test10']);
        $activity->setRelation('pricing', new ActivityPricing(['id' => 10, 'regular_price' => 100.00, 'currency' => 'USD']));
        $tierMassive = (new ActivityGroupDiscount())->forceFill(['id' => 14, 'activity_id' => 10, 'min_people' => 1, 'discount_amount' => 10000.00, 'discount_type' => 'fixed']);
        $activity->setRelation('groupDiscounts', collect([$tierMassive]));

        $result = $this->service->quote($activity, 10);

        $this->assertSame(0.0, $result['final_amount']);
        $this->assertGreaterThanOrEqual(0.0, $result['final_amount']);
    }

    public function testThrowsWhenPricingNull(): void
    {
        $activity = new Activity(['id' => 11, 'name' => 'Test Activity 11', 'slug' => 'test11']);
        $activity->setRelation('pricing', null);
        $activity->setRelation('groupDiscounts', collect([]));

        $this->expectException(RuntimeException::class);
        $this->service->quote($activity, 5);
    }

    public function testThrowsWhenRegularPriceNull(): void
    {
        $activity = new Activity(['id' => 12, 'name' => 'Test Activity 12', 'slug' => 'test12']);
        $activity->setRelation('pricing', new ActivityPricing(['id' => 12, 'regular_price' => null, 'currency' => 'USD']));
        $activity->setRelation('groupDiscounts', collect([]));

        $this->expectException(RuntimeException::class);
        $this->service->quote($activity, 5);
    }

    public function testCurrencyFromPricing(): void
    {
        $activity = new Activity(['id' => 13, 'name' => 'Test Activity 13', 'slug' => 'test13']);
        $activity->setRelation('pricing', new ActivityPricing(['id' => 13, 'regular_price' => 100.00, 'currency' => 'EUR']));
        $activity->setRelation('groupDiscounts', collect([]));

        $result = $this->service->quote($activity, 5);

        $this->assertSame('EUR', $result['currency']);
    }

    public function testPerPaxAsFloat(): void
    {
        $activity = new Activity(['id' => 14, 'name' => 'Test Activity 14', 'slug' => 'test14']);
        $activity->setRelation('pricing', new ActivityPricing(['id' => 14, 'regular_price' => 100.00, 'currency' => 'USD']));
        $activity->setRelation('groupDiscounts', collect([]));

        $result = $this->service->quote($activity, 5);

        $this->assertSame(100.0, $result['per_pax']);
        $this->assertIsFloat($result['per_pax']);
    }

    // Early-bird and Last-minute discount tests

    private function makeActivity(float $price = 100.0): Activity
    {
        $activity = (new Activity())->forceFill(['id' => 1, 'name' => 'Test', 'slug' => 'test']);
        $activity->setRelation('pricing', (new ActivityPricing())->forceFill([
            'id' => 1, 'regular_price' => $price, 'currency' => 'USD',
        ]));
        $activity->setRelation('groupDiscounts', collect([]));
        $activity->setRelation('earlyBirdDiscount', null);
        $activity->setRelation('lastMinuteDiscount', null);
        return $activity;
    }

    private function attachEarlyBird(Activity $a, array $attrs): void
    {
        $a->setRelation('earlyBirdDiscount', (new ActivityEarlyBirdDiscount())->forceFill(array_merge([
            'id' => 100, 'activity_id' => 1, 'enabled' => true,
        ], $attrs)));
    }

    private function attachLastMinute(Activity $a, array $attrs): void
    {
        $a->setRelation('lastMinuteDiscount', (new ActivityLastMinuteDiscount())->forceFill(array_merge([
            'id' => 200, 'activity_id' => 1, 'enabled' => true,
        ], $attrs)));
    }

    public function testEarlyBirdDisabledReturnsZero(): void
    {
        $a = $this->makeActivity();
        $this->attachEarlyBird($a, ['enabled' => false, 'days_before_start' => 30, 'discount_amount' => 10, 'discount_type' => 'percentage']);
        $travel = CarbonImmutable::today()->addDays(90);

        $result = $this->service->quote($a, 2, $travel);

        $this->assertSame(0.0, $result['early_bird_discount']);
        $this->assertNull($result['selected_early_bird']);
        $this->assertSame(200.0, $result['final_amount']);
    }

    public function testEarlyBirdPercentTriggersAtThreshold(): void
    {
        $a = $this->makeActivity();
        $this->attachEarlyBird($a, ['days_before_start' => 30, 'discount_amount' => 10, 'discount_type' => 'percentage']);
        $travel = CarbonImmutable::today()->addDays(30);

        $result = $this->service->quote($a, 2, $travel);

        $this->assertSame(20.0, $result['early_bird_discount']);
        $this->assertSame(180.0, $result['final_amount']);
        $this->assertSame(30, $result['days_ahead']);
    }

    public function testEarlyBirdDoesNotTriggerBelowThreshold(): void
    {
        $a = $this->makeActivity();
        $this->attachEarlyBird($a, ['days_before_start' => 30, 'discount_amount' => 10, 'discount_type' => 'percentage']);
        $travel = CarbonImmutable::today()->addDays(29);

        $result = $this->service->quote($a, 2, $travel);

        $this->assertSame(0.0, $result['early_bird_discount']);
        $this->assertSame(200.0, $result['final_amount']);
    }

    public function testEarlyBirdFixedStaysFlatAcrossHeadcount(): void
    {
        $a = $this->makeActivity();
        $this->attachEarlyBird($a, ['days_before_start' => 30, 'discount_amount' => 10, 'discount_type' => 'fixed']);
        $travel = CarbonImmutable::today()->addDays(45);

        $this->assertSame(10.0, $this->service->quote($a, 1, $travel)['early_bird_discount']);
        $this->assertSame(10.0, $this->service->quote($a, 5, $travel)['early_bird_discount']);
        $this->assertSame(10.0, $this->service->quote($a, 10, $travel)['early_bird_discount']);
    }

    public function testEarlyBirdPercentClampsAt100(): void
    {
        $a = $this->makeActivity();
        $this->attachEarlyBird($a, ['days_before_start' => 1, 'discount_amount' => 150, 'discount_type' => 'percentage']);
        $travel = CarbonImmutable::today()->addDays(5);

        $result = $this->service->quote($a, 2, $travel);

        $this->assertSame(200.0, $result['early_bird_discount']);
        $this->assertSame(0.0, $result['final_amount']);
    }

    public function testLastMinuteDisabledReturnsZero(): void
    {
        $a = $this->makeActivity();
        $this->attachLastMinute($a, ['enabled' => false, 'days_before_start' => 7, 'discount_amount' => 10, 'discount_type' => 'percentage']);
        $travel = CarbonImmutable::today()->addDays(3);

        $this->assertSame(0.0, $this->service->quote($a, 2, $travel)['last_minute_discount']);
    }

    public function testLastMinutePercentTriggersWithinWindow(): void
    {
        $a = $this->makeActivity();
        $this->attachLastMinute($a, ['days_before_start' => 7, 'discount_amount' => 10, 'discount_type' => 'percentage']);
        $travel = CarbonImmutable::today()->addDays(7);

        $result = $this->service->quote($a, 2, $travel);

        $this->assertSame(20.0, $result['last_minute_discount']);
        $this->assertSame(180.0, $result['final_amount']);
    }

    public function testLastMinuteDoesNotTriggerBeyondWindow(): void
    {
        $a = $this->makeActivity();
        $this->attachLastMinute($a, ['days_before_start' => 7, 'discount_amount' => 10, 'discount_type' => 'percentage']);
        $travel = CarbonImmutable::today()->addDays(8);

        $this->assertSame(0.0, $this->service->quote($a, 2, $travel)['last_minute_discount']);
    }

    public function testLastMinuteIgnoresPastDate(): void
    {
        $a = $this->makeActivity();
        $this->attachLastMinute($a, ['days_before_start' => 7, 'discount_amount' => 10, 'discount_type' => 'percentage']);
        $travel = CarbonImmutable::today()->subDay();

        $this->assertSame(0.0, $this->service->quote($a, 2, $travel)['last_minute_discount']);
    }

    public function testEbAndLmStackAdditivelyWhenBothConfigured(): void
    {
        $a = $this->makeActivity();
        $this->attachEarlyBird($a, ['days_before_start' => 1, 'discount_amount' => 10, 'discount_type' => 'percentage']);
        $this->attachLastMinute($a, ['days_before_start' => 14, 'discount_amount' => 10, 'discount_type' => 'percentage']);
        $travel = CarbonImmutable::today()->addDays(5);

        $result = $this->service->quote($a, 2, $travel);

        $this->assertSame(20.0, $result['early_bird_discount']);
        $this->assertSame(20.0, $result['last_minute_discount']);
        $this->assertSame(40.0, $result['combined_discount']);
        $this->assertSame(160.0, $result['final_amount']);
    }

    public function testTimeDiscountsStackOnRegularSubtotalNotPostGroup(): void
    {
        $a = $this->makeActivity();
        $tier = new ActivityGroupDiscount([
            'id' => 10, 'activity_id' => 1,
            'min_people' => 5, 'discount_amount' => 20, 'discount_type' => 'percentage',
        ]);
        $a->setRelation('groupDiscounts', collect([$tier]));
        $this->attachEarlyBird($a, ['days_before_start' => 30, 'discount_amount' => 10, 'discount_type' => 'percentage']);
        $travel = CarbonImmutable::today()->addDays(90);

        $result = $this->service->quote($a, 6, $travel);

        $this->assertSame(600.0, $result['subtotal']);
        $this->assertSame(100.0, $result['discount_total']);
        $this->assertSame(60.0, $result['early_bird_discount']);
        $this->assertSame(160.0, $result['combined_discount']);
        $this->assertSame(440.0, $result['final_amount']);
    }

    public function testPercentClampWithGroupStack(): void
    {
        $a = $this->makeActivity();
        $tier = new ActivityGroupDiscount([
            'id' => 10, 'activity_id' => 1,
            'min_people' => 5, 'discount_amount' => 20, 'discount_type' => 'percentage',
        ]);
        $a->setRelation('groupDiscounts', collect([$tier]));
        $this->attachEarlyBird($a, ['days_before_start' => 30, 'discount_amount' => 150, 'discount_type' => 'percentage']);
        $travel = CarbonImmutable::today()->addDays(60);

        $result = $this->service->quote($a, 6, $travel);

        $this->assertSame(600.0, $result['subtotal']);
        $this->assertSame(100.0, $result['discount_total']);
        $this->assertSame(600.0, $result['early_bird_discount']);
        $this->assertSame(700.0, $result['combined_discount']);
        $this->assertSame(0.0, $result['final_amount']);
    }

    public function testFixedEarlyBirdMixedWithPercentLastMinute(): void
    {
        $a = $this->makeActivity();
        $this->attachEarlyBird($a, ['days_before_start' => 1, 'discount_amount' => 15, 'discount_type' => 'fixed']);
        $this->attachLastMinute($a, ['days_before_start' => 14, 'discount_amount' => 10, 'discount_type' => 'percentage']);
        $travel = CarbonImmutable::today()->addDays(5);

        $result = $this->service->quote($a, 4, $travel);

        $this->assertSame(15.0, $result['early_bird_discount']);
        $this->assertSame(40.0, $result['last_minute_discount']);
        $this->assertSame(55.0, $result['combined_discount']);
        $this->assertSame(345.0, $result['final_amount']);
    }

    public function testOverDiscountClampsToZero(): void
    {
        $a = $this->makeActivity();
        $this->attachEarlyBird($a, ['days_before_start' => 1, 'discount_amount' => 60, 'discount_type' => 'fixed']);
        $this->attachLastMinute($a, ['days_before_start' => 14, 'discount_amount' => 50, 'discount_type' => 'fixed']);
        $travel = CarbonImmutable::today()->addDays(3);

        $this->assertSame(0.0, $this->service->quote($a, 1, $travel)['final_amount']);
    }

    public function testNullTravelDateReturnsZeroTimeDiscounts(): void
    {
        $a = $this->makeActivity();
        $this->attachEarlyBird($a, ['days_before_start' => 30, 'discount_amount' => 10, 'discount_type' => 'percentage']);
        $this->attachLastMinute($a, ['days_before_start' => 7, 'discount_amount' => 10, 'discount_type' => 'percentage']);

        $result = $this->service->quote($a, 2, null);

        $this->assertSame(0.0, $result['early_bird_discount']);
        $this->assertSame(0.0, $result['last_minute_discount']);
        $this->assertNull($result['days_ahead']);
        $this->assertSame(200.0, $result['final_amount']);
    }

    public function testZeroHeadcountWithEbLmAttachedStaysZero(): void
    {
        $a = $this->makeActivity();
        $this->attachEarlyBird($a, ['days_before_start' => 30, 'discount_amount' => 10, 'discount_type' => 'percentage']);
        $this->attachLastMinute($a, ['days_before_start' => 7, 'discount_amount' => 10, 'discount_type' => 'percentage']);
        $travel = CarbonImmutable::today()->addDays(60);

        $result = $this->service->quote($a, 0, $travel);

        $this->assertSame(0, $result['headcount']);
        $this->assertSame(0.0, $result['subtotal']);
        $this->assertSame(0.0, $result['early_bird_discount']);
        $this->assertSame(0.0, $result['last_minute_discount']);
        $this->assertSame(0.0, $result['combined_discount']);
        $this->assertSame(0.0, $result['final_amount']);
    }
}
