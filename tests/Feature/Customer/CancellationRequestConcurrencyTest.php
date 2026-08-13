<?php

namespace Tests\Feature\Customer;

use App\Contracts\StripeRefundGateway;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\StripeController;
use App\Models\Activity;
use App\Models\CancellationRequest;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use App\Services\CancellationNotificationService;
use App\Services\CancellationPolicyService;
use App\Services\CancellationRefundService;
use App\Services\CancellationRequestService;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Group;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

#[Group('lock-database')]
class CancellationRequestConcurrencyTest extends TestCase
{
    public function test_request_service_lock_harness_constructs_without_a_mysql_connection(): void
    {
        $service = $this->makeRequestService();

        $this->assertInstanceOf(CancellationRequestService::class, $service);
    }

    public function test_webhook_lock_harness_initializes_cancellation_notifications_without_a_mysql_connection(): void
    {
        $controller = $this->makeStripeController('signal', 'release');
        $property = new ReflectionProperty(StripeController::class, 'cancellationNotifications');

        $this->assertTrue($property->isInitialized($controller));
        $this->assertInstanceOf(CancellationNotificationService::class, $property->getValue($controller));
    }

    public function test_two_real_connections_create_only_one_unresolved_request(): void
    {
        $this->runLockScenario('duplicate');
    }

    public function test_concurrent_canonical_refund_writer_prevents_a_stale_cancellation_request(): void
    {
        $this->runLockScenario('refund');
    }

    public function test_webhook_waits_for_cancellation_then_reconciles_the_new_request(): void
    {
        $this->runLockScenario('cancellation_first');
    }

    public function test_admin_mutation_lock_finishes_before_refund_and_rechecks_cancellation_state(): void
    {
        $this->runAdminMutationRefundScenario('mutation_first');
    }

    public function test_refund_processing_identity_survives_a_competing_admin_delete(): void
    {
        $this->runAdminMutationRefundScenario('refund_first');
    }

    private function runAdminMutationRefundScenario(string $scenario): void
    {
        $url = getenv('CANCELLATION_LOCK_TEST_DB_URL');
        $enabled = getenv('CANCELLATION_LOCK_TEST_ENABLED');
        $disposableAck = getenv('CANCELLATION_LOCK_TEST_DISPOSABLE_ACK');

        if ($enabled !== '1'
            || $disposableAck !== 'I_ACKNOWLEDGE_THIS_DATABASE_WILL_BE_ERASED'
            || ! is_string($url)
            || $url === '') {
            $this->markTestSkipped(
                'Real lock test requires explicit shell-only CANCELLATION_LOCK_TEST_ENABLED=1 '
                .'CANCELLATION_LOCK_TEST_DISPOSABLE_ACK=I_ACKNOWLEDGE_THIS_DATABASE_WILL_BE_ERASED '
                .'and CANCELLATION_LOCK_TEST_DB_URL for a disposable MySQL database.'
            );
        }

        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Real lock test requires the pcntl PHP extension.');
        }

        config([
            'database.default' => 'cancellation_lock_test',
            'database.connections.cancellation_lock_test' => $this->disposableMysqlConnection($url),
            'app.timezone' => 'UTC',
        ]);
        DB::purge('cancellation_lock_test');
        Artisan::call('migrate:fresh', [
            '--database' => 'cancellation_lock_test',
            '--force' => true,
        ]);

        Carbon::setTestNow('2026-08-12 09:00:00');
        $customer = User::factory()->customer()->create();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
        $activity = Activity::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'orderable_type' => $activity->getMorphClass(),
            'orderable_id' => $activity->id,
            'status' => 'confirmed',
            'travel_date' => '2026-09-12',
            'preferred_time' => '09:00:00',
        ]);
        OrderPayment::factory()->for($order)->create([
            'payment_status' => 'paid',
            'payment_intent_id' => 'pi_admin_mutation_lock_test',
            'total_amount' => '100.00',
            'refunded_amount' => '0.00',
            'currency' => 'USD',
        ]);
        $cancellation = CancellationRequest::factory()->for($order)->create([
            'customer_id' => $customer->id,
            'paid_amount' => '100.00',
            'currency' => 'USD',
            'suggested_refund_amount' => '90.00',
            'suggested_deduction_amount' => '10.00',
        ]);

        $firstSignal = tempnam(sys_get_temp_dir(), 'admin-refund-first-');
        $secondSignal = tempnam(sys_get_temp_dir(), 'admin-refund-second-');
        $release = tempnam(sys_get_temp_dir(), 'admin-refund-release-');
        $adminResult = tempnam(sys_get_temp_dir(), 'admin-refund-admin-');
        $refundResult = tempnam(sys_get_temp_dir(), 'admin-refund-refund-');
        foreach ([$firstSignal, $secondSignal, $release] as $signal) {
            unlink($signal);
        }

        $children = [];

        try {
            if ($scenario === 'mutation_first') {
                $children[] = $this->forkAdminDestroy(
                    $adminResult,
                    $order->id,
                    $firstSignal,
                    $release,
                    holdAfterLock: true,
                );
                $this->waitForSignal($firstSignal, 'admin mutation order lock', $children[0]);
                $children[] = $this->forkCancellationApproval(
                    $refundResult,
                    $cancellation->id,
                    $admin->id,
                    $secondSignal,
                    $release,
                    signalBeforeApproval: true,
                );
                $this->waitForSignal($secondSignal, 'refund competing query', $children[1]);
                $this->assertWorkerIsBlocked($children[1]);
                touch($release);
            } else {
                $children[] = $this->forkCancellationApproval(
                    $refundResult,
                    $cancellation->id,
                    $admin->id,
                    $firstSignal,
                    $release,
                    holdAtProvider: true,
                );
                $this->waitForSignal($firstSignal, 'refund provider call', $children[0]);
                $children[] = $this->forkAdminDestroy(
                    $adminResult,
                    $order->id,
                    $secondSignal,
                    $release,
                    signalBeforeMutation: true,
                );
                $this->waitForSignal($secondSignal, 'admin competing mutation', $children[1]);
                $adminStatus = $this->waitForChild($children[1]);
                $this->assertSame(0, pcntl_wexitstatus($adminStatus));
                $this->assertSame('conflict', trim(file_get_contents($adminResult)));
                touch($release);
            }

            foreach ($children as $index => $child) {
                if ($scenario === 'refund_first' && $index === 1) {
                    continue;
                }
                $status = $this->waitForChild($child);
                $this->assertSame(0, pcntl_wexitstatus($status));
            }

            $this->assertSame('conflict', trim(file_get_contents($adminResult)));
            $refundOutcome = json_decode(file_get_contents($refundResult), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame('approved', $refundOutcome['status']);
            $this->assertSame(1, $refundOutcome['refund_calls']);
            $this->assertSame("cancel-request-{$cancellation->id}", $refundOutcome['idempotency_key']);

            DB::purge('cancellation_lock_test');
            $this->assertNotSoftDeleted('orders', ['id' => $order->id]);
            $this->assertDatabaseHas('cancellation_requests', [
                'id' => $cancellation->id,
                'status' => CancellationRequest::STATUS_APPROVED,
                'idempotency_key' => "cancel-request-{$cancellation->id}",
            ]);
        } finally {
            Carbon::setTestNow();
            foreach ($children as $child) {
                $status = 0;
                if (pcntl_waitpid($child, $status, WNOHANG) === 0 && function_exists('posix_kill')) {
                    posix_kill($child, SIGTERM);
                    pcntl_waitpid($child, $status);
                }
            }
            foreach ([$firstSignal, $secondSignal, $release, $adminResult, $refundResult] as $file) {
                if (is_string($file) && file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }

    private function runLockScenario(string $scenario): void
    {
        $url = getenv('CANCELLATION_LOCK_TEST_DB_URL');
        $enabled = getenv('CANCELLATION_LOCK_TEST_ENABLED');
        $disposableAck = getenv('CANCELLATION_LOCK_TEST_DISPOSABLE_ACK');

        if ($enabled !== '1'
            || $disposableAck !== 'I_ACKNOWLEDGE_THIS_DATABASE_WILL_BE_ERASED'
            || ! is_string($url)
            || $url === '') {
            $this->markTestSkipped(
                'Real lock test requires explicit shell-only CANCELLATION_LOCK_TEST_ENABLED=1 '
                .'CANCELLATION_LOCK_TEST_DISPOSABLE_ACK=I_ACKNOWLEDGE_THIS_DATABASE_WILL_BE_ERASED '
                .'and CANCELLATION_LOCK_TEST_DB_URL for a disposable MySQL database.'
            );
        }

        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Real lock test requires the pcntl PHP extension.');
        }

        $connection = $this->disposableMysqlConnection($url);
        config([
            'database.default' => 'cancellation_lock_test',
            'database.connections.cancellation_lock_test' => $connection,
            'app.timezone' => 'UTC',
        ]);
        DB::purge('cancellation_lock_test');

        Artisan::call('migrate:fresh', [
            '--database' => 'cancellation_lock_test',
            '--force' => true,
        ]);

        Carbon::setTestNow('2026-08-12 09:00:00');
        $customer = User::factory()->customer()->create();
        $activity = Activity::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'orderable_type' => $activity->getMorphClass(),
            'orderable_id' => $activity->id,
            'status' => 'confirmed',
            'travel_date' => '2026-09-12',
            'preferred_time' => '09:00:00',
        ]);
        $payment = OrderPayment::factory()->for($order)->create([
            'payment_status' => 'paid',
            'payment_intent_id' => 'pi_lock_test',
            'total_amount' => '100.00',
            'currency' => 'USD',
        ]);

        $firstLocked = tempnam(sys_get_temp_dir(), 'cancel-lock-held-');
        $secondCompeting = tempnam(sys_get_temp_dir(), 'cancel-lock-competing-');
        $releaseFirst = tempnam(sys_get_temp_dir(), 'cancel-lock-release-');
        $firstResult = tempnam(sys_get_temp_dir(), 'cancel-lock-one-');
        $secondResult = tempnam(sys_get_temp_dir(), 'cancel-lock-two-');
        foreach ([$firstLocked, $secondCompeting, $releaseFirst] as $signal) {
            unlink($signal);
        }

        $children = [];

        try {
            if ($scenario === 'refund') {
                $children[] = $this->forkWebhookRefund(
                    $firstResult,
                    $order->id,
                    $payment->payment_intent_id,
                    $firstLocked,
                    $releaseFirst,
                    holdAfterLocks: true,
                );
            } else {
                $children[] = $this->forkCreate(
                    $firstResult,
                    $order->id,
                    $customer->id,
                    afterLock: function () use ($firstLocked, $releaseFirst): void {
                        touch($firstLocked);
                        $this->waitForSignal($releaseFirst, 'release signal');
                    },
                );
            }
            $this->waitForSignal($firstLocked, 'first worker row lock', $children[0]);

            if ($scenario === 'cancellation_first') {
                $children[] = $this->forkWebhookRefund(
                    $secondResult,
                    $order->id,
                    $payment->payment_intent_id,
                    $secondCompeting,
                    $releaseFirst,
                    signalBeforeLock: true,
                );
            } else {
                $children[] = $this->forkCreate(
                    $secondResult,
                    $order->id,
                    $customer->id,
                    beforeLock: static function () use ($secondCompeting): void {
                        touch($secondCompeting);
                    },
                    expectedConflictMessage: $scenario === 'refund'
                        ? 'Order is no longer eligible for cancellation.'
                        : 'A cancellation request is already being reviewed.',
                );
            }
            $this->waitForSignal($secondCompeting, 'second worker competing query', $children[1]);

            $this->assertWorkerIsBlocked($children[1]);

            touch($releaseFirst);

            foreach ($children as $child) {
                $status = $this->waitForChild($child);
                $this->assertSame(0, pcntl_wexitstatus($status));
            }

            $outcomes = [trim(file_get_contents($firstResult)), trim(file_get_contents($secondResult))];
            sort($outcomes);
            $this->assertSame(
                match ($scenario) {
                    'refund' => ['conflict', 'refunded'],
                    'cancellation_first' => ['created', 'refunded'],
                    default => ['conflict', 'created'],
                },
                $outcomes,
            );

            DB::purge('cancellation_lock_test');
            $this->assertSame(
                $scenario === 'refund' ? 0 : 1,
                CancellationRequest::query()->where('order_id', $order->id)->count(),
            );
            $this->assertSame(
                in_array($scenario, ['refund', 'cancellation_first'], true) ? ($scenario === 'refund' ? 'refunded' : 'cancelled') : 'confirmed',
                $order->fresh()->status,
            );
            $this->assertSame(in_array($scenario, ['refund', 'cancellation_first'], true) ? 'refunded' : 'paid', $payment->fresh()->payment_status);
            $this->assertSame(in_array($scenario, ['refund', 'cancellation_first'], true) ? '100.00' : '0.00', $payment->fresh()->refunded_amount);
            if ($scenario === 'cancellation_first') {
                $this->assertSame(
                    CancellationRequest::STATUS_APPROVED,
                    CancellationRequest::query()->where('order_id', $order->id)->sole()->status,
                );
            }
        } finally {
            Carbon::setTestNow();
            foreach ($children as $child) {
                $status = 0;
                if (pcntl_waitpid($child, $status, WNOHANG) === 0 && function_exists('posix_kill')) {
                    posix_kill($child, SIGTERM);
                    pcntl_waitpid($child, $status);
                }
            }
            foreach ([$firstLocked, $secondCompeting, $releaseFirst, $firstResult, $secondResult] as $file) {
                if (is_string($file) && file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }

    private function forkWebhookRefund(
        string $result,
        int $orderId,
        string $paymentIntentId,
        string $lockedSignal,
        string $releaseSignal,
        bool $holdAfterLocks = false,
        bool $signalBeforeLock = false,
    ): int {
        $pid = pcntl_fork();
        if ($pid === -1) {
            $this->fail('Unable to fork the canonical refund writer.');
        }

        if ($pid === 0) {
            DB::purge('cancellation_lock_test');
            Mail::fake();

            try {
                DB::transaction(function () use ($paymentIntentId, $lockedSignal, $releaseSignal, $holdAfterLocks, $signalBeforeLock): void {
                    $controller = $this->makeStripeController(
                        $lockedSignal,
                        $releaseSignal,
                        $holdAfterLocks,
                        $signalBeforeLock,
                    );
                    $event = json_decode(json_encode([
                        'type' => 'charge.refunded',
                        'data' => ['object' => [
                            'payment_intent' => $paymentIntentId,
                            'amount' => 10000,
                            'amount_refunded' => 10000,
                            'currency' => 'usd',
                            'refunded' => true,
                        ]],
                    ], JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
                    $method = new ReflectionMethod($controller, 'applyStripeEvent');
                    $method->setAccessible(true);
                    $method->invoke($controller, $event);
                });
                file_put_contents($result, 'refunded');
                exit(0);
            } catch (\Throwable $exception) {
                file_put_contents($result, 'error:'.$exception::class);
                exit(1);
            }
        }

        return $pid;
    }

    private function makeStripeController(
        string $signal,
        string $release,
        bool $holdAfterLocks = false,
        bool $signalBeforeLock = false,
    ): StripeController {
        return new class(app(CancellationNotificationService::class), $signal, $release, $holdAfterLocks, $signalBeforeLock) extends StripeController
        {
            public function __construct(
                CancellationNotificationService $notifications,
                private readonly string $signal,
                private readonly string $release,
                private readonly bool $holdAfterLocks,
                private readonly bool $signalBeforeLock,
            ) {
                parent::__construct($notifications);
            }

            protected function beforeStripeEventOrderLock(int $orderId): void
            {
                if ($this->signalBeforeLock) {
                    touch($this->signal);
                }
            }

            protected function afterStripeEventPaymentLocked(Order $order, OrderPayment $payment): void
            {
                if ($this->holdAfterLocks) {
                    touch($this->signal);
                    $deadline = microtime(true) + 10;
                    while (! file_exists($this->release)) {
                        if (microtime(true) >= $deadline) {
                            throw new \RuntimeException('Timed out awaiting webhook release.');
                        }
                        usleep(10_000);
                    }
                }
            }
        };
    }

    private function forkCreate(
        string $result,
        int $orderId,
        int $customerId,
        ?\Closure $beforeLock = null,
        ?\Closure $afterLock = null,
        string $expectedConflictMessage = 'A cancellation request is already being reviewed.',
    ): int {
        $pid = pcntl_fork();
        if ($pid === -1) {
            $this->fail('Unable to fork the lock-test process.');
        }

        if ($pid === 0) {
            DB::purge('cancellation_lock_test');

            try {
                $service = $this->makeRequestService($beforeLock, $afterLock);

                $service->create(
                    $orderId,
                    $customerId,
                    'Concurrent cancellation request.',
                );
                file_put_contents($result, 'created');
                exit(0);
            } catch (DomainException $exception) {
                if ($exception->getMessage() === $expectedConflictMessage) {
                    file_put_contents($result, 'conflict');
                    exit(0);
                }

                file_put_contents($result, 'error:unexpected-domain:'.$exception->getMessage());
                exit(1);
            } catch (\Throwable $exception) {
                file_put_contents($result, 'error:'.$exception::class);
                exit(1);
            }
        }

        return $pid;
    }

    private function makeRequestService(
        ?\Closure $beforeLock = null,
        ?\Closure $afterLock = null,
    ): CancellationRequestService {
        return new class(app(CancellationPolicyService::class), app(CancellationNotificationService::class), $beforeLock, $afterLock) extends CancellationRequestService
        {
            public function __construct(
                CancellationPolicyService $policy,
                CancellationNotificationService $notifications,
                private readonly ?\Closure $beforeLockCallback,
                private readonly ?\Closure $afterLockCallback,
            ) {
                parent::__construct($policy, $notifications);
            }

            protected function beforeOrderLock(int $orderId, int $customerId): void
            {
                ($this->beforeLockCallback ?? static fn () => null)($orderId, $customerId);
            }

            protected function afterOrderLocked(Order $order): void
            {
                ($this->afterLockCallback ?? static fn () => null)($order);
            }
        };
    }

    private function forkAdminDestroy(
        string $result,
        int $orderId,
        string $signal,
        string $release,
        bool $holdAfterLock = false,
        bool $signalBeforeMutation = false,
    ): int {
        $pid = pcntl_fork();
        if ($pid === -1) {
            $this->fail('Unable to fork the admin mutation worker.');
        }

        if ($pid === 0) {
            DB::purge('cancellation_lock_test');

            try {
                if ($signalBeforeMutation) {
                    touch($signal);
                }

                $controller = new class($signal, $release, $holdAfterLock) extends OrderController
                {
                    public function __construct(
                        private readonly string $signal,
                        private readonly string $release,
                        private readonly bool $holdAfterLock,
                    ) {}

                    protected function afterOrderWorkflowLocked(Order $order): void
                    {
                        if (! $this->holdAfterLock) {
                            return;
                        }

                        touch($this->signal);
                        $deadline = microtime(true) + 10;
                        while (! file_exists($this->release)) {
                            if (microtime(true) >= $deadline) {
                                throw new \RuntimeException('Timed out awaiting admin lock release.');
                            }
                            usleep(10_000);
                        }
                    }
                };

                $response = $controller->destroy($orderId);
                file_put_contents($result, $response->getStatusCode() === 409 ? 'conflict' : 'unexpected');
                exit($response->getStatusCode() === 409 ? 0 : 1);
            } catch (\Throwable $exception) {
                file_put_contents($result, 'error:'.$exception::class);
                exit(1);
            }
        }

        return $pid;
    }

    private function forkCancellationApproval(
        string $result,
        int $requestId,
        int $adminId,
        string $signal,
        string $release,
        bool $holdAtProvider = false,
        bool $signalBeforeApproval = false,
    ): int {
        $pid = pcntl_fork();
        if ($pid === -1) {
            $this->fail('Unable to fork the cancellation refund worker.');
        }

        if ($pid === 0) {
            DB::purge('cancellation_lock_test');
            Mail::fake();

            try {
                if ($signalBeforeApproval) {
                    touch($signal);
                }

                $gateway = new LockingRefundGateway($signal, $release, $holdAtProvider);
                $service = new CancellationRefundService(
                    $gateway,
                    app(CancellationNotificationService::class),
                );
                $request = $service->approve(
                    $requestId,
                    User::query()->findOrFail($adminId),
                    '90.00',
                    null,
                );
                file_put_contents($result, json_encode([
                    'status' => $request->status,
                    'refund_calls' => $gateway->refundCalls,
                    'idempotency_key' => $request->idempotency_key,
                ], JSON_THROW_ON_ERROR));
                exit(0);
            } catch (\Throwable $exception) {
                file_put_contents($result, 'error:'.$exception::class.':'.$exception->getMessage());
                exit(1);
            }
        }

        return $pid;
    }

    private function waitForSignal(string $signal, string $description, ?int $child = null): void
    {
        $deadline = microtime(true) + 10;

        while (! file_exists($signal)) {
            if ($child !== null) {
                $status = 0;
                if (pcntl_waitpid($child, $status, WNOHANG) === $child) {
                    $this->fail("Worker exited before signalling {$description}.");
                }
            }

            if (microtime(true) >= $deadline) {
                $this->fail("Timed out waiting for {$description}.");
            }

            usleep(10_000);
        }
    }

    private function waitForChild(int $child): int
    {
        $deadline = microtime(true) + 10;

        do {
            $status = 0;
            $result = pcntl_waitpid($child, $status, WNOHANG);
            if ($result === $child) {
                return $status;
            }
            if ($result === -1) {
                $this->fail('Unable to wait for lock-test worker.');
            }
            usleep(10_000);
        } while (microtime(true) < $deadline);

        $this->fail('Timed out waiting for lock-test worker to finish.');
    }

    private function assertWorkerIsBlocked(int $child): void
    {
        $deadline = microtime(true) + 0.5;

        do {
            $earlyStatus = 0;
            $result = pcntl_waitpid($child, $earlyStatus, WNOHANG);
            $this->assertSame(
                0,
                $result,
                'Second worker completed while the first still held the order lock. '
                .'The canonical order-then-payment lock contention guarantee is missing.',
            );
            usleep(10_000);
        } while (microtime(true) < $deadline);
    }

    private function disposableMysqlConnection(string $url): array
    {
        $parts = parse_url($url);
        if (! is_array($parts)) {
            $this->markTestSkipped('Lock-test URL is malformed.');
        }
        $database = ltrim((string) ($parts['path'] ?? ''), '/');
        $host = mb_strtolower((string) ($parts['host'] ?? ''));

        if (($parts['scheme'] ?? null) !== 'mysql'
            || ! str_starts_with(mb_strtolower($database), 'weelp_cancellation_test_')
            || mb_strtolower($database) === 'defaultdb'
            || str_contains($host, 'aivencloud')
            || str_contains($host, 'production')) {
            $this->markTestSkipped(
                "Rejected lock-test target {$host}/{$database}. The target must be MySQL, use a "
                .'weelp_cancellation_test_ database prefix, and must not resemble production.'
            );
        }

        return [
            'driver' => 'mysql',
            'host' => $parts['host'],
            'port' => $parts['port'] ?? 3306,
            'database' => $database,
            'username' => urldecode((string) ($parts['user'] ?? '')),
            'password' => urldecode((string) ($parts['pass'] ?? '')),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ];
    }
}

final class LockingRefundGateway implements StripeRefundGateway
{
    public int $refundCalls = 0;

    private int $refundedMinor = 0;

    public function __construct(
        private readonly string $signal,
        private readonly string $release,
        private readonly bool $holdAtProvider,
    ) {}

    public function refundedAmount(string $paymentIntentId): int
    {
        return $this->refundedMinor;
    }

    public function refund(
        string $paymentIntentId,
        int $amountInMinorUnits,
        string $idempotencyKey,
        array $metadata,
    ): object {
        $this->refundCalls++;

        if ($this->holdAtProvider) {
            touch($this->signal);
            $deadline = microtime(true) + 10;
            while (! file_exists($this->release)) {
                if (microtime(true) >= $deadline) {
                    throw new \RuntimeException('Timed out awaiting provider release.');
                }
                usleep(10_000);
            }
        }

        $this->refundedMinor = $amountInMinorUnits;

        return (object) [
            'id' => 're_lock_test',
            'status' => 'succeeded',
            'amount' => $amountInMinorUnits,
        ];
    }
}
