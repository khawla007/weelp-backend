<?php

namespace Tests\Unit;

use App\Models\Activity;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\CancellationPolicyService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CancellationPolicyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.timezone' => 'America/New_York']);
        Carbon::setTestNow(Carbon::parse('2026-08-12 09:00:00', 'America/New_York'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_exact_policy_boundaries_use_the_ordered_general_bands(): void
    {
        $cases = [
            ['2026-09-11', '09:00:00', 10, '10.00', '90.00'],
            ['2026-09-11', '08:59:59', 25, '25.00', '75.00'],
            ['2026-08-27', '09:00:00', 25, '25.00', '75.00'],
            ['2026-08-19', '09:00:00', 50, '50.00', '50.00'],
            ['2026-08-14', '09:00:00', 75, '75.00', '25.00'],
            ['2026-08-14', '08:59:59', 100, '100.00', '0.00'],
        ];

        foreach ($cases as [$travelDate, $preferredTime, $percentage, $deduction, $refund]) {
            $quote = $this->quoteFor($travelDate, $preferredTime);

            $this->assertSame($percentage, $quote['deduction_percentage']);
            $this->assertSame($deduction, $quote['suggested_deduction']);
            $this->assertSame($refund, $quote['suggested_refund']);
        }
    }

    public function test_quote_contains_stable_policy_and_timing_snapshots(): void
    {
        $quote = $this->quoteFor('2026-09-11', '09:00:00');

        $this->assertSame('general-v1', $quote['policy_version']);
        $this->assertSame(config('cancellation'), $quote['policy_snapshot']);
        $this->assertSame('2026-08-12T09:00:00-04:00', $quote['requested_at']);
        $this->assertSame('2026-09-11T09:00:00-04:00', $quote['travel_starts_at']);
        $this->assertSame(30 * 24 * 60 * 60, $quote['seconds_remaining']);
        $this->assertSame('100.00', $quote['paid_amount']);
        $this->assertSame('USD', $quote['currency']);
        $this->assertSame([
            ['minimum_seconds' => 30 * 24 * 60 * 60, 'deduction_percentage' => 10],
            ['minimum_seconds' => 15 * 24 * 60 * 60, 'deduction_percentage' => 25],
            ['minimum_seconds' => 7 * 24 * 60 * 60, 'deduction_percentage' => 50],
            ['minimum_seconds' => 48 * 60 * 60, 'deduction_percentage' => 75],
            ['minimum_seconds' => 0, 'deduction_percentage' => 100],
        ], $quote['policy_snapshot']['bands']);
    }

    public function test_an_injected_request_instant_is_normalized_to_the_business_timezone(): void
    {
        $order = $this->makeOrder('2026-09-11', '10:00:00');
        OrderPayment::factory()->for($order)->create([
            'payment_status' => 'paid',
            'payment_intent_id' => 'pi_injected',
            'total_amount' => '100.00',
            'currency' => 'USD',
        ]);

        $quote = app(CancellationPolicyService::class)->quote(
            $order->fresh(),
            Carbon::parse('2026-08-12 14:00:00', 'UTC')->toImmutable(),
        );

        $this->assertSame('2026-08-12T10:00:00-04:00', $quote['requested_at']);
        $this->assertSame(30 * 24 * 60 * 60, $quote['seconds_remaining']);
    }

    public function test_custom_amount_precedes_total_then_base_amount(): void
    {
        $custom = $this->quoteFor('2026-09-11', '09:00:00', [
            'is_custom_amount' => true,
            'custom_amount' => '120.00',
            'total_amount' => '100.00',
            'amount' => '80.00',
        ]);
        $total = $this->quoteFor('2026-09-11', '09:00:00', [
            'is_custom_amount' => false,
            'custom_amount' => '120.00',
            'total_amount' => '100.00',
            'amount' => '80.00',
        ]);
        $base = $this->quoteFor('2026-09-11', '09:00:00', [
            'is_custom_amount' => false,
            'custom_amount' => null,
            'total_amount' => null,
            'amount' => '80.00',
        ]);

        $this->assertSame('120.00', $custom['paid_amount']);
        $this->assertSame('100.00', $total['paid_amount']);
        $this->assertSame('80.00', $base['paid_amount']);
    }

    public function test_custom_payment_without_a_custom_amount_is_ineligible(): void
    {
        $this->assertIneligible('A paid payment is required.', function (): void {
            $this->quoteFor('2026-09-11', '09:00:00', [
                'is_custom_amount' => true,
                'custom_amount' => null,
                'total_amount' => '100.00',
                'amount' => '80.00',
            ]);
        });
    }

    public function test_zero_decimal_currency_is_calculated_in_integer_minor_units(): void
    {
        $quote = $this->quoteFor('2026-08-27', '09:00:00', [
            'currency' => 'jpy',
            'total_amount' => '101.00',
        ]);

        $this->assertSame('JPY', $quote['currency']);
        $this->assertSame('101.00', $quote['paid_amount']);
        $this->assertSame('25.00', $quote['suggested_deduction']);
        $this->assertSame('76.00', $quote['suggested_refund']);
    }

    public function test_krw_uses_stripes_zero_decimal_currency_rules(): void
    {
        $quote = $this->quoteFor('2026-08-27', '09:00:00', [
            'currency' => 'krw',
            'total_amount' => '101.00',
        ]);

        $this->assertSame('KRW', $quote['currency']);
        $this->assertSame('25.00', $quote['suggested_deduction']);
        $this->assertSame('76.00', $quote['suggested_refund']);
    }

    public function test_currency_must_be_a_nonblank_three_letter_code(): void
    {
        foreach ([null, '', '   ', 'US', 'USDD', '12$'] as $currency) {
            $this->assertIneligible('Payment currency is unavailable.', function () use ($currency): void {
                $this->quoteFor('2026-09-11', '09:00:00', ['currency' => $currency]);
            });
        }
    }

    public function test_valid_currency_outside_zero_decimal_list_defaults_to_two_decimals(): void
    {
        $quote = $this->quoteFor('2026-08-27', '09:00:00', [
            'currency' => 'eur',
            'total_amount' => '101.00',
        ]);

        $this->assertSame('EUR', $quote['currency']);
        $this->assertSame('25.25', $quote['suggested_deduction']);
        $this->assertSame('75.75', $quote['suggested_refund']);
    }

    public function test_utc_request_instant_and_local_travel_time_use_the_business_timezone_band(): void
    {
        $order = $this->makeOrder('2026-08-14', '10:00:00');
        OrderPayment::factory()->for($order)->create([
            'payment_status' => 'paid',
            'payment_intent_id' => 'pi_timezone',
            'total_amount' => '100.00',
            'currency' => 'USD',
        ]);
        $quote = app(CancellationPolicyService::class)->quote(
            $order->fresh(),
            Carbon::parse('2026-08-12 13:00:00', 'UTC')->toImmutable(),
        );

        $this->assertSame(75, $quote['deduction_percentage']);
        $this->assertSame(49 * 60 * 60, $quote['seconds_remaining']);
        $this->assertSame('2026-08-14T10:00:00-04:00', $quote['travel_starts_at']);
    }

    public function test_preferred_time_accepts_the_existing_hours_and_minutes_format(): void
    {
        $quote = $this->quoteFor('2026-08-14', '10:00');

        $this->assertSame('2026-08-14T10:00:00-04:00', $quote['travel_starts_at']);
    }

    public function test_preferred_time_must_be_present_and_well_formed(): void
    {
        foreach ([null, '', '   ', 'not-a-time', '25:00'] as $preferredTime) {
            $this->assertIneligible('Travel timing is unavailable.', function () use ($preferredTime): void {
                $this->quoteFor('2026-09-11', $preferredTime);
            });
        }
    }

    public function test_activity_package_and_itinerary_morph_types_are_eligible(): void
    {
        foreach (['activity', 'package', 'itinerary'] as $type) {
            $quote = $this->quoteFor('2026-09-11', '09:00:00', [], [
                'orderable_type' => $type,
            ]);

            $this->assertSame('90.00', $quote['suggested_refund']);
        }
    }

    public function test_legacy_activity_package_and_itinerary_class_types_are_eligible(): void
    {
        foreach ([Activity::class, \App\Models\Package::class, \App\Models\Itinerary::class] as $type) {
            $quote = $this->quoteFor('2026-09-11', '09:00:00', [], [
                'orderable_type' => $type,
            ]);

            $this->assertSame('90.00', $quote['suggested_refund']);
        }
    }

    public function test_terminal_orders_are_ineligible(): void
    {
        foreach (['completed', 'cancelled', 'refunded'] as $status) {
            $this->assertIneligible('Order is no longer eligible for cancellation.', function () use ($status): void {
                $this->quoteFor('2026-09-11', '09:00:00', [], ['status' => $status]);
            });
        }
    }

    public function test_soft_deleted_order_is_ineligible(): void
    {
        $order = $this->makeOrder('2026-09-11', '09:00:00');
        OrderPayment::factory()->for($order)->create([
            'payment_status' => 'paid',
            'payment_intent_id' => 'pi_deleted',
            'total_amount' => '100.00',
            'currency' => 'USD',
        ]);
        $order->delete();

        $this->assertIneligible('Order is no longer eligible for cancellation.', function () use ($order): void {
            app(CancellationPolicyService::class)->quote($order);
        });
    }

    public function test_suggested_amounts_are_capped_for_extreme_or_malformed_percentages(): void
    {
        foreach ([-50, 250, 'invalid'] as $percentage) {
            config(['cancellation.bands' => [[
                'minimum_seconds' => 0,
                'deduction_percentage' => $percentage,
            ]]]);

            $quote = $this->quoteFor('2026-09-11', '09:00:00');

            $this->assertGreaterThanOrEqual(0.0, (float) $quote['suggested_deduction']);
            $this->assertLessThanOrEqual(100.0, (float) $quote['suggested_deduction']);
            $this->assertGreaterThanOrEqual(0.0, (float) $quote['suggested_refund']);
            $this->assertLessThanOrEqual(100.0, (float) $quote['suggested_refund']);
        }
    }

    public function test_started_or_past_travel_is_ineligible(): void
    {
        foreach ([
            ['2026-08-12', '09:00:00'],
            ['2026-08-12', '08:59:59'],
        ] as [$date, $time]) {
            $this->assertIneligible('Travel has already started.', function () use ($date, $time): void {
                $this->quoteFor($date, $time);
            });
        }
    }

    public function test_missing_or_unpaid_payment_is_ineligible(): void
    {
        $order = $this->makeOrder('2026-09-11', '09:00:00');

        $this->assertIneligible('A paid payment is required.', function () use ($order): void {
            app(CancellationPolicyService::class)->quote($order);
        });

        $this->assertIneligible('A paid payment is required.', function (): void {
            $this->quoteFor('2026-09-11', '09:00:00', ['payment_status' => 'partial']);
        });
    }

    public function test_missing_stripe_intent_is_ineligible(): void
    {
        $this->assertIneligible('A refundable payment reference is required.', function (): void {
            $this->quoteFor('2026-09-11', '09:00:00', ['payment_intent_id' => null]);
        });
    }

    public function test_transfer_is_ineligible_even_when_paid(): void
    {
        $this->assertIneligible('This booking type cannot be cancelled online.', function (): void {
            $this->quoteFor('2026-09-11', '09:00:00', [], ['orderable_type' => 'transfer']);
        });
    }

    private function quoteFor(
        string $travelDate,
        ?string $preferredTime,
        array $paymentOverrides = [],
        array $orderOverrides = [],
    ): array {
        $order = $this->makeOrder($travelDate, $preferredTime, $orderOverrides);
        OrderPayment::factory()->for($order)->create(array_merge([
            'payment_status' => 'paid',
            'payment_intent_id' => 'pi_'.fake()->uuid(),
            'is_custom_amount' => false,
            'custom_amount' => null,
            'amount' => '80.00',
            'total_amount' => '100.00',
            'currency' => 'USD',
        ], $paymentOverrides));

        return app(CancellationPolicyService::class)->quote($order->fresh());
    }

    private function makeOrder(string $travelDate, ?string $preferredTime, array $overrides = []): Order
    {
        return Order::factory()->create(array_merge([
            'orderable_type' => (new Activity)->getMorphClass(),
            'travel_date' => $travelDate,
            'preferred_time' => $preferredTime,
            'status' => 'confirmed',
        ], $overrides));
    }

    private function assertIneligible(string $message, callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected the cancellation quote to be rejected.');
        } catch (DomainException $exception) {
            $this->assertSame($message, $exception->getMessage());
        }
    }
}
