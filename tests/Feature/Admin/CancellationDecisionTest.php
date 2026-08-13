<?php

namespace Tests\Feature\Admin;

use App\Contracts\StripeRefundGateway;
use App\Exceptions\RefundGatewayException;
use App\Models\Activity;
use App\Models\CancellationRequest;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use App\Services\CancellationNotificationService;
use App\Services\CancellationRefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class CancellationDecisionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private FakeStripeRefundGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-12 12:00:00');
        $this->admin = User::factory()->admin()->create();
        $this->gateway = new FakeStripeRefundGateway;
        $this->app->instance(StripeRefundGateway::class, $this->gateway);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_decision_endpoints_require_an_authenticated_admin(): void
    {
        [$cancellation] = $this->cancellation();
        $customer = User::factory()->customer()->create();
        $actions = [
            'approve' => ['final_refund' => '90.00'],
            'reject' => ['explanation' => 'The request does not qualify.'],
            'retry' => [],
        ];

        foreach ($actions as $action => $payload) {
            $url = "/api/admin/cancellation-requests/{$cancellation->id}/{$action}";
            $this->postJson($url, $payload)->assertUnauthorized();
        }

        foreach ($actions as $action => $payload) {
            $url = "/api/admin/cancellation-requests/{$cancellation->id}/{$action}";
            $this->actingAs($customer, 'api')->postJson($url, $payload)->assertForbidden();
        }
    }

    public function test_rejection_requires_an_explanation_and_does_not_change_order_or_payment(): void
    {
        [$cancellation, $order, $payment] = $this->cancellation();

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$cancellation->id}/reject")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('explanation');

        $this->assertSame('pending', $cancellation->fresh()->status);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('paid', $payment->fresh()->payment_status);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$cancellation->id}/reject", [
                'explanation' => 'The booking remains eligible to travel.',
            ])->assertOk()
            ->assertJsonPath('cancellation.status', 'rejected');

        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('paid', $payment->fresh()->payment_status);
    }

    public function test_zero_refund_checks_provider_skips_refund_creation_and_cancels_order(): void
    {
        [$cancellation, $order, $payment] = $this->cancellation(['suggested_refund_amount' => '0.00']);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$cancellation->id}/approve", [
                'final_refund' => '0.00',
            ])->assertOk()
            ->assertJsonPath('cancellation.refund_outcome', 'no_refund');

        $this->assertSame(['pi_cancel_test'], $this->gateway->refundedChecks);
        $this->assertCount(0, $this->gateway->refundCalls);
        $this->assertSame('approved', $cancellation->fresh()->status);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('paid', $payment->fresh()->payment_status);
        $this->assertSame('0.00', $payment->fresh()->refunded_amount);
    }

    public function test_zero_refund_reconciles_an_existing_provider_partial_or_full_refund(): void
    {
        foreach ([2500 => 'partially_refunded', 10000 => 'refunded'] as $providerMinor => $expectedStatus) {
            [$cancellation, $order, $payment] = $this->cancellation([
                'suggested_refund_amount' => '0.00',
            ], paymentIntentId: "pi_existing_{$providerMinor}");
            $this->gateway->refundedByIntent[$payment->payment_intent_id] = $providerMinor;

            $this->actingAs($this->admin, 'api')
                ->postJson("/api/admin/cancellation-requests/{$cancellation->id}/approve", [
                    'final_refund' => '0.00',
                ])->assertOk();

            $this->assertSame($expectedStatus, $payment->fresh()->payment_status);
            $this->assertSame(number_format($providerMinor / 100, 2, '.', ''), $payment->fresh()->refunded_amount);
            $this->assertSame('cancelled', $order->fresh()->status);
        }

        $this->assertCount(0, $this->gateway->refundCalls);
    }

    public function test_partial_and_full_approvals_use_exact_minor_units_and_reconcile_payment(): void
    {
        foreach ([
            ['25.00', 2500, 'partially_refunded', 'partial'],
            ['100.00', 10000, 'refunded', 'full'],
        ] as [$amount, $minor, $paymentStatus, $outcome]) {
            [$cancellation, $order, $payment] = $this->cancellation([
                'suggested_refund_amount' => $amount,
            ], paymentIntentId: "pi_{$minor}");

            $this->actingAs($this->admin, 'api')
                ->postJson("/api/admin/cancellation-requests/{$cancellation->id}/approve", [
                    'final_refund' => $amount,
                ])->assertOk()
                ->assertJsonPath('cancellation.refund_outcome', $outcome);

            $call = collect($this->gateway->refundCalls)->firstWhere('payment_intent_id', $payment->payment_intent_id);
            $this->assertSame($minor, $call['amount']);
            $this->assertSame("cancel-request-{$cancellation->id}", $call['idempotency_key']);
            $this->assertSame($paymentStatus, $payment->fresh()->payment_status);
            $this->assertSame($amount, $payment->fresh()->refunded_amount);
            $this->assertSame('approved', $cancellation->fresh()->status);
            $this->assertSame('cancelled', $order->fresh()->status);
        }
    }

    public function test_post_refund_balance_is_authoritative_when_an_external_refund_arrives_during_approval(): void
    {
        [$cancellation, $order, $payment] = $this->cancellation([
            'suggested_refund_amount' => '25.00',
        ], paymentIntentId: 'pi_post_balance');
        $this->gateway->refundedAmountsByCheck = [0, 4000];

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$cancellation->id}/approve", [
                'final_refund' => '25.00',
            ])->assertOk();

        $this->assertSame('40.00', $payment->fresh()->refunded_amount);
        $this->assertSame('partially_refunded', $payment->fresh()->payment_status);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertCount(2, $this->gateway->refundedChecks);
    }

    public function test_jpy_uses_zero_decimal_minor_units_and_rejects_fractional_amounts(): void
    {
        [$fractional] = $this->cancellation([
            'paid_amount' => '100.00',
            'currency' => 'JPY',
            'suggested_refund_amount' => '25.00',
        ], currency: 'JPY', paymentIntentId: 'pi_jpy_fraction');

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$fractional->id}/approve", [
                'final_refund' => '25.50',
                'explanation' => 'Adjusted for the customer request.',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors('final_refund');

        [$whole, , $payment] = $this->cancellation([
            'paid_amount' => '100.00',
            'currency' => 'JPY',
            'suggested_refund_amount' => '25.00',
        ], currency: 'JPY', paymentIntentId: 'pi_jpy_whole');

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$whole->id}/approve", [
                'final_refund' => '25',
            ])->assertOk();

        $call = collect($this->gateway->refundCalls)->firstWhere('payment_intent_id', 'pi_jpy_whole');
        $this->assertSame(25, $call['amount']);
        $this->assertSame('25.00', $payment->fresh()->refunded_amount);
    }

    public function test_negative_malformed_and_above_provider_remaining_amounts_are_rejected(): void
    {
        foreach (['-1.00', '1.234', 'not-money'] as $amount) {
            [$cancellation] = $this->cancellation(paymentIntentId: 'pi_invalid_'.md5($amount));

            $this->actingAs($this->admin, 'api')
                ->postJson("/api/admin/cancellation-requests/{$cancellation->id}/approve", [
                    'final_refund' => $amount,
                    'explanation' => 'Adjusted amount.',
                ])->assertUnprocessable()
                ->assertJsonValidationErrors('final_refund');
        }

        [$cancellation, $order, $payment] = $this->cancellation(paymentIntentId: 'pi_stale_local');
        $this->gateway->refundedByIntent['pi_stale_local'] = 8000;

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$cancellation->id}/approve", [
                'final_refund' => '25.00',
                'explanation' => 'Adjusted amount.',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors('final_refund');

        $this->assertSame('pending', $cancellation->fresh()->status);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('paid', $payment->fresh()->payment_status);
        $this->assertCount(0, $this->gateway->refundCalls);

        [$locallyRefunded, , $localPayment] = $this->cancellation(paymentIntentId: 'pi_local_balance');
        $localPayment->update([
            'payment_status' => 'partially_refunded',
            'refunded_amount' => '30.00',
        ]);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$locallyRefunded->id}/approve", [
                'final_refund' => '80.00',
                'explanation' => 'Adjusted amount.',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors('final_refund');

        $this->assertSame('pending', $locallyRefunded->fresh()->status);
        $this->assertCount(0, $this->gateway->refundCalls);
    }

    public function test_adjusted_approval_requires_an_explanation(): void
    {
        [$cancellation] = $this->cancellation(['suggested_refund_amount' => '75.00']);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$cancellation->id}/approve", [
                'final_refund' => '25.00',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors('explanation');
    }

    public function test_definitive_failure_is_safe_and_can_be_rejected_or_retried(): void
    {
        [$rejectable, $order, $payment] = $this->cancellation(paymentIntentId: 'pi_declined');
        $this->gateway->nextFailure = RefundGatewayException::definitive('card_declined', 'Stripe raw decline details');

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$rejectable->id}/approve", [
                'final_refund' => '90.00',
            ])->assertStatus(502)
            ->assertJsonPath('message', 'The refund provider rejected this refund.');

        $this->assertStringNotContainsString('Stripe raw', $response->getContent());
        $this->assertSame('refund_failed', $rejectable->fresh()->status);
        $this->assertSame('definitive', $rejectable->fresh()->failure_disposition);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('paid', $payment->fresh()->payment_status);
        $this->assertSame(1, Notification::query()->where(
            'deduplication_key',
            "cancellation:{$rejectable->id}:refund_failed:user:{$this->admin->id}",
        )->count());
        $this->actingAs($this->admin, 'api')
            ->getJson("/api/admin/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.cancellation.can_retry', true)
            ->assertJsonPath('data.cancellation.can_reject', true);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$rejectable->id}/reject", [
                'explanation' => 'The provider rejected the attempted refund.',
            ])->assertOk();

        [$retryable] = $this->cancellation(paymentIntentId: 'pi_declined_retry');
        $this->gateway->nextFailure = RefundGatewayException::definitive('invalid_request', 'Raw details');
        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$retryable->id}/approve", [
                'final_refund' => '90.00',
            ])->assertStatus(502);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$retryable->id}/retry")
            ->assertOk()
            ->assertJsonPath('cancellation.status', 'approved');
    }

    public function test_retry_reconciles_external_refund_and_rejects_captured_amount_above_remaining(): void
    {
        [$cancellation, $order, $payment] = $this->cancellation(paymentIntentId: 'pi_retry_external');
        $this->gateway->nextFailure = RefundGatewayException::definitive('declined', 'Raw decline');

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$cancellation->id}/approve", [
                'final_refund' => '90.00',
            ])->assertStatus(502);
        $this->assertSame(CancellationRequest::STATUS_REFUND_FAILED, $cancellation->fresh()->status);
        $this->gateway->refundedByIntent['pi_retry_external'] = 3000;
        $refundCallsBeforeRetry = count($this->gateway->refundCalls);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$cancellation->id}/retry")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('final_refund');

        $this->assertSame($refundCallsBeforeRetry, count($this->gateway->refundCalls));
        $this->assertSame(CancellationRequest::STATUS_REFUND_FAILED, $cancellation->fresh()->status);
        $this->assertSame('definitive', $cancellation->fresh()->failure_disposition);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('partially_refunded', $payment->fresh()->payment_status);
        $this->assertSame('30.00', $payment->fresh()->refunded_amount);
        $this->assertSame(1, $this->gateway->transactionLevels[array_key_last($this->gateway->transactionLevels)]);
    }

    public function test_indeterminate_failure_cannot_be_rejected_and_retries_with_the_same_key(): void
    {
        [$cancellation, $order] = $this->cancellation(paymentIntentId: 'pi_timeout');
        $this->gateway->nextFailure = RefundGatewayException::indeterminate('provider_timeout', 'socket 10.0.0.1 timed out');

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$cancellation->id}/approve", [
                'final_refund' => '90.00',
            ])->assertStatus(502);
        $this->assertStringNotContainsString('10.0.0.1', $response->getContent());
        $this->assertSame('indeterminate', $cancellation->fresh()->failure_disposition);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->actingAs($this->admin, 'api')
            ->getJson("/api/admin/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.cancellation.can_retry', true)
            ->assertJsonPath('data.cancellation.can_reject', false);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$cancellation->id}/reject", [
                'explanation' => 'Reject this uncertain request.',
            ])->assertConflict();

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$cancellation->id}/retry")
            ->assertOk();

        $keys = array_column($this->gateway->refundCalls, 'idempotency_key');
        $this->assertSame(["cancel-request-{$cancellation->id}", "cancel-request-{$cancellation->id}"], $keys);
        $this->assertCount(1, $this->gateway->createdRefunds);
    }

    public function test_post_refund_balance_failure_keeps_processing_and_logs_safe_operator_context(): void
    {
        Log::spy();
        [$cancellation, $order, $payment] = $this->cancellation(paymentIntentId: 'pi_postcheck_timeout');
        $this->gateway->refundedFailureOnCheck = 2;
        $this->gateway->refundedFailure = RefundGatewayException::indeterminate(
            'postcheck_timeout',
            'raw provider timeout',
        );

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$cancellation->id}/approve", [
                'final_refund' => '90.00',
            ])->assertStatus(502)
            ->assertJsonPath('message', 'The refund provider did not confirm whether the refund succeeded.');

        $this->assertStringNotContainsString('raw provider timeout', $response->getContent());
        $this->assertSame(CancellationRequest::STATUS_REFUND_PROCESSING, $cancellation->fresh()->status);
        $this->assertNull($cancellation->fresh()->failure_disposition);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('paid', $payment->fresh()->payment_status);
        $this->assertCount(1, $this->gateway->createdRefunds);
        $this->assertSame(1, Notification::query()->where(
            'deduplication_key',
            "cancellation:{$cancellation->id}:refund_confirmation_failed:user:{$this->admin->id}",
        )->count());
        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context): bool => $message === 'Cancellation refund provider failure.'
                && $context['cancellation_request_id'] === $cancellation->id
                && $context['order_id'] === $order->id
                && $context['payment_intent_id'] === 'pi_postcheck_timeout'
                && $context['failure_code'] === 'postcheck_timeout'
                && $context['failure_disposition'] === 'indeterminate'
                && $context['safe_summary'] === 'The refund provider did not confirm whether the refund succeeded.'
                && $context['exception_class'] === RefundGatewayException::class
                && ! array_key_exists('exception', $context)
                && ! array_key_exists('provider_message', $context),
        )->once();
    }

    public function test_malformed_provider_success_is_an_indeterminate_safe_failure(): void
    {
        [$cancellation, $order, $payment] = $this->cancellation(paymentIntentId: 'pi_malformed');
        $this->gateway->nextResult = (object) [
            'id' => null,
            'amount' => 9000,
            'status' => 'succeeded',
            'raw_secret' => 'must-not-leak',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$cancellation->id}/approve", [
                'final_refund' => '90.00',
            ])->assertStatus(502)
            ->assertJsonPath('message', 'The refund provider did not confirm whether the refund succeeded.');

        $this->assertStringNotContainsString('must-not-leak', $response->getContent());
        $this->assertSame('refund_failed', $cancellation->fresh()->status);
        $this->assertSame('indeterminate', $cancellation->fresh()->failure_disposition);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('paid', $payment->fresh()->payment_status);
    }

    public function test_local_finalization_failure_leaves_processing_and_stale_retry_completes_same_refund(): void
    {
        [$cancellation, $order, $payment] = $this->cancellation(paymentIntentId: 'pi_local_failure');
        $gateway = $this->gateway;
        $failing = new class($gateway, app(CancellationNotificationService::class)) extends CancellationRefundService
        {
            protected function beforeFinalization(CancellationRequest $request, object $refund): void
            {
                throw new RuntimeException('database became unavailable');
            }
        };
        $this->app->instance(CancellationRefundService::class, $failing);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$cancellation->id}/approve", [
                'final_refund' => '90.00',
            ])->assertStatus(500);
        $this->assertStringNotContainsString('database became', $response->getContent());
        $this->assertSame('refund_processing', $cancellation->fresh()->status);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('paid', $payment->fresh()->payment_status);

        $this->app->instance(CancellationRefundService::class, new CancellationRefundService(
            $gateway,
            app(CancellationNotificationService::class),
        ));
        Carbon::setTestNow(now()->addSeconds(config('cancellation.refund_processing_stale_after_seconds')));

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$cancellation->id}/retry")
            ->assertOk();

        $this->assertSame('approved', $cancellation->fresh()->status);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertCount(2, $gateway->refundCalls);
        $this->assertCount(1, $gateway->createdRefunds);
        $this->assertSame("cancel-request-{$cancellation->id}", $cancellation->fresh()->idempotency_key);
    }

    public function test_processing_retry_is_blocked_one_second_before_stale_and_allowed_at_boundary(): void
    {
        $threshold = (int) config('cancellation.refund_processing_stale_after_seconds');
        [$cancellation] = $this->cancellation([
            'status' => CancellationRequest::STATUS_REFUND_PROCESSING,
            'final_refund_amount' => '90.00',
            'final_deduction_amount' => '10.00',
            'idempotency_key' => 'cancel-request-boundary',
            'decided_by' => $this->admin->id,
            'decided_at' => now(),
            'updated_at' => now(),
        ], paymentIntentId: 'pi_boundary');

        Carbon::setTestNow(now()->addSeconds($threshold - 1));
        $this->actingAs($this->admin, 'api')
            ->getJson("/api/admin/orders/{$cancellation->order_id}")
            ->assertOk()
            ->assertJsonPath('data.cancellation.can_retry', false);
        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$cancellation->id}/retry")
            ->assertConflict();

        Carbon::setTestNow($cancellation->fresh()->updated_at->copy()->addSeconds($threshold));
        $this->actingAs($this->admin, 'api')
            ->getJson("/api/admin/orders/{$cancellation->order_id}")
            ->assertOk()
            ->assertJsonPath('data.cancellation.can_retry', true);
        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$cancellation->id}/retry")
            ->assertOk();
    }

    public function test_terminal_and_invalid_transitions_do_not_call_provider_again(): void
    {
        foreach ([CancellationRequest::STATUS_APPROVED, CancellationRequest::STATUS_REJECTED] as $status) {
            [$cancellation] = $this->cancellation(['status' => $status], paymentIntentId: "pi_terminal_{$status}");

            foreach (['approve', 'retry', 'reject'] as $action) {
                $payload = $action === 'approve'
                    ? ['final_refund' => '90.00']
                    : ($action === 'reject' ? ['explanation' => 'Cannot transition.'] : []);
                $this->actingAs($this->admin, 'api')
                    ->postJson("/api/admin/cancellation-requests/{$cancellation->id}/{$action}", $payload)
                    ->assertConflict();
            }
        }

        $this->assertCount(0, $this->gateway->refundCalls);
        $this->assertCount(0, $this->gateway->refundedChecks);
    }

    public function test_repeated_approve_and_retry_after_success_do_not_create_another_refund(): void
    {
        [$cancellation] = $this->cancellation(paymentIntentId: 'pi_repeat_success');
        $url = "/api/admin/cancellation-requests/{$cancellation->id}";

        $this->actingAs($this->admin, 'api')
            ->postJson("{$url}/approve", ['final_refund' => '90.00'])
            ->assertOk();
        $this->actingAs($this->admin, 'api')
            ->postJson("{$url}/approve", ['final_refund' => '90.00'])
            ->assertConflict();
        $this->actingAs($this->admin, 'api')
            ->postJson("{$url}/retry")
            ->assertConflict();

        $this->assertCount(1, $this->gateway->refundCalls);
        $this->assertCount(1, $this->gateway->createdRefunds);
        // RefreshDatabase owns level 1; an application transaction would be level 2.
        $this->assertSame([1, 1, 1], $this->gateway->transactionLevels);
    }

    public function test_legacy_refund_shortcut_is_blocked_without_stripe_and_unresolved_requests_block_status_updates(): void
    {
        [$cancellation, $order] = $this->cancellation();

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/orders/{$order->id}", ['status' => 'completed'])
            ->assertConflict()
            ->assertJsonPath('message', 'Resolve the customer cancellation request before changing this order.');

        $cancellation->update(['status' => CancellationRequest::STATUS_REJECTED]);
        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/orders/{$order->id}", ['status' => 'refunded'])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Use the cancellation request workflow to issue refunds.');

        $this->assertCount(0, $this->gateway->refundCalls);
    }

    public function test_all_actionable_cancellation_states_block_ordinary_order_status_updates(): void
    {
        foreach (CancellationRequest::ADMIN_ATTENTION_STATUSES as $status) {
            [$cancellation, $order] = $this->cancellation(
                ['status' => $status],
                paymentIntentId: "pi_status_guard_{$status}",
            );

            $this->actingAs($this->admin, 'api')
                ->putJson("/api/admin/orders/{$order->id}", ['status' => 'completed'])
                ->assertConflict()
                ->assertJsonPath('message', 'Resolve the customer cancellation request before changing this order.');

            $this->assertSame('confirmed', $order->fresh()->status);
            $this->assertSame($status, $cancellation->fresh()->status);
        }
    }

    public function test_cancellation_decisions_require_the_order_to_be_active(): void
    {
        [$cancellation, $order] = $this->cancellation();
        $order->delete();

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$cancellation->id}/approve", [
                'final_refund' => '90.00',
            ])
            ->assertNotFound();

        $this->assertSame(CancellationRequest::STATUS_PENDING, $cancellation->fresh()->status);
        $this->assertNotNull($order->fresh()->deleted_at);
        $this->assertCount(0, $this->gateway->refundCalls);
    }

    /** @return array{CancellationRequest, Order, OrderPayment} */
    private function cancellation(
        array $requestAttributes = [],
        string $currency = 'USD',
        string $paymentIntentId = 'pi_cancel_test',
    ): array {
        $customer = User::factory()->customer()->create();
        $activity = Activity::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'orderable_type' => $activity->getMorphClass(),
            'orderable_id' => $activity->id,
            'status' => 'confirmed',
        ]);
        $payment = OrderPayment::factory()->for($order)->create([
            'payment_status' => 'paid',
            'payment_intent_id' => $paymentIntentId,
            'total_amount' => '100.00',
            'refunded_amount' => '0.00',
            'currency' => $currency,
        ]);
        $cancellation = CancellationRequest::factory()->for($order)->create(array_merge([
            'customer_id' => $customer->id,
            'paid_amount' => '100.00',
            'currency' => $currency,
            'suggested_refund_amount' => '90.00',
            'suggested_deduction_amount' => '10.00',
        ], $requestAttributes));

        return [$cancellation, $order, $payment];
    }
}

class FakeStripeRefundGateway implements StripeRefundGateway
{
    /** @var array<string, int> */
    public array $refundedByIntent = [];

    /** @var list<string> */
    public array $refundedChecks = [];

    /** @var list<array{payment_intent_id: string, amount: int, idempotency_key: string, metadata: array}> */
    public array $refundCalls = [];

    /** @var array<string, object> */
    public array $createdRefunds = [];

    public ?RefundGatewayException $nextFailure = null;

    public ?object $nextResult = null;

    /** @var list<int> */
    public array $refundedAmountsByCheck = [];

    public ?int $refundedFailureOnCheck = null;

    public ?RefundGatewayException $refundedFailure = null;

    /** @var list<int> */
    public array $transactionLevels = [];

    public function refundedAmount(string $paymentIntentId): int
    {
        $this->transactionLevels[] = \Illuminate\Support\Facades\DB::transactionLevel();
        $this->refundedChecks[] = $paymentIntentId;

        if ($this->refundedFailureOnCheck === count($this->refundedChecks) && $this->refundedFailure) {
            throw $this->refundedFailure;
        }

        if ($this->refundedAmountsByCheck !== []) {
            return array_shift($this->refundedAmountsByCheck);
        }

        return $this->refundedByIntent[$paymentIntentId] ?? 0;
    }

    public function refund(
        string $paymentIntentId,
        int $amountInMinorUnits,
        string $idempotencyKey,
        array $metadata,
    ): object {
        $this->transactionLevels[] = \Illuminate\Support\Facades\DB::transactionLevel();
        $this->refundCalls[] = [
            'payment_intent_id' => $paymentIntentId,
            'amount' => $amountInMinorUnits,
            'idempotency_key' => $idempotencyKey,
            'metadata' => $metadata,
        ];

        if ($this->nextFailure) {
            $failure = $this->nextFailure;
            $this->nextFailure = null;

            throw $failure;
        }

        if ($this->nextResult) {
            $result = $this->nextResult;
            $this->nextResult = null;

            return $result;
        }

        if (! isset($this->createdRefunds[$idempotencyKey])) {
            $this->createdRefunds[$idempotencyKey] = (object) [
                'id' => 're_'.count($this->createdRefunds),
                'amount' => $amountInMinorUnits,
                'status' => 'succeeded',
            ];
            $this->refundedByIntent[$paymentIntentId] = ($this->refundedByIntent[$paymentIntentId] ?? 0)
                + $amountInMinorUnits;
        }

        return $this->createdRefunds[$idempotencyKey];
    }
}
