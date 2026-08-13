<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\CancellationRequest;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use LogicException;

final class CancellationWorkflowSeeder extends Seeder
{
    public const MARKER_PREFIX = '[Cancellation workflow fixture:';

    private const CUSTOMER_EMAIL = 'abhinav@fanaticcoders.com';

    private const ADMIN_EMAIL = 'khawla@fanaticcoders.com';

    private const TOTAL_AMOUNT = '100.00';

    private const ACTIVITY_SLUG = 'cancellation-workflow-fixture';

    private const ACTIVITY_SIGNATURE = [
        'name' => 'Cancellation Workflow Test Booking',
        'description' => 'Local-only activity used to verify cancellation workflow states.',
        'short_description' => 'Local cancellation workflow fixture.',
        'item_type' => 'activity',
        'featured_activity' => false,
    ];

    public function run(): void
    {
        $connection = (string) config('database.default');
        $connectionConfig = config("database.connections.{$connection}", []);
        $expectedDatabase = (string) config('cancellation.fixture_database');

        self::assertSafeConfiguration(
            app()->environment(),
            (bool) config('cancellation.fixture_enabled', false),
            $expectedDatabase,
            is_array($connectionConfig) ? $connectionConfig : [],
            $this->actualDatabaseName($connection),
        );

        DB::transaction(function (): void {
            $customer = $this->fixtureUser(
                self::CUSTOMER_EMAIL,
                'Abhinav Chaudhary',
                User::ROLE_CUSTOMER,
                'abhinav@123#',
            );
            $admin = $this->fixtureUser(
                self::ADMIN_EMAIL,
                'Khawla Admin',
                User::ROLE_SUPER_ADMIN,
                'khawla@123#',
            );
            $activity = $this->fixtureActivity();

            foreach ($this->fixtures() as $key => $fixture) {
                $order = $this->upsertOrder($key, $fixture, $customer, $activity);
                $this->upsertPayment($key, $fixture, $order);

                if (isset($fixture['cancellation'])) {
                    $this->upsertCancellation($order, $customer, $admin, $fixture['cancellation']);
                }
            }
        });
    }

    public static function assertSafeConfiguration(
        string $environment,
        bool $enabled,
        string $expectedDatabase,
        array $connection,
        string $actualDatabase,
    ): void {
        $host = strtolower(trim(is_string($connection['host'] ?? null) ? $connection['host'] : ''));
        $database = is_string($connection['database'] ?? null) ? trim($connection['database']) : '';
        $write = is_array($connection['write'] ?? null) ? $connection['write'] : [];
        $writeHost = $write['host'] ?? $host;
        $writeDatabase = $write['database'] ?? $database;
        $safe = $environment === 'local'
            && $enabled
            && in_array($host, ['localhost', '127.0.0.1'], true)
            && preg_match('/^weelp_local_[a-z0-9_]+$/D', $expectedDatabase) === 1
            && $database === $expectedDatabase
            && $actualDatabase === $expectedDatabase
            && is_string($writeHost)
            && strtolower(trim($writeHost)) === $host
            && is_string($writeDatabase)
            && trim($writeDatabase) === $expectedDatabase
            && blank($connection['url'] ?? null)
            && blank($write['url'] ?? null)
            && ! str_contains($host, 'aivencloud');

        if (! $safe) {
            throw new LogicException(
                'Cancellation fixtures require an explicitly enabled local Weelp database.',
            );
        }
    }

    private function actualDatabaseName(string $connection): string
    {
        $testOverride = config('cancellation.fixture_actual_database_override');
        if (defined('PHPUNIT_COMPOSER_INSTALL') && is_string($testOverride) && $testOverride !== '') {
            return $testOverride;
        }

        $driver = (string) config("database.connections.{$connection}.driver");
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new LogicException('Cancellation fixtures require a dedicated local MySQL database.');
        }

        $result = DB::connection($connection)->selectOne(
            'select database() as database_name',
            [],
            false,
        );

        return is_string($result?->database_name ?? null) ? $result->database_name : '';
    }

    private function fixtureActivity(): Activity
    {
        $matches = Activity::query()->where('slug', self::ACTIVITY_SLUG)->get();
        if ($matches->count() > 1) {
            throw new LogicException('Cancellation fixture activity identity is ambiguous.');
        }

        $activity = $matches->first();
        if ($activity instanceof Activity) {
            foreach (self::ACTIVITY_SIGNATURE as $attribute => $expected) {
                if ($activity->getAttribute($attribute) !== $expected) {
                    throw new LogicException('Cancellation fixture activity slug is already in use.');
                }
            }

            return $activity;
        }

        return Activity::query()->create([
            'slug' => self::ACTIVITY_SLUG,
            ...self::ACTIVITY_SIGNATURE,
        ]);
    }

    public static function marker(string $key): string
    {
        return self::MARKER_PREFIX.$key.']';
    }

    private function fixtureUser(string $email, string $name, string $role, string $localPassword): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($localPassword),
            ],
        );

        if ($user->wasRecentlyCreated) {
            $user->forceFill([
                'role' => $role,
                'status' => User::STATUS_ACTIVE,
            ])->save();
        }

        return $user;
    }

    /** @param array<string, mixed> $fixture */
    private function upsertOrder(string $key, array $fixture, User $customer, Activity $activity): Order
    {
        $order = Order::query()->withTrashed()->firstOrNew([
            'special_requirements' => self::marker($key),
        ]);
        $order->fill([
            'user_id' => $customer->id,
            'creator_id' => null,
            'orderable_type' => $activity->getMorphClass(),
            'orderable_id' => $activity->id,
            'variation_id' => null,
            'item_snapshot_json' => json_encode([
                'fixture_key' => $key,
                'name' => 'Cancellation fixture: '.str_replace('-', ' ', $key),
                'slug' => $activity->slug,
                'item_type' => 'activity',
                'location' => [
                    'city' => 'Dubai',
                    'city_slug' => 'dubai',
                ],
            ], JSON_THROW_ON_ERROR),
            'travel_date' => now()->addDays(45)->toDateString(),
            'preferred_time' => '10:00:00',
            'number_of_adults' => 2,
            'number_of_children' => 0,
            'status' => $fixture['order_status'],
        ]);
        if ($order->exists && $order->trashed()) {
            $order->restore();
        }
        $order->save();

        return $order;
    }

    /** @param array<string, mixed> $fixture */
    private function upsertPayment(string $key, array $fixture, Order $order): void
    {
        OrderPayment::query()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'payment_status' => $fixture['payment_status'],
                'stripe_session_id' => 'cs_test_weelp_cancellation_'.$key,
                'payment_intent_id' => 'pi_test_weelp_cancellation_'.$key,
                'payment_method' => 'credit_card',
                'amount' => self::TOTAL_AMOUNT,
                'is_custom_amount' => false,
                'custom_amount' => null,
                'total_amount' => self::TOTAL_AMOUNT,
                'refunded_amount' => $fixture['refunded_amount'],
                'currency' => 'USD',
            ],
        );
    }

    /** @param array<string, mixed> $state */
    private function upsertCancellation(Order $order, User $customer, User $admin, array $state): void
    {
        $requestedAt = now()->subDay();
        $travelStartsAt = now()->addDays(45)->setTime(10, 0);
        $isDecided = in_array(
            $state['status'],
            [CancellationRequest::STATUS_REJECTED, CancellationRequest::STATUS_APPROVED],
            true,
        ) || $state['status'] === CancellationRequest::STATUS_REFUND_FAILED;

        CancellationRequest::query()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'customer_id' => $customer->id,
                'status' => $state['status'],
                'reason' => 'Local fixture request for cancellation workflow verification.',
                'requested_at' => $requestedAt,
                'policy_version' => (string) config('cancellation.version'),
                'policy_snapshot' => [
                    'version' => config('cancellation.version'),
                    'bands' => config('cancellation.bands'),
                ],
                'travel_starts_at' => $travelStartsAt,
                'seconds_remaining' => $requestedAt->diffInSeconds($travelStartsAt),
                'paid_amount' => self::TOTAL_AMOUNT,
                'currency' => 'USD',
                'suggested_deduction_percentage' => '10.00',
                'suggested_deduction_amount' => '10.00',
                'suggested_refund_amount' => '90.00',
                'final_refund_amount' => $state['final_refund_amount'] ?? null,
                'final_deduction_amount' => $state['final_deduction_amount'] ?? null,
                'decision_explanation' => $state['decision_explanation'] ?? null,
                'decided_by' => $isDecided ? $admin->id : null,
                'decided_at' => $isDecided ? now()->subHours(12) : null,
                'stripe_refund_id' => $state['stripe_refund_id'] ?? null,
                'idempotency_key' => $state['idempotency_key'] ?? null,
                'failure_code' => $state['failure_code'] ?? null,
                'failure_summary' => $state['failure_summary'] ?? null,
                'failure_disposition' => $state['failure_disposition'] ?? null,
                'refund_outcome' => $state['refund_outcome'] ?? null,
                'refund_completed_at' => isset($state['refund_outcome']) ? now()->subHours(11) : null,
            ],
        );
    }

    /** @return array<string, array<string, mixed>> */
    private function fixtures(): array
    {
        return [
            'eligible' => [
                'order_status' => 'confirmed',
                'payment_status' => 'paid',
                'refunded_amount' => '0.00',
            ],
            'pending' => [
                'order_status' => 'confirmed',
                'payment_status' => 'paid',
                'refunded_amount' => '0.00',
                'cancellation' => ['status' => CancellationRequest::STATUS_PENDING],
            ],
            'rejected' => [
                'order_status' => 'confirmed',
                'payment_status' => 'paid',
                'refunded_amount' => '0.00',
                'cancellation' => [
                    'status' => CancellationRequest::STATUS_REJECTED,
                    'decision_explanation' => 'The booking remains confirmed for this local rejection fixture.',
                ],
            ],
            'refund-failed-definitive' => [
                'order_status' => 'confirmed',
                'payment_status' => 'paid',
                'refunded_amount' => '0.00',
                'cancellation' => [
                    'status' => CancellationRequest::STATUS_REFUND_FAILED,
                    'final_refund_amount' => '90.00',
                    'final_deduction_amount' => '10.00',
                    'decision_explanation' => 'Local definitive refund failure fixture.',
                    'idempotency_key' => 'cancel-fixture-refund-failed-definitive',
                    'failure_code' => 'fixture_refund_rejected',
                    'failure_summary' => 'The local fixture refund was definitively rejected.',
                    'failure_disposition' => 'definitive',
                ],
            ],
            'refund-failed-indeterminate' => [
                'order_status' => 'confirmed',
                'payment_status' => 'paid',
                'refunded_amount' => '0.00',
                'cancellation' => [
                    'status' => CancellationRequest::STATUS_REFUND_FAILED,
                    'final_refund_amount' => '90.00',
                    'final_deduction_amount' => '10.00',
                    'decision_explanation' => 'Local indeterminate refund failure fixture.',
                    'idempotency_key' => 'cancel-fixture-refund-failed-indeterminate',
                    'failure_code' => 'fixture_refund_unknown',
                    'failure_summary' => 'The local fixture refund outcome could not be confirmed.',
                    'failure_disposition' => 'indeterminate',
                ],
            ],
            'approved-zero' => [
                'order_status' => 'cancelled',
                'payment_status' => 'paid',
                'refunded_amount' => '0.00',
                'cancellation' => [
                    'status' => CancellationRequest::STATUS_APPROVED,
                    'final_refund_amount' => '0.00',
                    'final_deduction_amount' => '100.00',
                    'decision_explanation' => 'No refund approved for this local fixture.',
                    'idempotency_key' => 'cancel-fixture-approved-zero',
                    'refund_outcome' => 'no_refund',
                ],
            ],
            'approved-partial' => [
                'order_status' => 'cancelled',
                'payment_status' => 'partially_refunded',
                'refunded_amount' => '60.00',
                'cancellation' => [
                    'status' => CancellationRequest::STATUS_APPROVED,
                    'final_refund_amount' => '60.00',
                    'final_deduction_amount' => '40.00',
                    'decision_explanation' => 'Partial refund approved for this local fixture.',
                    'stripe_refund_id' => 're_test_weelp_cancellation_partial',
                    'idempotency_key' => 'cancel-fixture-approved-partial',
                    'refund_outcome' => 'partial',
                ],
            ],
        ];
    }
}
