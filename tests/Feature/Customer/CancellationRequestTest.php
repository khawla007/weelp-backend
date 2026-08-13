<?php

namespace Tests\Feature\Customer;

use App\Models\Activity;
use App\Models\CancellationRequest;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CancellationRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.timezone' => 'America/New_York']);
        Carbon::setTestNow(Carbon::parse('2026-08-12 09:00:00', 'America/New_York'));
        $this->customer = User::factory()->customer()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_customer_can_quote_an_owned_eligible_order(): void
    {
        $order = $this->paidOrder($this->customer, ['travel_date' => '2026-08-27']);

        $this->actingAs($this->customer, 'api')
            ->getJson("/api/customer/userorders/{$order->id}/cancellation-quote")
            ->assertOk()
            ->assertExactJson([
                'quote' => [
                    'policy_version' => 'general-v1',
                    'requested_at' => '2026-08-12T09:00:00-04:00',
                    'travel_starts_at' => '2026-08-27T09:00:00-04:00',
                    'seconds_remaining' => 15 * 24 * 60 * 60,
                    'paid_amount' => '100.00',
                    'currency' => 'USD',
                    'deduction_percentage' => 25,
                    'suggested_deduction' => '25.00',
                    'suggested_refund' => '75.00',
                ],
            ]);
    }

    public function test_customer_can_create_a_trimmed_snapshot_without_mutating_order_or_payment(): void
    {
        $order = $this->paidOrder($this->customer, ['travel_date' => '2026-08-27']);
        $payment = $order->payment;

        $response = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/userorders/{$order->id}/cancellation-requests", [
                'reason' => '  Our travel dates have changed.  ',
            ])
            ->assertCreated()
            ->assertJsonPath('cancellation.status', 'pending')
            ->assertJsonPath('cancellation.reason', 'Our travel dates have changed.')
            ->assertJsonPath('cancellation.suggested_refund', '75.00');

        $request = CancellationRequest::query()->sole();
        $this->assertSame($response->json('cancellation.id'), $request->id);
        $this->assertSame(config('cancellation'), $request->policy_snapshot);
        $this->assertSame('2026-08-12 09:00:00', $request->requested_at->format('Y-m-d H:i:s'));
        $this->assertSame('100.00', $request->paid_amount);
        $this->assertSame('25.00', $request->suggested_deduction_amount);
        $this->assertSame('75.00', $request->suggested_refund_amount);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('paid', $payment->fresh()->payment_status);
        $this->assertSame('0.00', $payment->fresh()->refunded_amount);
    }

    public function test_stored_snapshot_does_not_change_when_booking_values_change_later(): void
    {
        $order = $this->paidOrder($this->customer, ['travel_date' => '2026-08-27']);

        $created = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/userorders/{$order->id}/cancellation-requests", [
                'reason' => 'Our travel dates have changed.',
            ])
            ->assertCreated();

        $order->update(['travel_date' => '2026-09-30']);
        $order->payment()->update(['total_amount' => '500.00']);

        $this->actingAs($this->customer, 'api')
            ->getJson("/api/customer/userorders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('order.cancellation.travel_starts_at', $created->json('cancellation.travel_starts_at'))
            ->assertJsonPath('order.cancellation.paid_amount', '100.00')
            ->assertJsonPath('order.cancellation.deduction_percentage', 25)
            ->assertJsonPath('order.cancellation.suggested_refund', '75.00');
    }

    public function test_endpoints_hide_orders_owned_by_another_customer_and_trashed_orders(): void
    {
        $other = User::factory()->customer()->create();
        $otherOrder = $this->paidOrder($other);
        $trashed = $this->paidOrder($this->customer);
        $trashed->delete();

        foreach ([$otherOrder, $trashed] as $order) {
            $this->actingAs($this->customer, 'api')
                ->getJson("/api/customer/userorders/{$order->id}/cancellation-quote")
                ->assertNotFound();
            $this->actingAs($this->customer, 'api')
                ->postJson("/api/customer/userorders/{$order->id}/cancellation-requests", [
                    'reason' => 'Our travel dates changed.',
                ])
                ->assertNotFound();
        }
    }

    public function test_endpoints_require_authentication(): void
    {
        $order = $this->paidOrder($this->customer);

        $this->getJson("/api/customer/userorders/{$order->id}/cancellation-quote")
            ->assertUnauthorized();
        $this->postJson("/api/customer/userorders/{$order->id}/cancellation-requests", [
            'reason' => 'Our travel dates changed.',
        ])->assertUnauthorized();
    }

    public function test_reason_is_trimmed_and_must_meet_the_configured_bounds(): void
    {
        $order = $this->paidOrder($this->customer);

        foreach (['         ', str_repeat('a', 9), str_repeat('a', 1001)] as $reason) {
            $this->actingAs($this->customer, 'api')
                ->postJson("/api/customer/userorders/{$order->id}/cancellation-requests", compact('reason'))
                ->assertUnprocessable()
                ->assertJsonValidationErrors('reason');
        }
    }

    public function test_ineligible_orders_return_a_safe_conflict(): void
    {
        $order = $this->paidOrder($this->customer, ['status' => 'completed']);

        foreach (['get', 'post'] as $method) {
            $response = $method === 'get'
                ? $this->actingAs($this->customer, 'api')->getJson("/api/customer/userorders/{$order->id}/cancellation-quote")
                : $this->actingAs($this->customer, 'api')->postJson("/api/customer/userorders/{$order->id}/cancellation-requests", [
                    'reason' => 'Our travel dates changed.',
                ]);

            $response->assertConflict()
                ->assertExactJson(['message' => 'Order is no longer eligible for cancellation.']);
        }
    }

    public function test_unpaid_and_started_orders_return_safe_conflicts(): void
    {
        $unpaid = $this->paidOrder($this->customer, [], ['payment_status' => 'pending']);
        $started = $this->paidOrder($this->customer, [
            'travel_date' => '2026-08-12',
            'preferred_time' => '09:00:00',
        ]);

        $this->actingAs($this->customer, 'api')
            ->getJson("/api/customer/userorders/{$unpaid->id}/cancellation-quote")
            ->assertConflict()
            ->assertExactJson(['message' => 'A paid payment is required.']);
        $this->actingAs($this->customer, 'api')
            ->getJson("/api/customer/userorders/{$started->id}/cancellation-quote")
            ->assertConflict()
            ->assertExactJson(['message' => 'Travel has already started.']);
    }

    public function test_quote_and_store_reject_every_ineligible_state_without_mutation(): void
    {
        $cases = [
            [$this->paidOrder($this->customer, [], ['payment_status' => 'pending']), 'A paid payment is required.'],
            [$this->paidOrder($this->customer, ['travel_date' => '2026-08-12']), 'Travel has already started.'],
            [$this->paidOrder($this->customer, ['travel_date' => '2026-08-11']), 'Travel has already started.'],
        ];

        foreach (['completed', 'cancelled', 'refunded'] as $status) {
            $cases[] = [
                $this->paidOrder($this->customer, ['status' => $status]),
                'Order is no longer eligible for cancellation.',
            ];
        }

        foreach ($cases as [$order, $message]) {
            $originalOrderStatus = $order->status;
            $originalPaymentStatus = $order->payment->payment_status;

            $this->actingAs($this->customer, 'api')
                ->getJson("/api/customer/userorders/{$order->id}/cancellation-quote")
                ->assertConflict()
                ->assertExactJson(['message' => $message]);
            $this->actingAs($this->customer, 'api')
                ->postJson("/api/customer/userorders/{$order->id}/cancellation-requests", [
                    'reason' => 'Our travel dates have changed.',
                ])
                ->assertConflict()
                ->assertExactJson(['message' => $message]);

            $this->assertDatabaseMissing('cancellation_requests', ['order_id' => $order->id]);
            $this->assertSame($originalOrderStatus, $order->fresh()->status);
            $this->assertSame($originalPaymentStatus, $order->payment->fresh()->payment_status);
        }
    }

    public function test_only_one_unresolved_request_can_exist_and_a_duplicate_does_not_mutate_state(): void
    {
        $order = $this->paidOrder($this->customer);
        $payload = ['reason' => 'Our travel dates have changed.'];

        $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/userorders/{$order->id}/cancellation-requests", $payload)
            ->assertCreated();

        foreach ([
            CancellationRequest::STATUS_PENDING,
            CancellationRequest::STATUS_REFUND_PROCESSING,
            CancellationRequest::STATUS_REFUND_FAILED,
        ] as $status) {
            CancellationRequest::query()->where('order_id', $order->id)->update(['status' => $status]);

            $this->actingAs($this->customer, 'api')
                ->getJson("/api/customer/userorders/{$order->id}/cancellation-quote")
                ->assertConflict()
                ->assertJsonPath('message', 'A cancellation request is already being reviewed.');

            $this->actingAs($this->customer, 'api')
                ->postJson("/api/customer/userorders/{$order->id}/cancellation-requests", $payload)
                ->assertConflict()
                ->assertJsonPath('message', 'A cancellation request is already being reviewed.');

            $this->assertSame(1, CancellationRequest::query()->where('order_id', $order->id)->count());
        }
    }

    public function test_a_resolved_request_does_not_open_an_unsupported_customer_appeal(): void
    {
        $order = $this->paidOrder($this->customer);
        CancellationRequest::factory()->for($order)->create([
            'customer_id' => $this->customer->id,
            'status' => CancellationRequest::STATUS_REJECTED,
        ]);

        $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/userorders/{$order->id}/cancellation-requests", [
                'reason' => 'We would like the request reconsidered.',
            ])
            ->assertConflict()
            ->assertExactJson([
                'message' => 'This booking already has a cancellation decision. Contact support for help.',
            ]);
        $this->assertSame(1, CancellationRequest::query()->where('order_id', $order->id)->count());
    }

    public function test_customer_list_and_detail_expose_the_complete_safe_contract(): void
    {
        $order = $this->paidOrder($this->customer);
        $cancellation = CancellationRequest::factory()->for($order)->create([
            'customer_id' => $this->customer->id,
            'status' => CancellationRequest::STATUS_REFUND_FAILED,
            'reason' => 'The dates no longer work.',
            'final_refund_amount' => '70.00',
            'final_deduction_amount' => '30.00',
            'decision_explanation' => 'A smaller refund was approved.',
            'decided_at' => now(),
            'refund_outcome' => 'partial',
            'stripe_refund_id' => 're_customer_must_not_see',
            'idempotency_key' => 'cancel_customer_must_not_see',
            'failure_code' => 'provider_timeout',
            'failure_summary' => 'Provider request timed out.',
            'failure_disposition' => 'definitive',
        ]);

        $expectedKeys = [
            'id', 'status', 'reason', 'requested_at', 'policy_version', 'travel_starts_at',
            'seconds_remaining', 'currency', 'deduction_percentage', 'paid_amount',
            'suggested_deduction', 'suggested_refund', 'final_refund', 'final_deduction',
            'decision_explanation', 'decided_at', 'refund_completed_at', 'refund_outcome',
            'can_retry', 'can_reject',
        ];

        $detail = $this->actingAs($this->customer, 'api')
            ->getJson("/api/customer/userorders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('order.cancellation.id', $cancellation->id)
            ->assertJsonPath('order.cancellation.can_retry', true)
            ->assertJsonPath('order.cancellation.can_reject', true)
            ->assertJsonPath('order.cancellation_eligible', false)
            ->assertJsonPath('order.cancellation_ineligibility_reason', 'A cancellation request is already being reviewed.');

        $this->assertSame($expectedKeys, array_keys($detail->json('order.cancellation')));
        $this->assertSame([
            'id' => $cancellation->id,
            'status' => 'refund_failed',
            'reason' => 'The dates no longer work.',
            'requested_at' => $cancellation->fresh()->requested_at->toISOString(),
            'policy_version' => 'general-v1',
            'travel_starts_at' => $cancellation->fresh()->travel_starts_at->toISOString(),
            'seconds_remaining' => 30 * 24 * 60 * 60,
            'currency' => 'USD',
            'deduction_percentage' => 10,
            'paid_amount' => '100.00',
            'suggested_deduction' => '10.00',
            'suggested_refund' => '90.00',
            'final_refund' => '70.00',
            'final_deduction' => '30.00',
            'decision_explanation' => 'A smaller refund was approved.',
            'decided_at' => $cancellation->fresh()->decided_at->toISOString(),
            'refund_completed_at' => null,
            'refund_outcome' => 'partial',
            'can_retry' => true,
            'can_reject' => true,
        ], $detail->json('order.cancellation'));
        $this->assertCancellationSecretsAbsent($detail->json());

        $list = $this->actingAs($this->customer, 'api')->getJson('/api/customer/userorders')->assertOk();
        $listedOrder = collect($list->json('orders'))->firstWhere('id', $order->id);
        $this->assertSame($expectedKeys, array_keys($listedOrder['cancellation']));
        $this->assertFalse($listedOrder['cancellation_eligible']);
        $this->assertCancellationSecretsAbsent($list->json());
    }

    public function test_admin_detail_adds_only_safe_failure_fields_to_cancellation(): void
    {
        $order = $this->paidOrder($this->customer);
        $cancellation = CancellationRequest::factory()->for($order)->create([
            'customer_id' => $this->customer->id,
            'status' => CancellationRequest::STATUS_REFUND_FAILED,
            'failure_code' => 'provider_timeout',
            'failure_summary' => 'The refund provider did not confirm the request.',
            'stripe_refund_id' => 're_admin_contract_must_not_include',
            'idempotency_key' => 'cancel_admin_contract_must_not_include',
        ]);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'api')
            ->getJson("/api/admin/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.cancellation.id', $cancellation->id)
            ->assertJsonPath('data.cancellation.failure_code', 'provider_timeout')
            ->assertJsonPath('data.cancellation.failure_summary', 'The refund provider did not confirm the request.');

        $this->assertSame([
            'id', 'status', 'reason', 'requested_at', 'policy_version', 'travel_starts_at',
            'seconds_remaining', 'currency', 'deduction_percentage', 'paid_amount',
            'suggested_deduction', 'suggested_refund', 'final_refund', 'final_deduction',
            'decision_explanation', 'decided_at', 'refund_completed_at', 'refund_outcome',
            'can_retry', 'can_reject', 'failure_code', 'failure_summary',
        ], array_keys($response->json('data.cancellation')));
        $this->assertStringNotContainsString('re_admin_contract_must_not_include', $response->getContent());
        $this->assertStringNotContainsString('cancel_admin_contract_must_not_include', $response->getContent());
    }

    public function test_customer_refund_outcome_is_normalized_instead_of_exposing_persisted_text(): void
    {
        $order = $this->paidOrder($this->customer);
        $cancellation = CancellationRequest::factory()->for($order)->create([
            'customer_id' => $this->customer->id,
            'status' => CancellationRequest::STATUS_REFUND_FAILED,
            'refund_outcome' => 'Stripe timeout: socket 10.0.0.2 secret provider trace',
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->getJson("/api/customer/userorders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('order.cancellation.refund_outcome', 'failed');

        $this->assertStringNotContainsString('Stripe timeout', $response->getContent());
        $this->assertStringNotContainsString('10.0.0.2', $response->getContent());
        $this->assertStringNotContainsString('provider trace', $response->getContent());

        $admin = User::factory()->admin()->create();
        $adminResponse = $this->actingAs($admin, 'api')
            ->getJson("/api/admin/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.cancellation.id', $cancellation->id)
            ->assertJsonPath('data.cancellation.refund_outcome', 'failed');
        $this->assertStringNotContainsString('Stripe timeout', $adminResponse->getContent());
        $this->assertStringNotContainsString('10.0.0.2', $adminResponse->getContent());
        $this->assertStringNotContainsString('provider trace', $adminResponse->getContent());
    }

    private function paidOrder(User $customer, array $orderOverrides = [], array $paymentOverrides = []): Order
    {
        $activity = Activity::factory()->create();
        $order = Order::factory()->create(array_merge([
            'user_id' => $customer->id,
            'orderable_type' => $activity->getMorphClass(),
            'orderable_id' => $activity->id,
            'status' => 'confirmed',
            'travel_date' => '2026-09-11',
            'preferred_time' => '09:00:00',
        ], $orderOverrides));
        OrderPayment::factory()->for($order)->create(array_merge([
            'payment_status' => 'paid',
            'payment_intent_id' => 'pi_'.fake()->uuid(),
            'amount' => '80.00',
            'total_amount' => '100.00',
            'currency' => 'USD',
        ], $paymentOverrides));

        return $order->load('payment');
    }

    private function assertCancellationSecretsAbsent(array $payload): void
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR);

        foreach (['stripe_refund_id', 'idempotency_key', 'failure_code', 'failure_summary', 'failure_disposition', 'provider_timeout', 'Provider request timed out.'] as $secret) {
            $this->assertStringNotContainsString($secret, $json);
        }
    }
}
