<?php

namespace Tests\Feature;

use App\Contracts\StripeRefundGateway;
use App\Exceptions\RefundGatewayException;
use App\Mail\CancellationRefundFailedAdminMail;
use App\Mail\CancellationRequestAdminMail;
use App\Mail\CancellationRequestApprovedMail;
use App\Mail\CancellationRequestReceivedMail;
use App\Mail\CancellationRequestRejectedMail;
use App\Models\Activity;
use App\Models\CancellationRequest;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use App\Services\CancellationNotificationService;
use App\Services\CancellationRefundService;
use App\Services\CancellationRequestService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class CancellationNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $admin;

    private NotificationStripeRefundGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-12 09:00:00');
        config([
            'app.timezone' => 'UTC',
            'mail.support_address' => 'support@example.test',
        ]);
        Mail::fake();

        $this->customer = User::factory()->customer()->create(['email' => 'customer@example.test']);
        $this->admin = User::factory()->admin()->create();
        $this->gateway = new NotificationStripeRefundGateway;
        $this->app->instance(StripeRefundGateway::class, $this->gateway);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_creation_queues_customer_acknowledgement_and_admin_alert(): void
    {
        $order = $this->paidOrder();
        $this->admin->update(['email' => 'admin@example.test']);
        $superAdmin = User::factory()->superAdmin()->create([
            'email' => 'super-admin@example.test',
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/userorders/{$order->id}/cancellation-requests", [
                'reason' => 'Our travel dates have changed.',
            ])
            ->assertCreated();

        $request = CancellationRequest::query()->sole();
        Mail::assertQueued(CancellationRequestReceivedMail::class, function ($mail) use ($request): bool {
            return $mail instanceof ShouldQueue
                && $mail->cancellationRequest->is($request)
                && $mail->hasTo('customer@example.test');
        });
        Mail::assertQueued(CancellationRequestAdminMail::class, function ($mail) use ($request): bool {
            return $mail instanceof ShouldQueue
                && $mail->cancellationRequest->is($request)
                && $mail->hasTo('admin@example.test');
        });
        Mail::assertQueued(CancellationRequestAdminMail::class, fn ($mail): bool => $mail->hasTo('super-admin@example.test'));
        Mail::assertQueued(CancellationRequestAdminMail::class, 2);
        $this->assertDatabaseHas('user_notifications', [
            'deduplication_key' => "cancellation:{$request->id}:requested:user:{$this->customer->id}",
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'deduplication_key' => "cancellation:{$request->id}:requested:user:{$this->admin->id}",
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'deduplication_key' => "cancellation:{$request->id}:requested:user:{$superAdmin->id}",
        ]);
    }

    public function test_record_requested_creates_safe_in_app_notifications_for_customer_and_active_staff_only(): void
    {
        [$request, $order] = $this->cancellation();
        $superAdmin = User::factory()->superAdmin()->create([
            'email' => 'super-admin@example.test',
            'status' => User::STATUS_ACTIVE,
        ]);
        $inactiveAdmin = User::factory()->admin()->create([
            'email' => 'inactive-admin@example.test',
            'status' => User::STATUS_INACTIVE,
        ]);
        $emailLessAdmin = User::factory()->admin()->create([
            'email' => '   ',
            'status' => User::STATUS_ACTIVE,
        ]);
        $unrelatedCustomer = User::factory()->customer()->create([
            'email' => 'unrelated@example.test',
            'status' => User::STATUS_ACTIVE,
        ]);

        DB::transaction(function () use ($request): void {
            $service = app(CancellationNotificationService::class);
            $service->recordRequested($request);
            $service->recordRequested($request);
        });

        $this->assertSame(3, Notification::query()->count());
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->customer->id,
            'type' => 'custom',
            'deduplication_key' => "cancellation:{$request->id}:requested:user:{$this->customer->id}",
            'title' => 'Cancellation request received',
            'message' => "Your cancellation request for booking #{$order->id} was sent to customer care and is awaiting review.",
            'action_url' => "/dashboard/customer?order={$order->id}",
            'display_style' => 'inline',
        ]);

        foreach ([$this->admin, $superAdmin] as $staff) {
            $notification = Notification::query()->where('user_id', $staff->id)->sole();

            $this->assertSame("cancellation:{$request->id}:requested:user:{$staff->id}", $notification->deduplication_key);
            $this->assertSame('custom', $notification->type);
            $this->assertSame('Cancellation request needs review', $notification->title);
            $this->assertSame(
                "Cancellation request #{$request->id} for booking #{$order->id} needs review.",
                $notification->message,
            );
            $this->assertSame("/dashboard/admin/orders?order={$order->id}", $notification->action_url);
            $this->assertSame('inline', $notification->display_style);
        }

        foreach (Notification::query()->get() as $notification) {
            $this->assertSame([
                'event' => 'requested',
                'order_id' => $order->id,
                'cancellation_request_id' => $request->id,
                'safe_status' => 'awaiting_review',
            ], $notification->data);
        }

        $this->assertDatabaseMissing('user_notifications', ['user_id' => $inactiveAdmin->id]);
        $this->assertDatabaseMissing('user_notifications', ['user_id' => $emailLessAdmin->id]);
        $this->assertDatabaseMissing('user_notifications', ['user_id' => $unrelatedCustomer->id]);
    }

    public function test_record_requested_keeps_per_user_rows_but_queues_one_staff_email_per_normalized_address(): void
    {
        [$request] = $this->cancellation();
        $this->admin->update(['email' => 'shared-staff@example.test']);
        User::factory()->superAdmin()->create([
            'email' => 'SHARED-STAFF@example.test',
            'status' => User::STATUS_ACTIVE,
        ]);

        DB::transaction(fn () => app(CancellationNotificationService::class)->recordRequested($request));

        $this->assertSame(3, Notification::query()->count());
        Mail::assertQueued(CancellationRequestReceivedMail::class, 1);
        Mail::assertQueued(CancellationRequestAdminMail::class, 1);
        Mail::assertQueued(CancellationRequestAdminMail::class, fn ($mail): bool => $mail->hasTo('shared-staff@example.test'));
    }

    public function test_record_requested_queues_only_customer_acknowledgement_when_customer_and_staff_share_an_address(): void
    {
        [$request] = $this->cancellation();
        $this->customer->update(['email' => 'shared-recipient@example.test']);
        $this->admin->update(['email' => ' SHARED-RECIPIENT@example.test ']);

        DB::transaction(fn () => app(CancellationNotificationService::class)->recordRequested($request));

        $this->assertSame(2, Notification::query()->count());
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->customer->id,
            'deduplication_key' => "cancellation:{$request->id}:requested:user:{$this->customer->id}",
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->admin->id,
            'deduplication_key' => "cancellation:{$request->id}:requested:user:{$this->admin->id}",
        ]);
        Mail::assertQueued(CancellationRequestReceivedMail::class, 1);
        Mail::assertNotQueued(CancellationRequestAdminMail::class);
    }

    public function test_record_requested_queue_failure_logs_only_allowlisted_context(): void
    {
        [$request, $order] = $this->cancellation();
        $this->admin->update(['status' => User::STATUS_INACTIVE]);
        Mail::shouldReceive('to')
            ->once()
            ->with('customer@example.test')
            ->andThrow(new RuntimeException('provider detail must not be logged'));
        Log::shouldReceive('warning')->once()->with(
            'Cancellation notification mail queue failed.',
            [
                'cancellation_request_id' => $request->id,
                'order_id' => $order->id,
                'event' => 'requested',
                'recipient_user_id' => $this->customer->id,
                'exception_class' => RuntimeException::class,
            ],
        );

        DB::transaction(fn () => app(CancellationNotificationService::class)->recordRequested($request));

        $this->assertSame(1, Notification::query()->count());
    }

    public function test_record_requested_mailable_construction_failure_is_contained_after_commit(): void
    {
        [$request] = $this->cancellation();
        $this->admin->update(['status' => User::STATUS_INACTIVE]);
        $service = new class extends CancellationNotificationService
        {
            protected function makeRequestedCustomerMail(CancellationRequest $request): Mailable
            {
                throw new RuntimeException('relationship query failed');
            }
        };
        Log::shouldReceive('warning')->once()->with(
            'Cancellation notification mail queue failed.',
            [
                'cancellation_request_id' => $request->id,
                'order_id' => $request->order_id,
                'event' => 'requested',
                'recipient_user_id' => $this->customer->id,
                'exception_class' => RuntimeException::class,
            ],
        );

        DB::transaction(fn () => $service->recordRequested($request));

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->customer->id,
            'deduplication_key' => "cancellation:{$request->id}:requested:user:{$this->customer->id}",
        ]);
    }

    public function test_record_requested_replay_adds_new_staff_rows_without_reemailing_a_customer_owned_shared_address(): void
    {
        [$request] = $this->cancellation();
        $this->customer->update(['email' => 'shared-replay@example.test']);
        $this->admin->update([
            'email' => ' SHARED-REPLAY@example.test ',
            'status' => User::STATUS_INACTIVE,
        ]);
        $service = app(CancellationNotificationService::class);

        DB::transaction(fn () => $service->recordRequested($request));

        Mail::assertQueued(CancellationRequestReceivedMail::class, 1);
        Mail::assertNotQueued(CancellationRequestAdminMail::class);
        Mail::fake();

        $this->admin->update(['status' => User::STATUS_ACTIVE]);
        $newSuperAdmin = User::factory()->superAdmin()->create([
            'email' => 'new-staff@example.test',
            'status' => User::STATUS_ACTIVE,
        ]);
        DB::transaction(fn () => $service->recordRequested($request));

        $this->assertSame(3, Notification::query()->count());
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->admin->id,
            'deduplication_key' => "cancellation:{$request->id}:requested:user:{$this->admin->id}",
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $newSuperAdmin->id,
            'deduplication_key' => "cancellation:{$request->id}:requested:user:{$newSuperAdmin->id}",
        ]);
        Mail::assertNotQueued(CancellationRequestReceivedMail::class);
        Mail::assertQueued(CancellationRequestAdminMail::class, 1);
        Mail::assertQueued(CancellationRequestAdminMail::class, fn ($mail): bool => $mail->hasTo('new-staff@example.test'));
        Mail::assertNotQueued(CancellationRequestAdminMail::class, fn ($mail): bool => $mail->hasTo('shared-replay@example.test'));
    }

    public function test_rejection_queues_customer_decision_with_customer_facing_explanation(): void
    {
        [$request] = $this->cancellation();
        $superAdmin = User::factory()->superAdmin()->create(['email' => 'decision-staff@example.test']);
        $unrelatedCustomer = User::factory()->customer()->create(['email' => 'unrelated-decision@example.test']);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$request->id}/reject", [
                'explanation' => 'The supplier has already committed the booking cost.',
            ])
            ->assertOk();

        Mail::assertQueued(CancellationRequestRejectedMail::class, function ($mail): bool {
            $rendered = $mail->render();

            return $mail->hasTo('customer@example.test')
                && str_contains($rendered, 'The supplier has already committed the booking cost.');
        });
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->customer->id,
            'deduplication_key' => "cancellation:{$request->id}:rejected:user:{$this->customer->id}",
        ]);
        foreach ([$this->admin, $superAdmin, $unrelatedCustomer] as $nonRecipient) {
            $this->assertDatabaseMissing('user_notifications', [
                'user_id' => $nonRecipient->id,
                'data->event' => 'rejected',
            ]);
        }
        Mail::assertNotQueued(CancellationRequestRejectedMail::class, fn ($mail): bool => $mail->hasTo($this->admin->email));
        Mail::assertNotQueued(CancellationRequestRejectedMail::class, fn ($mail): bool => $mail->hasTo('decision-staff@example.test'));
        Mail::assertNotQueued(CancellationRequestRejectedMail::class, fn ($mail): bool => $mail->hasTo('unrelated-decision@example.test'));
    }

    public function test_successful_approval_queues_customer_mail_using_stored_final_amounts(): void
    {
        [$request] = $this->cancellation();
        $superAdmin = User::factory()->superAdmin()->create(['email' => 'approval-staff@example.test']);
        $unrelatedCustomer = User::factory()->customer()->create(['email' => 'unrelated-approval@example.test']);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$request->id}/approve", [
                'final_refund' => '70.00',
                'explanation' => 'A supplier fee was retained.',
            ])
            ->assertOk();

        Mail::assertQueued(CancellationRequestApprovedMail::class, function ($mail): bool {
            $rendered = $mail->render();

            return $mail->hasTo('customer@example.test')
                && str_contains($rendered, 'USD 70.00')
                && str_contains($rendered, 'USD 30.00')
                && str_contains($rendered, 'partial');
        });
        Mail::assertNotQueued(CancellationRefundFailedAdminMail::class);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->customer->id,
            'deduplication_key' => "cancellation:{$request->id}:approved:user:{$this->customer->id}",
        ]);
        foreach ([$this->admin, $superAdmin, $unrelatedCustomer] as $nonRecipient) {
            $this->assertDatabaseMissing('user_notifications', [
                'user_id' => $nonRecipient->id,
                'data->event' => 'approved',
            ]);
        }
        Mail::assertNotQueued(CancellationRequestApprovedMail::class, fn ($mail): bool => $mail->hasTo($this->admin->email));
        Mail::assertNotQueued(CancellationRequestApprovedMail::class, fn ($mail): bool => $mail->hasTo('approval-staff@example.test'));
        Mail::assertNotQueued(CancellationRequestApprovedMail::class, fn ($mail): bool => $mail->hasTo('unrelated-approval@example.test'));
    }

    public function test_refund_service_requires_the_notification_boundary_dependency(): void
    {
        $parameter = (new \ReflectionMethod(CancellationRefundService::class, '__construct'))
            ->getParameters()[1];

        $this->assertFalse($parameter->allowsNull());
        $this->assertFalse($parameter->isDefaultValueAvailable());
        $this->assertSame(CancellationNotificationService::class, (string) $parameter->getType());
    }

    public function test_refund_call_failure_alerts_active_staff_with_safe_details_and_sends_no_approval(): void
    {
        [$request, $order] = $this->cancellation();
        $superAdmin = User::factory()->superAdmin()->create([
            'email' => 'refund-super-admin@example.test',
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->gateway->failure = RefundGatewayException::definitive(
            'card/declined',
            'secret provider diagnostics sk_test_should_not_leak',
        );

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$request->id}/approve", [
                'final_refund' => '90.00',
            ])
            ->assertStatus(502);

        $this->assertSame(CancellationRequest::STATUS_REFUND_FAILED, $request->fresh()->status);
        Mail::assertQueued(CancellationRefundFailedAdminMail::class, function ($mail) use ($request, $order): bool {
            $rendered = $mail->render();

            return $mail->hasTo($this->admin->email)
                && str_contains($rendered, "#{$request->id}")
                && str_contains($rendered, "#{$order->id}")
                && str_contains($rendered, 'The refund provider rejected this refund.')
                && ! str_contains($rendered, 'sk_test_should_not_leak');
        });
        Mail::assertQueued(CancellationRefundFailedAdminMail::class, fn ($mail): bool => $mail->hasTo('refund-super-admin@example.test'));
        Mail::assertQueued(CancellationRefundFailedAdminMail::class, 2);
        Mail::assertNotQueued(CancellationRequestApprovedMail::class);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->admin->id,
            'deduplication_key' => "cancellation:{$request->id}:refund_failed:user:{$this->admin->id}",
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $superAdmin->id,
            'deduplication_key' => "cancellation:{$request->id}:refund_failed:user:{$superAdmin->id}",
        ]);
        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $this->customer->id,
            'data->event' => 'refund_failed',
        ]);
    }

    public function test_balance_lookup_failure_persists_one_staff_failure_lifecycle_and_retry_does_not_repeat_it(): void
    {
        [$request] = $this->cancellation();
        $this->gateway->refundedFailure = RefundGatewayException::definitive('balance_unavailable', 'raw lookup detail');

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$request->id}/approve", ['final_refund' => '90.00'])
            ->assertStatus(502);

        $this->assertSame(CancellationRequest::STATUS_REFUND_FAILED, $request->fresh()->status);
        $this->assertSame(1, Notification::query()->where('deduplication_key', "cancellation:{$request->id}:refund_failed:user:{$this->admin->id}")->count());
        Mail::assertQueued(CancellationRefundFailedAdminMail::class, 1);
        Mail::assertNotQueued(CancellationRequestApprovedMail::class);

        $this->gateway->refundedFailure = null;
        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$request->id}/retry")
            ->assertOk();

        $this->assertSame(1, Notification::query()->where('deduplication_key', "cancellation:{$request->id}:refund_failed:user:{$this->admin->id}")->count());
        Mail::assertQueued(CancellationRefundFailedAdminMail::class, 1);
        Mail::assertQueued(CancellationRequestApprovedMail::class, 1);
    }

    public function test_post_refund_confirmation_failure_records_danger_once_without_customer_failure_or_early_approval(): void
    {
        [$request] = $this->cancellation();
        $this->gateway->refundedFailureOnCheck = 2;
        $this->gateway->refundedFailure = RefundGatewayException::indeterminate('confirmation_timeout', 'raw confirmation detail');

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$request->id}/approve", ['final_refund' => '90.00'])
            ->assertStatus(502);

        $this->assertSame(CancellationRequest::STATUS_REFUND_PROCESSING, $request->fresh()->status);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->admin->id,
            'deduplication_key' => "cancellation:{$request->id}:refund_confirmation_failed:user:{$this->admin->id}",
        ]);
        $notification = Notification::query()
            ->where('deduplication_key', "cancellation:{$request->id}:refund_confirmation_failed:user:{$this->admin->id}")
            ->sole();
        $this->assertSame([
            'event' => 'refund_confirmation_failed',
            'order_id' => $request->order_id,
            'cancellation_request_id' => $request->id,
            'safe_status' => 'refund_processing',
        ], $notification->data);
        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $this->customer->id,
            'data->event' => 'refund_confirmation_failed',
        ]);
        Mail::assertQueued(CancellationRefundFailedAdminMail::class, 1);
        Mail::assertNotQueued(CancellationRequestApprovedMail::class);

        app(CancellationRefundService::class)->reportLocalFailure($request->id);
        $this->assertSame(1, Notification::query()->where('deduplication_key', "cancellation:{$request->id}:refund_confirmation_failed:user:{$this->admin->id}")->count());
        Mail::assertQueued(CancellationRefundFailedAdminMail::class, 1);
    }

    public function test_all_cancellation_mailables_are_queued_mailables(): void
    {
        foreach ([
            CancellationRequestReceivedMail::class,
            CancellationRequestAdminMail::class,
            CancellationRequestRejectedMail::class,
            CancellationRequestApprovedMail::class,
            CancellationRefundFailedAdminMail::class,
        ] as $mailable) {
            $this->assertContains(ShouldQueue::class, class_implements($mailable));
        }
    }

    public function test_cancellation_mailables_render_safe_booking_context_and_role_specific_deep_links(): void
    {
        config([
            'app.frontend_url' => 'https://portal.weelp.example/',
            'app.frontend_fallback_url' => 'https://weelp.netlify.app',
        ]);
        $this->customer->update([
            'name' => 'Customer <script>alert("customer")</script>',
        ]);
        [$request, $order] = $this->cancellation();
        $order->update([
            'item_snapshot_json' => json_encode([
                'name' => 'Desert <script>alert("item")</script> Safari',
            ], JSON_THROW_ON_ERROR),
            'travel_date' => '2030-01-01',
            'preferred_time' => '23:59:00',
        ]);
        $request->update([
            'travel_starts_at' => '2026-09-20 10:00:00',
            'decision_explanation' => 'Supplier retained USD 30.00 <reviewed>',
            'final_refund_amount' => '70.00',
            'final_deduction_amount' => '30.00',
            'refund_outcome' => 'partial',
            'failure_code' => 'provider_declined',
            'failure_summary' => 'The refund provider rejected this refund.',
            'stripe_refund_id' => 're_provider_sentinel_must_not_render',
        ]);
        $request = $request->fresh(['order.payment', 'customer']);

        $customerUrl = "https://portal.weelp.example/dashboard/customer?order={$order->id}";
        $adminUrl = "https://portal.weelp.example/dashboard/admin/orders?order={$order->id}";
        $received = (new CancellationRequestReceivedMail($request))->render();
        $admin = (new CancellationRequestAdminMail($request))->render();
        $approved = (new CancellationRequestApprovedMail($request))->render();
        $rejected = (new CancellationRequestRejectedMail($request))->render();
        $refundFailed = (new CancellationRefundFailedAdminMail($request))->render();

        $this->assertStringContainsString("booking #{$order->id}", $received);
        $this->assertStringContainsString('waiting for an administrator to review it', $received);
        $this->assertStringContainsString('href="'.$customerUrl.'"', $received);

        $this->assertStringContainsString("booking #{$order->id}", $admin);
        $this->assertStringContainsString('Customer &lt;script&gt;alert', $admin);
        $this->assertStringContainsString('customer', $admin);
        $this->assertStringContainsString('&lt;/script&gt;', $admin);
        $this->assertStringContainsString('Desert &lt;script&gt;alert', $admin);
        $this->assertStringContainsString('item', $admin);
        $this->assertStringContainsString('&lt;/script&gt; Safari', $admin);
        $this->assertStringContainsString('September 20, 2026', $admin);
        $this->assertStringContainsString('10:00 AM', $admin);
        $this->assertStringNotContainsString('2030-01-01', $admin);
        $this->assertStringNotContainsString('23:59', $admin);
        $this->assertStringContainsString('href="'.$adminUrl.'"', $admin);

        $this->assertStringContainsString('USD 70.00', $approved);
        $this->assertStringContainsString('USD 30.00', $approved);
        $this->assertStringContainsString('Supplier retained USD 30.00 &lt;reviewed&gt;', $approved);
        $this->assertStringContainsString('href="'.$customerUrl.'"', $approved);

        $this->assertStringContainsString('Supplier retained USD 30.00 &lt;reviewed&gt;', $rejected);
        $this->assertStringContainsString('href="'.$customerUrl.'"', $rejected);

        $this->assertStringContainsString("booking #{$order->id}", $refundFailed);
        $this->assertStringContainsString('The refund provider rejected this refund.', $refundFailed);
        $this->assertStringContainsString('href="'.$adminUrl.'"', $refundFailed);

        foreach ([$received, $admin, $approved, $rejected, $refundFailed] as $html) {
            $this->assertStringNotContainsString('<script>', $html);
            $this->assertStringNotContainsString('re_provider_sentinel_must_not_render', $html);
        }
    }

    public function test_cancellation_mail_links_fall_back_when_the_configured_frontend_origin_is_unsafe(): void
    {
        config([
            'app.frontend_fallback_url' => 'https://weelp.netlify.app/',
        ]);
        [$request, $order] = $this->cancellation();

        foreach ([
            'javascript:alert(1)',
            '//attacker.example',
            'https://user:secret@attacker.example',
            'https://user@attacker.example',
            'https://bad host.example',
        ] as $unsafeOrigin) {
            config()->set('app.frontend_url', $unsafeOrigin);

            $customerHtml = (new CancellationRequestReceivedMail($request->fresh()))->render();
            $adminHtml = (new CancellationRequestAdminMail($request->fresh()))->render();

            $this->assertStringContainsString(
                'href="https://weelp.netlify.app/dashboard/customer?order='.$order->id.'"',
                $customerHtml,
            );
            $this->assertStringContainsString(
                'href="https://weelp.netlify.app/dashboard/admin/orders?order='.$order->id.'"',
                $adminHtml,
            );
            $this->assertStringNotContainsString($unsafeOrigin, $customerHtml);
            $this->assertStringNotContainsString($unsafeOrigin, $adminHtml);
        }
    }

    public function test_admin_cancellation_mail_uses_booking_number_when_the_item_snapshot_has_no_name(): void
    {
        [$request, $order] = $this->cancellation();
        $order->update(['item_snapshot_json' => '{malformed-json']);

        $html = (new CancellationRequestAdminMail($request->fresh()))->render();

        $this->assertStringContainsString("Item: Booking #{$order->id}", $html);
    }

    public function test_cancellation_mailables_render_untrusted_markdown_as_literal_text(): void
    {
        config()->set('app.frontend_url', 'https://portal.weelp.example');
        $hostile = '[x](https://evil.example/link) ![tracker](https://evil.example/pixel) '
            .'<https://evil.example/auto>'."\n# injected heading";
        $this->customer->update(['name' => $hostile]);
        [$request, $order] = $this->cancellation();
        $order->update([
            'item_snapshot_json' => json_encode(['name' => $hostile], JSON_THROW_ON_ERROR),
        ]);
        $request->update([
            'reason' => $hostile,
            'decision_explanation' => $hostile,
            'failure_code' => $hostile,
            'failure_summary' => $hostile,
            'final_refund_amount' => '70.00',
            'final_deduction_amount' => '30.00',
            'refund_outcome' => 'partial',
        ]);
        $request = $request->fresh(['order', 'customer']);

        $mailables = [
            new CancellationRequestReceivedMail($request),
            new CancellationRequestAdminMail($request),
            new CancellationRequestApprovedMail($request),
            new CancellationRequestRejectedMail($request),
            new CancellationRefundFailedAdminMail($request),
        ];

        foreach ($mailables as $mailable) {
            $html = $mailable->render();

            $this->assertStringContainsString('[x](https://evil.example/link)', $html);
            $this->assertStringContainsString('![tracker](https://evil.example/pixel)', $html);
            $this->assertStringContainsString('&lt;https://evil.example/auto&gt;', $html);
            $this->assertStringContainsString('# injected heading', $html);
            $this->assertStringNotContainsString('href="https://evil.example', $html);
            $this->assertStringNotContainsString('src="https://evil.example', $html);
            $this->assertStringNotContainsString('<h1>injected heading</h1>', $html);
            $this->assertStringContainsString('href="https://portal.weelp.example/', $html);
        }
    }

    public function test_cancellation_mailables_do_not_eager_load_unused_payment_data(): void
    {
        [$request] = $this->cancellation();

        foreach ([
            CancellationRequestReceivedMail::class,
            CancellationRequestAdminMail::class,
            CancellationRequestApprovedMail::class,
            CancellationRequestRejectedMail::class,
            CancellationRefundFailedAdminMail::class,
        ] as $mailableClass) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $mailable = new $mailableClass($request->fresh());
            $queries = DB::getQueryLog();
            DB::disableQueryLog();

            $this->assertTrue($mailable->cancellationRequest->relationLoaded('order'));
            $this->assertTrue($mailable->cancellationRequest->relationLoaded('customer'));
            $this->assertFalse($mailable->cancellationRequest->order->relationLoaded('payment'));
            $this->assertFalse(collect($queries)->contains(
                fn (array $query): bool => str_contains($query['query'], 'order_payments'),
            ));
        }
    }

    public function test_frontend_config_has_no_obsolete_default_and_null_uses_the_canonical_fallback(): void
    {
        $configSource = file_get_contents(config_path('app.php'));
        $this->assertIsString($configSource);
        $this->assertStringNotContainsString('weelp-frontend.vercel.app', $configSource);

        config([
            'app.frontend_url' => null,
            'app.frontend_fallback_url' => 'https://weelp.netlify.app',
        ]);
        [$request, $order] = $this->cancellation();
        $html = (new CancellationRequestReceivedMail($request))->render();

        $this->assertStringContainsString(
            'href="https://weelp.netlify.app/dashboard/customer?order='.$order->id.'"',
            $html,
        );
    }

    public function test_notifications_wait_for_the_outermost_commit_and_are_suppressed_on_rollback(): void
    {
        $order = $this->paidOrder();
        DB::beginTransaction();
        $rolledBack = app(CancellationRequestService::class)->create($order->id, $this->customer->id, 'Our dates changed.');
        Mail::assertNothingQueued();
        $this->assertDatabaseHas('user_notifications', [
            'deduplication_key' => "cancellation:{$rolledBack->id}:requested:user:{$this->customer->id}",
        ]);
        DB::rollBack();
        Mail::assertNothingQueued();
        $this->assertDatabaseMissing('cancellation_requests', ['id' => $rolledBack->id]);
        $this->assertDatabaseMissing('user_notifications', [
            'deduplication_key' => "cancellation:{$rolledBack->id}:requested:user:{$this->customer->id}",
        ]);

        $order = $this->paidOrder();
        DB::beginTransaction();
        $created = app(CancellationRequestService::class)->create($order->id, $this->customer->id, 'Our dates changed.');
        Mail::assertNothingQueued();
        DB::commit();
        Mail::assertQueued(CancellationRequestReceivedMail::class, 1);
        Mail::assertQueued(CancellationRequestAdminMail::class, 1);

        Mail::fake();
        DB::beginTransaction();
        app(CancellationRefundService::class)->reject($created->id, $this->admin, 'The cost is committed.');
        Mail::assertNothingQueued();
        DB::commit();
        Mail::assertQueued(CancellationRequestRejectedMail::class, 1);

        Mail::fake();
        $this->gateway = new NotificationStripeRefundGateway;
        $this->app->instance(StripeRefundGateway::class, $this->gateway);
        [$approval] = $this->cancellation();
        DB::beginTransaction();
        app(CancellationRefundService::class)->approve($approval->id, $this->admin, '90.00', null);
        Mail::assertNothingQueued();
        DB::commit();
        Mail::assertQueued(CancellationRequestApprovedMail::class, 1);

        Mail::fake();
        $this->gateway = new NotificationStripeRefundGateway;
        $this->app->instance(StripeRefundGateway::class, $this->gateway);
        [$failure] = $this->cancellation();
        $this->gateway->failure = RefundGatewayException::definitive('declined', 'raw provider detail');
        DB::beginTransaction();
        try {
            app(CancellationRefundService::class)->approve($failure->id, $this->admin, '90.00', null);
        } catch (RefundGatewayException) {
            // The surrounding caller owns the transaction and chooses to commit the failure state.
        }
        Mail::assertNothingQueued();
        DB::commit();
        Mail::assertQueued(CancellationRefundFailedAdminMail::class, 1);
    }

    public function test_notification_dispatch_failure_cannot_roll_back_an_approved_refund(): void
    {
        [$request, $order] = $this->cancellation();
        Log::spy();
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('queue unavailable'));

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$request->id}/approve", [
                'final_refund' => '90.00',
            ])
            ->assertOk();

        $this->assertSame(CancellationRequest::STATUS_APPROVED, $request->fresh()->status);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->customer->id,
            'deduplication_key' => "cancellation:{$request->id}:approved:user:{$this->customer->id}",
        ]);
        Log::shouldHaveReceived('warning')->once()->with(
            'Cancellation notification mail queue failed.',
            [
                'cancellation_request_id' => $request->id,
                'order_id' => $order->id,
                'event' => 'approved',
                'recipient_user_id' => $this->customer->id,
                'exception_class' => RuntimeException::class,
            ],
        );
    }

    public function test_provider_logs_never_include_raw_provider_details_or_exception_payloads(): void
    {
        [$request] = $this->cancellation();
        $sentinel = 'sk_test_raw_provider_secret';
        $this->gateway->failure = RefundGatewayException::definitive('card/declined', $sentinel);
        Log::shouldReceive('warning')->once()->with(
            'Cancellation refund provider failure.',
            \Mockery::on(fn (array $context): bool => ! str_contains(json_encode($context), $sentinel)
                && ! array_key_exists('exception', $context)
                && ! array_key_exists('provider_message', $context)),
        );

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/cancellation-requests/{$request->id}/approve", [
                'final_refund' => '90.00',
            ])
            ->assertStatus(502);
    }

    private function paidOrder(): Order
    {
        $activity = Activity::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'orderable_type' => $activity->getMorphClass(),
            'orderable_id' => $activity->id,
            'status' => 'confirmed',
            'travel_date' => '2026-09-20',
            'preferred_time' => '10:00:00',
        ]);
        OrderPayment::factory()->for($order)->create([
            'payment_status' => 'paid',
            'payment_intent_id' => 'pi_notification_'.$order->id,
            'total_amount' => '100.00',
            'refunded_amount' => '0.00',
            'currency' => 'USD',
        ]);

        return $order;
    }

    /** @return array{CancellationRequest, Order} */
    private function cancellation(): array
    {
        $order = $this->paidOrder();
        $request = CancellationRequest::factory()->for($order)->create([
            'customer_id' => $this->customer->id,
            'paid_amount' => '100.00',
            'currency' => 'USD',
            'suggested_refund_amount' => '90.00',
            'suggested_deduction_amount' => '10.00',
        ]);

        return [$request, $order];
    }
}

class NotificationStripeRefundGateway implements StripeRefundGateway
{
    public ?RefundGatewayException $failure = null;

    public ?RefundGatewayException $refundedFailure = null;

    public ?int $refundedFailureOnCheck = null;

    private int $refundedChecks = 0;

    private int $refunded = 0;

    public function refundedAmount(string $paymentIntentId): int
    {
        $this->refundedChecks++;
        if ($this->refundedFailure
            && ($this->refundedFailureOnCheck === null || $this->refundedFailureOnCheck === $this->refundedChecks)) {
            throw $this->refundedFailure;
        }

        return $this->refunded;
    }

    public function refund(
        string $paymentIntentId,
        int $amountInMinorUnits,
        string $idempotencyKey,
        array $metadata,
    ): object {
        if ($this->failure) {
            throw $this->failure;
        }

        $this->refunded += $amountInMinorUnits;

        return (object) [
            'id' => 're_notification',
            'amount' => $amountInMinorUnits,
            'status' => 'succeeded',
        ];
    }
}
