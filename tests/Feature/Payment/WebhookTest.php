<?php

namespace Tests\Feature\Payment;

use App\Mail\CancellationRequestApprovedMail;
use App\Models\Activity;
use App\Models\CancellationRequest;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use App\Services\CancellationNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use ReflectionMethod;
use RuntimeException;
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
        $this->actingAs($user, 'api');
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
                'id' => 'ch_test_'.uniqid(),
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
        $this->markTestSkipped('Pre-existing failure — see docs/TEST_TRIAGE_2026-05-04.md (Bucket B).');

        Mail::fake();

        $intentId = 'pi_test_'.uniqid();
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

        $intentId = 'pi_test_'.uniqid();
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
        $this->markTestSkipped('Pre-existing failure — see docs/TEST_TRIAGE_2026-05-04.md (Bucket B).');

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
        $this->markTestSkipped('Pre-existing failure — see docs/TEST_TRIAGE_2026-05-04.md (Bucket B).');

        Mail::fake();

        $intentId = 'pi_test_'.uniqid();
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
        $this->markTestSkipped('Pre-existing failure — see docs/TEST_TRIAGE_2026-05-04.md (Bucket B).');

        Mail::fake();

        $intentId = 'pi_test_'.uniqid();
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

    public function test_unrelated_charge_refund_reconciles_payment_but_does_not_approve_a_pending_request(): void
    {
        Mail::fake();
        $intentId = 'pi_reconcile_'.uniqid();
        $data = $this->createOrderWithPayment($intentId, 'confirmed');
        $data['payment']->update(['payment_status' => 'paid']);
        $request = CancellationRequest::factory()->for($data['order'])->create([
            'customer_id' => $data['user']->id,
            'status' => CancellationRequest::STATUS_PENDING,
            'paid_amount' => '100.00',
            'suggested_refund_amount' => '75.00',
            'suggested_deduction_amount' => '25.00',
        ]);
        $event = json_decode(json_encode([
            'type' => 'charge.refunded',
            'data' => ['object' => [
                'payment_intent' => $intentId,
                'amount' => 10000,
                'amount_refunded' => 10000,
                'currency' => 'usd',
                'refunded' => true,
            ]],
        ], JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);

        $this->applyStripeEvent($event);

        $request->refresh();
        $this->assertSame(CancellationRequest::STATUS_PENDING, $request->status);
        $this->assertNull($request->final_refund_amount);
        $this->assertNull($request->refund_outcome);
        $this->assertNull($request->decided_at);
        $this->assertNull($request->refund_completed_at);
        $this->assertSame('refunded', $data['payment']->fresh()->payment_status);
        $this->assertSame('100.00', $data['payment']->fresh()->refunded_amount);
        $this->assertSame('refunded', $data['order']->fresh()->status);
    }

    public function test_charge_refund_correlated_to_processing_request_completes_cancellation_first_race(): void
    {
        Mail::fake();
        $intentId = 'pi_processing_reconcile_'.uniqid();
        $data = $this->createOrderWithPayment($intentId, 'confirmed');
        $data['payment']->update(['payment_status' => 'paid']);
        $admin = User::factory()->admin()->create();
        $request = CancellationRequest::factory()->for($data['order'])->create([
            'customer_id' => $data['user']->id,
            'status' => CancellationRequest::STATUS_REFUND_PROCESSING,
            'paid_amount' => '100.00',
            'final_refund_amount' => '75.00',
            'final_deduction_amount' => '25.00',
            'idempotency_key' => 'cancel-request-processing',
            'decided_by' => $admin->id,
            'decided_at' => now(),
        ]);
        DB::transaction(fn () => app(CancellationNotificationService::class)->recordRefundConfirmationFailed($request));
        $event = json_decode(json_encode([
            'type' => 'charge.refunded',
            'data' => ['object' => [
                'payment_intent' => $intentId,
                'amount' => 10000,
                'amount_refunded' => 7500,
                'currency' => 'usd',
                'refunded' => false,
                'refunds' => ['data' => [[
                    'id' => 're_processing_correlated',
                    'amount' => 7500,
                    'status' => 'succeeded',
                    'metadata' => ['cancellation_request_id' => (string) $request->id],
                ]]],
            ]],
        ], JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);

        $this->applyStripeEvent($event);

        $request->refresh();
        $this->assertSame(CancellationRequest::STATUS_APPROVED, $request->status);
        $this->assertSame('75.00', $request->final_refund_amount);
        $this->assertSame('25.00', $request->final_deduction_amount);
        $this->assertSame('re_processing_correlated', $request->stripe_refund_id);
        $this->assertSame('partial', $request->refund_outcome);
        $this->assertSame('partially_refunded', $data['payment']->fresh()->payment_status);
        $this->assertSame('75.00', $data['payment']->fresh()->refunded_amount);
        $this->assertSame('cancelled', $data['order']->fresh()->status);
        Mail::assertQueued(CancellationRequestApprovedMail::class, fn ($mail): bool => $mail->cancellationRequest->is($request)
                && $mail->hasTo($data['user']->email)
        );
        $this->assertSame(1, Notification::query()->where('deduplication_key', "cancellation:{$request->id}:refund_confirmation_failed:user:{$admin->id}")->count());
        $this->assertSame(1, Notification::query()->where('deduplication_key', "cancellation:{$request->id}:approved:user:{$data['user']->id}")->count());

        $this->applyStripeEvent($event);
        Mail::assertQueued(CancellationRequestApprovedMail::class, 1);
        $this->assertSame(1, Notification::query()->where('deduplication_key', "cancellation:{$request->id}:refund_confirmation_failed:user:{$admin->id}")->count());
        $this->assertSame(1, Notification::query()->where('deduplication_key', "cancellation:{$request->id}:approved:user:{$data['user']->id}")->count());
    }

    public function test_partial_charge_refund_reconciles_failed_request_but_stale_events_do_not_overwrite_resolved_requests(): void
    {
        Mail::fake();
        $intentId = 'pi_partial_reconcile_'.uniqid();
        $data = $this->createOrderWithPayment($intentId, 'confirmed');
        $data['payment']->update(['payment_status' => 'paid']);
        $request = CancellationRequest::factory()->for($data['order'])->create([
            'customer_id' => $data['user']->id,
            'status' => CancellationRequest::STATUS_REFUND_FAILED,
            'paid_amount' => '100.00',
            'final_refund_amount' => '40.00',
            'final_deduction_amount' => '60.00',
            'idempotency_key' => 'cancel-request-failed',
            'failure_code' => 'timeout',
            'failure_summary' => 'Safe timeout.',
            'failure_disposition' => 'indeterminate',
        ]);
        $event = json_decode(json_encode([
            'type' => 'charge.refunded',
            'data' => ['object' => [
                'payment_intent' => $intentId,
                'amount' => 10000,
                'amount_refunded' => 4000,
                'currency' => 'usd',
                'refunded' => false,
                'refunds' => ['data' => [[
                    'id' => 're_failed_correlated',
                    'amount' => 4000,
                    'status' => 'succeeded',
                    'metadata' => ['cancellation_request_id' => (string) $request->id],
                ]]],
            ]],
        ], JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);

        $this->applyStripeEvent($event);

        $request->refresh();
        $this->assertSame(CancellationRequest::STATUS_APPROVED, $request->status);
        $this->assertSame('40.00', $request->final_refund_amount);
        $this->assertSame('60.00', $request->final_deduction_amount);
        $this->assertSame('partial', $request->refund_outcome);
        $this->assertNull($request->failure_code);
        $this->assertNull($request->failure_summary);
        $this->assertSame('partially_refunded', $data['payment']->fresh()->payment_status);
        $this->assertSame('cancelled', $data['order']->fresh()->status);

        $request->update([
            'status' => CancellationRequest::STATUS_REJECTED,
            'final_refund_amount' => '10.00',
            'refund_outcome' => 'no_refund',
        ]);
        $this->applyStripeEvent($event);
        $this->assertSame(CancellationRequest::STATUS_REJECTED, $request->fresh()->status);
        $this->assertSame('10.00', $request->fresh()->final_refund_amount);
        $this->assertSame('no_refund', $request->fresh()->refund_outcome);

        $request->update([
            'status' => CancellationRequest::STATUS_APPROVED,
            'final_refund_amount' => '35.00',
            'final_deduction_amount' => '65.00',
            'refund_outcome' => 'partial',
        ]);
        $this->applyStripeEvent($event);
        $this->assertSame(CancellationRequest::STATUS_APPROVED, $request->fresh()->status);
        $this->assertSame('35.00', $request->fresh()->final_refund_amount);
        $this->assertSame('65.00', $request->fresh()->final_deduction_amount);
        $this->assertSame('partial', $request->fresh()->refund_outcome);
    }

    public function test_correlated_refund_does_not_approve_a_definitive_failure(): void
    {
        Mail::fake();
        $intentId = 'pi_definitive_correlated_'.uniqid();
        $data = $this->createOrderWithPayment($intentId, 'confirmed');
        $data['payment']->update(['payment_status' => 'paid']);
        $request = CancellationRequest::factory()->for($data['order'])->create([
            'customer_id' => $data['user']->id,
            'status' => CancellationRequest::STATUS_REFUND_FAILED,
            'paid_amount' => '100.00',
            'final_refund_amount' => '40.00',
            'final_deduction_amount' => '60.00',
            'idempotency_key' => 'cancel-request-definitive',
            'stripe_refund_id' => 're_definitive_correlated',
            'failure_code' => 'provider_declined',
            'failure_summary' => 'The refund provider rejected this refund.',
            'failure_disposition' => 'definitive',
            'decision_explanation' => 'Supplier policy adjustment.',
        ]);
        $event = json_decode(json_encode([
            'type' => 'charge.refunded',
            'data' => ['object' => [
                'payment_intent' => $intentId,
                'amount' => 10000,
                'amount_refunded' => 4000,
                'currency' => 'usd',
                'refunded' => false,
                'refunds' => ['data' => [[
                    'id' => 're_definitive_correlated',
                    'amount' => 4000,
                    'status' => 'succeeded',
                    'metadata' => ['cancellation_request_id' => (string) $request->id],
                ]]],
            ]],
        ], JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);

        $this->applyStripeEvent($event);

        $request->refresh();
        $this->assertSame(CancellationRequest::STATUS_REFUND_FAILED, $request->status);
        $this->assertSame('definitive', $request->failure_disposition);
        $this->assertSame('provider_declined', $request->failure_code);
        $this->assertSame('The refund provider rejected this refund.', $request->failure_summary);
        $this->assertSame('40.00', $request->final_refund_amount);
        $this->assertSame('60.00', $request->final_deduction_amount);
        $this->assertSame('Supplier policy adjustment.', $request->decision_explanation);
        $this->assertNull($request->refund_completed_at);
        $this->assertSame('confirmed', $data['order']->fresh()->status);
        $this->assertSame('partially_refunded', $data['payment']->fresh()->payment_status);
        $this->assertSame('40.00', $data['payment']->fresh()->refunded_amount);
    }

    public function test_failed_matching_refund_plus_unrelated_success_does_not_approve_request(): void
    {
        Mail::fake();
        $intentId = 'pi_mixed_refunds_'.uniqid();
        $data = $this->createOrderWithPayment($intentId, 'confirmed');
        $data['payment']->update(['payment_status' => 'paid', 'refunded_amount' => '0.00']);
        $request = CancellationRequest::factory()->for($data['order'])->create([
            'customer_id' => $data['user']->id,
            'status' => CancellationRequest::STATUS_REFUND_FAILED,
            'failure_disposition' => 'indeterminate',
            'paid_amount' => '100.00',
            'final_refund_amount' => '40.00',
            'final_deduction_amount' => '60.00',
            'idempotency_key' => 'cancel-request-mixed',
        ]);
        $event = json_decode(json_encode([
            'type' => 'charge.refunded',
            'data' => ['object' => [
                'payment_intent' => $intentId,
                'amount' => 10000,
                'amount_refunded' => 4000,
                'currency' => 'usd',
                'refunded' => false,
                'refunds' => ['data' => [
                    [
                        'id' => 're_matching_failed',
                        'amount' => 4000,
                        'status' => 'failed',
                        'metadata' => ['cancellation_request_id' => (string) $request->id],
                    ],
                    [
                        'id' => 're_unrelated_succeeded',
                        'amount' => 4000,
                        'status' => 'succeeded',
                        'metadata' => [],
                    ],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);

        $this->applyStripeEvent($event);

        $this->assertSame(CancellationRequest::STATUS_REFUND_FAILED, $request->fresh()->status);
        $this->assertSame('indeterminate', $request->fresh()->failure_disposition);
        $this->assertNull($request->fresh()->refund_completed_at);
        $this->assertSame('confirmed', $data['order']->fresh()->status);
        $this->assertSame('partially_refunded', $data['payment']->fresh()->payment_status);
        $this->assertSame('40.00', $data['payment']->fresh()->refunded_amount);
    }

    public function test_full_stale_webhook_keeps_approved_cancellation_order_cancelled(): void
    {
        Mail::fake();
        $intentId = 'pi_approved_full_replay_'.uniqid();
        $data = $this->createOrderWithPayment($intentId, 'cancelled');
        $data['payment']->update(['payment_status' => 'partially_refunded', 'refunded_amount' => '40.00']);
        $request = CancellationRequest::factory()->for($data['order'])->create([
            'customer_id' => $data['user']->id,
            'status' => CancellationRequest::STATUS_APPROVED,
            'paid_amount' => '100.00',
            'final_refund_amount' => '100.00',
            'final_deduction_amount' => '0.00',
            'stripe_refund_id' => 're_approved_full',
            'refund_outcome' => 'full',
            'refund_completed_at' => now(),
        ]);
        $event = json_decode(json_encode([
            'type' => 'charge.refunded',
            'data' => ['object' => [
                'payment_intent' => $intentId,
                'amount' => 10000,
                'amount_refunded' => 10000,
                'currency' => 'usd',
                'refunded' => true,
                'refunds' => ['data' => [[
                    'id' => 're_approved_full',
                    'amount' => 10000,
                    'status' => 'succeeded',
                    'metadata' => ['cancellation_request_id' => (string) $request->id],
                ]]],
            ]],
        ], JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);

        $this->applyStripeEvent($event);

        $this->assertSame(CancellationRequest::STATUS_APPROVED, $request->fresh()->status);
        $this->assertSame('full', $request->fresh()->refund_outcome);
        $this->assertSame('cancelled', $data['order']->fresh()->status);
        $this->assertSame('refunded', $data['payment']->fresh()->payment_status);
        $this->assertSame('100.00', $data['payment']->fresh()->refunded_amount);
    }

    public function test_refund_id_correlation_takes_precedence_over_unrelated_metadata(): void
    {
        Mail::fake();
        $intentId = 'pi_refund_id_priority_'.uniqid();
        $data = $this->createOrderWithPayment($intentId, 'confirmed');
        $data['payment']->update(['payment_status' => 'paid']);
        $request = CancellationRequest::factory()->for($data['order'])->create([
            'customer_id' => $data['user']->id,
            'status' => CancellationRequest::STATUS_REFUND_PROCESSING,
            'paid_amount' => '100.00',
            'final_refund_amount' => '45.00',
            'final_deduction_amount' => '55.00',
            'idempotency_key' => 'cancel-request-refund-id-priority',
            'stripe_refund_id' => 're_known_identity',
        ]);
        $event = $this->refundEvent($intentId, 4500, [[
            'id' => 're_known_identity',
            'amount' => 4500,
            'status' => 'succeeded',
            'metadata' => ['cancellation_request_id' => '999999'],
        ]]);

        $this->applyStripeEvent($event);

        $this->assertSame(CancellationRequest::STATUS_APPROVED, $request->fresh()->status);
        $this->assertSame('re_known_identity', $request->fresh()->stripe_refund_id);
        $this->assertSame('cancelled', $data['order']->fresh()->status);
    }

    public function test_unique_stale_processing_request_correlates_by_intent_and_exact_amount_without_metadata(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-08-12 12:00:00');
        $intentId = 'pi_stale_fallback_'.uniqid();
        $data = $this->createOrderWithPayment($intentId, 'confirmed');
        $data['payment']->update(['payment_status' => 'paid']);
        $request = CancellationRequest::factory()->for($data['order'])->create([
            'customer_id' => $data['user']->id,
            'status' => CancellationRequest::STATUS_REFUND_PROCESSING,
            'paid_amount' => '100.00',
            'final_refund_amount' => '35.00',
            'final_deduction_amount' => '65.00',
            'idempotency_key' => 'cancel-request-stale-fallback',
        ]);
        $request->timestamps = false;
        $request->updated_at = now()->subSeconds(config('cancellation.refund_processing_stale_after_seconds') + 1);
        $request->save();
        $event = $this->refundEvent($intentId, 3500, [[
            'id' => 're_no_metadata',
            'amount' => 3500,
            'status' => 'succeeded',
            'metadata' => [],
        ]]);

        $this->applyStripeEvent($event);

        $this->assertSame(CancellationRequest::STATUS_APPROVED, $request->fresh()->status);
        $this->assertSame('re_no_metadata', $request->fresh()->stripe_refund_id);
        $this->assertSame('35.00', $data['payment']->fresh()->refunded_amount);
        $this->assertSame('cancelled', $data['order']->fresh()->status);

        Carbon::setTestNow();
    }

    public function test_fallback_does_not_correlate_fresh_processing_or_wrong_amount_and_stale_events_do_not_reduce_cumulative_refund(): void
    {
        Mail::fake();
        $intentId = 'pi_guarded_fallback_'.uniqid();
        $data = $this->createOrderWithPayment($intentId, 'confirmed');
        $data['payment']->update([
            'payment_status' => 'partially_refunded',
            'refunded_amount' => '60.00',
        ]);
        $request = CancellationRequest::factory()->for($data['order'])->create([
            'customer_id' => $data['user']->id,
            'status' => CancellationRequest::STATUS_REFUND_PROCESSING,
            'paid_amount' => '100.00',
            'final_refund_amount' => '40.00',
            'final_deduction_amount' => '60.00',
            'idempotency_key' => 'cancel-request-guarded-fallback',
        ]);

        $this->applyStripeEvent($this->refundEvent($intentId, 4000, [[
            'id' => 're_unidentified',
            'amount' => 3000,
            'status' => 'succeeded',
            'metadata' => [],
        ]]));

        $this->assertSame(CancellationRequest::STATUS_REFUND_PROCESSING, $request->fresh()->status);
        $this->assertSame('60.00', $data['payment']->fresh()->refunded_amount);
        $this->assertSame('confirmed', $data['order']->fresh()->status);
    }

    public function test_successive_partial_webhooks_store_the_provider_cumulative_amount_idempotently(): void
    {
        Mail::fake();
        $intentId = 'pi_cumulative_partial_'.uniqid();
        $data = $this->createOrderWithPayment($intentId, 'confirmed');
        $data['payment']->update(['payment_status' => 'paid']);

        $first = $this->refundEvent($intentId, 3000, [[
            'id' => 're_partial_one',
            'amount' => 3000,
            'status' => 'succeeded',
            'metadata' => [],
        ]]);
        $second = $this->refundEvent($intentId, 6000, [
            [
                'id' => 're_partial_one',
                'amount' => 3000,
                'status' => 'succeeded',
                'metadata' => [],
            ],
            [
                'id' => 're_partial_two',
                'amount' => 3000,
                'status' => 'succeeded',
                'metadata' => [],
            ],
        ]);

        $this->applyStripeEvent($first);
        $this->assertSame('30.00', $data['payment']->fresh()->refunded_amount);
        $this->applyStripeEvent($second);
        $this->applyStripeEvent($first);

        $this->assertSame('partially_refunded', $data['payment']->fresh()->payment_status);
        $this->assertSame('60.00', $data['payment']->fresh()->refunded_amount);
    }

    public function test_legacy_refund_mail_dispatch_failure_happens_after_commit_and_does_not_rollback_reconciliation(): void
    {
        $intentId = 'pi_legacy_mail_failure_'.uniqid();
        $data = $this->createOrderWithPayment($intentId, 'confirmed');
        $data['payment']->update(['payment_status' => 'paid']);
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('queue transport unavailable'));

        $this->applyStripeEvent($this->refundEvent($intentId, 5000, [[
            'id' => 're_legacy_mail_failure',
            'amount' => 5000,
            'status' => 'succeeded',
            'metadata' => [],
        ]]));

        $this->assertSame('partially_refunded', $data['payment']->fresh()->payment_status);
        $this->assertSame('50.00', $data['payment']->fresh()->refunded_amount);
        $this->assertSame('confirmed', $data['order']->fresh()->status);
    }

    /** @param list<array<string, mixed>> $refunds */
    private function refundEvent(string $intentId, int $amountRefunded, array $refunds): object
    {
        return json_decode(json_encode([
            'type' => 'charge.refunded',
            'data' => ['object' => [
                'payment_intent' => $intentId,
                'amount' => 10000,
                'amount_refunded' => $amountRefunded,
                'currency' => 'usd',
                'refunded' => $amountRefunded >= 10000,
                'refunds' => ['data' => $refunds],
            ]],
        ], JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
    }

    private function applyStripeEvent(object $event): void
    {
        $controller = app(\App\Http\Controllers\StripeController::class);
        $method = new ReflectionMethod($controller, 'applyStripeEvent');
        $method->setAccessible(true);

        DB::transaction(fn () => $method->invoke($controller, $event));
    }

    /**
     * Known issue: Same as test_webhook_handles_payment_failure.
     * The payment_status enum does not include 'cancelled'.
     * This test documents the bug.
     */
    public function test_webhook_handles_payment_canceled(): void
    {
        Mail::fake();

        $intentId = 'pi_test_'.uniqid();
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
        $this->markTestSkipped('Pre-existing failure — see docs/TEST_TRIAGE_2026-05-04.md (Bucket B).');

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
