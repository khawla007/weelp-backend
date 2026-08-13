<?php

namespace Tests\Feature;

use App\Contracts\StripeRefundGateway;
use App\Models\Activity;
use App\Models\CancellationRequest;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\CancellationWorkflowSeeder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CancellationWorkflowSeederTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('unsafeConfigurationProvider')]
    public function test_safety_guard_rejects_unsafe_configuration(
        string $environment,
        bool $enabled,
        string $database,
    ): void {
        $this->expectException(LogicException::class);

        CancellationWorkflowSeeder::assertSafeConfiguration(
            $environment,
            $enabled,
            'weelp_local_cancellation',
            ['host' => '127.0.0.1', 'database' => $database, 'url' => null],
            $database,
        );
    }

    /** @return array<string, array{string, bool, string}> */
    public static function unsafeConfigurationProvider(): array
    {
        return [
            'non-local environment' => ['testing', true, 'weelp_local_cancellation'],
            'explicit switch disabled' => ['local', false, 'weelp_local_cancellation'],
            'near-prefix database' => ['local', true, 'weelp_local_cancellation_extra'],
            'case-mismatched database' => ['local', true, 'WEELP_LOCAL_CANCELLATION'],
            'production database identity' => ['local', true, 'defaultdb'],
        ];
    }

    public function test_safety_guard_accepts_only_enabled_local_database_configuration(): void
    {
        CancellationWorkflowSeeder::assertSafeConfiguration(
            'local',
            true,
            'weelp_local_cancellation',
            ['host' => 'localhost', 'database' => 'weelp_local_cancellation', 'url' => null],
            'weelp_local_cancellation',
        );

        $this->addToAssertionCount(1);
    }

    public function test_safety_guard_rejects_a_connection_url_that_could_override_local_metadata(): void
    {
        $this->expectException(LogicException::class);

        CancellationWorkflowSeeder::assertSafeConfiguration(
            'local',
            true,
            'weelp_local_cancellation',
            [
                'host' => '127.0.0.1',
                'database' => 'weelp_local_cancellation',
                'url' => 'mysql://configured-connection',
            ],
            'weelp_local_cancellation',
        );
    }

    #[DataProvider('unsafeWriteConfigurationProvider')]
    public function test_safety_guard_rejects_unsafe_write_overrides(array $write): void
    {
        $this->expectException(LogicException::class);

        CancellationWorkflowSeeder::assertSafeConfiguration(
            'local',
            true,
            'weelp_local_cancellation',
            [
                'host' => '127.0.0.1',
                'database' => 'weelp_local_cancellation',
                'url' => null,
                'write' => $write,
            ],
            'weelp_local_cancellation',
        );
    }

    /** @return array<string, array{array<string, string>}> */
    public static function unsafeWriteConfigurationProvider(): array
    {
        return [
            'remote host' => [['host' => 'weelp-mysql.d.aivencloud.com']],
            'database mismatch' => [['database' => 'defaultdb']],
            'url override' => [['url' => 'mysql://configured-connection']],
        ];
    }

    public function test_safety_guard_rejects_actual_database_identity_mismatch(): void
    {
        $this->expectException(LogicException::class);

        CancellationWorkflowSeeder::assertSafeConfiguration(
            'local',
            true,
            'weelp_local_cancellation',
            ['host' => '127.0.0.1', 'database' => 'weelp_local_cancellation', 'url' => null],
            'defaultdb',
        );
    }

    public function test_seeder_is_idempotent_and_creates_the_approved_workflow_states(): void
    {
        $this->app->instance('env', 'local');
        config()->set('cancellation.fixture_enabled', true);
        config()->set('cancellation.fixture_database', 'weelp_local_cancellation');
        config()->set('cancellation.fixture_actual_database_override', 'weelp_local_cancellation');

        // RefreshDatabase has already established the in-memory connection. These
        // values exercise the same safety metadata the seeder reads in local use.
        config()->set('database.connections.sqlite.host', '127.0.0.1');
        config()->set('database.connections.sqlite.database', 'weelp_local_cancellation');

        Mail::fake();
        Queue::fake();
        $gatewayResolved = false;
        $this->app->beforeResolving(StripeRefundGateway::class, function () use (&$gatewayResolved): void {
            $gatewayResolved = true;
        });

        $this->seed(CancellationWorkflowSeeder::class);
        $firstOrderIds = $this->fixtureOrders()->pluck('id')->all();

        $password = Hash::make('do-not-overwrite');
        $orders = $this->fixtureOrders()->get();
        $customer = $orders->firstOrFail()->user;
        $customer->forceFill([
            'password' => $password,
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_INACTIVE,
        ])->save();
        $admin = User::query()->where('email', 'khawla@fanaticcoders.com')->firstOrFail();
        $adminPassword = Hash::make('also-do-not-overwrite');
        $admin->forceFill([
            'password' => $adminPassword,
            'role' => User::ROLE_CUSTOMER,
            'status' => User::STATUS_INACTIVE,
        ])->save();
        $unrelated = Order::factory()->create();
        $orders->firstWhere(
            'special_requirements',
            CancellationWorkflowSeeder::marker('rejected'),
        )?->delete();

        $this->seed(CancellationWorkflowSeeder::class);

        $orders = $this->fixtureOrders()->get();
        $requests = CancellationRequest::query()
            ->whereIn('order_id', $orders->pluck('id'))
            ->get();

        $this->assertCount(7, $orders);
        $this->assertSame($firstOrderIds, $orders->pluck('id')->all());
        $this->assertTrue($unrelated->fresh()->exists);
        $this->assertSame($password, $customer->fresh()->password);
        $this->assertSame(User::ROLE_ADMIN, $customer->fresh()->role);
        $this->assertSame(User::STATUS_INACTIVE, $customer->fresh()->status);
        $this->assertSame($adminPassword, $admin->fresh()->password);
        $this->assertSame(User::ROLE_CUSTOMER, $admin->fresh()->role);
        $this->assertSame(User::STATUS_INACTIVE, $admin->fresh()->status);
        $this->assertCount(6, $requests);
        $this->assertSame(1, $requests->where('status', CancellationRequest::STATUS_PENDING)->count());
        $this->assertSame(1, $requests->where('status', CancellationRequest::STATUS_REJECTED)->count());
        $this->assertSame(2, $requests->where('status', CancellationRequest::STATUS_REFUND_FAILED)->count());
        $this->assertSame(2, $requests->where('status', CancellationRequest::STATUS_APPROVED)->count());
        $this->assertSame(
            ['definitive', 'indeterminate'],
            $requests->where('status', CancellationRequest::STATUS_REFUND_FAILED)
                ->pluck('failure_disposition')->sort()->values()->all(),
        );
        $this->assertSame(
            ['no_refund', 'partial'],
            $requests->where('status', CancellationRequest::STATUS_APPROVED)
                ->pluck('refund_outcome')->sort()->values()->all(),
        );

        $eligible = $orders->firstWhere('special_requirements', CancellationWorkflowSeeder::marker('eligible'));
        $this->assertNotNull($eligible);
        $this->assertNull($eligible->latestCancellationRequest);

        foreach ($orders as $order) {
            $this->assertNotNull($order->payment);
            $this->assertStringStartsWith('pi_test_weelp_cancellation_', (string) $order->payment?->payment_intent_id);
            $this->assertTrue(now()->startOfDay()->lt($order->travel_date));
            $this->assertSame($order->user_id, $order->latestCancellationRequest?->customer_id ?? $order->user_id);
        }

        $partialOrder = $orders->firstWhere(
            'special_requirements',
            CancellationWorkflowSeeder::marker('approved-partial'),
        );
        $this->assertSame('partially_refunded', $partialOrder?->payment?->payment_status);
        $this->assertSame('60.00', $partialOrder?->payment?->refunded_amount);
        $this->assertFixtureState($orders, 'eligible', 'confirmed', 'paid', '0.00', null, null);
        $this->assertFixtureState($orders, 'pending', 'confirmed', 'paid', '0.00', 'pending', null);
        $this->assertFixtureState($orders, 'rejected', 'confirmed', 'paid', '0.00', 'rejected', null);
        $this->assertFixtureState($orders, 'refund-failed-definitive', 'confirmed', 'paid', '0.00', 'refund_failed', '90.00');
        $this->assertFixtureState($orders, 'refund-failed-indeterminate', 'confirmed', 'paid', '0.00', 'refund_failed', '90.00');
        $this->assertFixtureState($orders, 'approved-zero', 'cancelled', 'paid', '0.00', 'approved', '0.00');
        $this->assertFixtureState($orders, 'approved-partial', 'cancelled', 'partially_refunded', '60.00', 'approved', '60.00');
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
        Queue::assertNothingPushed();
        $this->assertFalse($gatewayResolved);
    }

    public function test_seeder_refuses_to_overwrite_an_activity_slug_collision(): void
    {
        $this->configureSafeSeederTest();
        $activity = Activity::factory()->create([
            'slug' => 'cancellation-workflow-fixture',
            'name' => 'Unrelated activity',
        ]);

        try {
            $this->seed(CancellationWorkflowSeeder::class);
            $this->fail('Expected an activity collision to stop the seeder.');
        } catch (LogicException) {
            $this->assertSame('Unrelated activity', $activity->fresh()->name);
        }
    }

    private function configureSafeSeederTest(): void
    {
        $this->app->instance('env', 'local');
        config()->set('cancellation.fixture_enabled', true);
        config()->set('cancellation.fixture_database', 'weelp_local_cancellation');
        config()->set('cancellation.fixture_actual_database_override', 'weelp_local_cancellation');
        config()->set('database.connections.sqlite.host', '127.0.0.1');
        config()->set('database.connections.sqlite.database', 'weelp_local_cancellation');
    }

    private function assertFixtureState(
        Collection $orders,
        string $key,
        string $orderStatus,
        string $paymentStatus,
        string $refundedAmount,
        ?string $cancellationStatus,
        ?string $finalRefund,
    ): void {
        $order = $orders->firstWhere('special_requirements', CancellationWorkflowSeeder::marker($key));
        $this->assertNotNull($order);
        $this->assertSame($orderStatus, $order->status);
        $this->assertSame($paymentStatus, $order->payment?->payment_status);
        $this->assertSame($refundedAmount, $order->payment?->refunded_amount);
        $this->assertSame($cancellationStatus, $order->latestCancellationRequest?->status);
        $this->assertSame($finalRefund, $order->latestCancellationRequest?->final_refund_amount);
    }

    /** @return Builder<Order> */
    private function fixtureOrders(): Builder
    {
        return Order::query()
            ->with(['payment', 'latestCancellationRequest'])
            ->where('special_requirements', 'like', CancellationWorkflowSeeder::MARKER_PREFIX.'%')
            ->orderBy('id');
    }
}
