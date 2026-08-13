<?php

namespace Tests\Feature;

use App\Models\CancellationRequest;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CancellationPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancellation_schema_persists_lifecycle_and_money_snapshots(): void
    {
        $this->assertTrue(Schema::hasColumns('cancellation_requests', [
            'order_id',
            'customer_id',
            'status',
            'reason',
            'requested_at',
            'policy_version',
            'policy_snapshot',
            'travel_starts_at',
            'seconds_remaining',
            'paid_amount',
            'currency',
            'suggested_deduction_percentage',
            'suggested_deduction_amount',
            'suggested_refund_amount',
            'final_refund_amount',
            'final_deduction_amount',
            'decision_explanation',
            'decided_by',
            'decided_at',
            'stripe_refund_id',
            'idempotency_key',
            'failure_code',
            'failure_summary',
            'failure_disposition',
            'refund_outcome',
            'refund_completed_at',
        ]));

        $order = Order::factory()->create();
        $customer = User::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $requestedAt = Carbon::parse('2026-08-12 09:00:00');

        $request = CancellationRequest::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'decided_by' => $admin->id,
            'status' => CancellationRequest::STATUS_APPROVED,
            'requested_at' => $requestedAt,
            'policy_snapshot' => ['version' => 'general-v1'],
            'paid_amount' => '100.00',
            'suggested_deduction_percentage' => '25.00',
            'suggested_deduction_amount' => '25.00',
            'suggested_refund_amount' => '75.00',
            'final_refund_amount' => '70.00',
            'final_deduction_amount' => '30.00',
            'decided_at' => $requestedAt->copy()->addHour(),
        ])->fresh();

        $this->assertTrue($request->order->is($order));
        $this->assertTrue($request->customer->is($customer));
        $this->assertTrue($request->decidingAdmin->is($admin));
        $this->assertSame(['version' => 'general-v1'], $request->policy_snapshot);
        $this->assertTrue($request->requested_at->equalTo($requestedAt));
        $this->assertSame('100.00', $request->paid_amount);
        $this->assertSame('25.00', $request->suggested_deduction_percentage);
        $this->assertSame('75.00', $request->suggested_refund_amount);
    }

    public function test_order_exposes_all_and_latest_cancellation_requests(): void
    {
        $order = Order::factory()->create();
        $olderId = CancellationRequest::factory()->for($order)->create([
            'requested_at' => '2026-08-13 09:00:00',
        ]);
        $latestId = CancellationRequest::factory()->for($order)->create([
            'requested_at' => '2026-08-11 09:00:00',
        ]);

        $this->assertCount(2, $order->cancellationRequests);
        $this->assertTrue($order->latestCancellationRequest->is($latestId));
        $this->assertFalse($order->latestCancellationRequest->is($olderId));
    }

    public function test_factory_customer_owns_the_factory_order_by_default(): void
    {
        $request = CancellationRequest::factory()->create();

        $this->assertSame($request->order->user_id, $request->customer_id);
    }

    public function test_stripe_refund_identifiers_are_unique_when_present_and_allow_multiple_nulls(): void
    {
        CancellationRequest::factory()->count(2)->create([
            'stripe_refund_id' => null,
            'idempotency_key' => null,
        ]);

        CancellationRequest::factory()->create([
            'stripe_refund_id' => 're_unique',
            'idempotency_key' => 'cancel-unique',
        ]);

        $this->expectException(QueryException::class);

        CancellationRequest::factory()->create([
            'stripe_refund_id' => 're_unique',
            'idempotency_key' => 'cancel-other',
        ]);
    }

    public function test_idempotency_keys_are_unique_when_present(): void
    {
        CancellationRequest::factory()->create([
            'stripe_refund_id' => 're_first',
            'idempotency_key' => 'cancel-unique',
        ]);

        $this->expectException(QueryException::class);

        CancellationRequest::factory()->create([
            'stripe_refund_id' => 're_second',
            'idempotency_key' => 'cancel-unique',
        ]);
    }

    public function test_order_payment_tracks_refunds_and_accepts_partially_refunded_status(): void
    {
        $payment = OrderPayment::factory()->create([
            'payment_status' => 'partially_refunded',
            'amount' => '80.10',
            'custom_amount' => '90.20',
            'total_amount' => '100.30',
            'refunded_amount' => '40.25',
        ])->fresh();

        $this->assertSame('partially_refunded', $payment->payment_status);
        $this->assertSame('80.10', $payment->amount);
        $this->assertSame('90.20', $payment->custom_amount);
        $this->assertSame('100.30', $payment->total_amount);
        $this->assertSame('40.25', $payment->refunded_amount);
        $this->assertSame($payment->order_id, $payment->order->id);

        $defaultPayment = OrderPayment::factory()->create()->fresh();
        $this->assertSame('0.00', $defaultPayment->refunded_amount);
    }

    public function test_refund_tracking_rollback_normalizes_partial_refunds_before_narrowing_statuses(): void
    {
        $payment = OrderPayment::factory()->create([
            'payment_status' => 'partially_refunded',
            'refunded_amount' => '40.25',
        ]);
        $migration = require database_path('migrations/2026_08_12_000002_add_refund_tracking_to_order_payments.php');

        $migration->down();

        $this->assertSame('paid', DB::table('order_payments')->where('id', $payment->id)->value('payment_status'));
        $this->assertFalse(Schema::hasColumn('order_payments', 'refunded_amount'));

        $migration->up();
    }

    public function test_notification_deduplication_migration_round_trips_with_nullable_unique_key(): void
    {
        $migration = require database_path('migrations/2026_08_12_000003_add_deduplication_key_to_user_notifications.php');
        $migration->down();
        $migration->up();

        $user = User::factory()->create();
        Notification::factory()->count(2)->create([
            'user_id' => $user->id,
            'deduplication_key' => null,
        ]);
        Notification::factory()->create([
            'user_id' => $user->id,
            'deduplication_key' => 'cancellation:1:requested:user:1',
        ]);

        try {
            Notification::factory()->create([
                'user_id' => $user->id,
                'deduplication_key' => 'cancellation:1:requested:user:1',
            ]);
            $this->fail('The named unique index accepted a duplicate non-null key.');
        } catch (QueryException) {
            $this->assertSame(3, Notification::query()->count());
        }

        $migration->down();

        $this->assertFalse(Schema::hasColumn('user_notifications', 'deduplication_key'));
        $indexes = collect(DB::select("PRAGMA index_list('user_notifications')"));
        $this->assertFalse($indexes->contains(fn (object $index): bool => $index->name === 'user_notifications_deduplication_key_unique'));

        $migration->up();
    }
}
