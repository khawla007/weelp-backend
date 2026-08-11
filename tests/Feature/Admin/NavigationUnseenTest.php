<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NavigationUnseenTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    public function test_admin_navigation_seen_timestamps_start_at_the_database_creation_baseline(): void
    {
        $before = Carbon::now()->startOfSecond();

        $admin = $this->admin()->fresh();

        $after = Carbon::now()->endOfSecond();

        $this->assertNotNull($admin->admin_orders_last_seen_at);
        $this->assertNotNull($admin->admin_reviews_last_seen_at);
        $this->assertTrue($admin->admin_orders_last_seen_at->betweenIncluded($before, $after));
        $this->assertTrue($admin->admin_reviews_last_seen_at->betweenIncluded($before, $after));
    }

    public function test_migration_baselines_existing_admins_and_defaults_future_admins(): void
    {
        $migration = require database_path(
            'migrations/2026_08_11_000001_add_admin_navigation_seen_at_to_users_table.php'
        );

        $migration->down();

        $existingAdmin = $this->admin();

        $migration->up();

        $columns = collect(Schema::getColumns('users'))->keyBy('name');

        $this->assertFalse($columns['admin_orders_last_seen_at']['nullable']);
        $this->assertSame('CURRENT_TIMESTAMP', $columns['admin_orders_last_seen_at']['default']);
        $this->assertFalse($columns['admin_reviews_last_seen_at']['nullable']);
        $this->assertSame('CURRENT_TIMESTAMP', $columns['admin_reviews_last_seen_at']['default']);

        $existingAdmin->refresh();
        $newAdmin = $this->admin()->fresh();

        $this->assertNotNull($existingAdmin->admin_orders_last_seen_at);
        $this->assertNotNull($existingAdmin->admin_reviews_last_seen_at);
        $this->assertNotNull($newAdmin->admin_orders_last_seen_at);
        $this->assertNotNull($newAdmin->admin_reviews_last_seen_at);
    }

    public function test_migration_adds_polling_indexes_with_expected_column_order(): void
    {
        $reviewIndex = collect(Schema::getIndexes('reviews'))
            ->firstWhere('name', 'reviews_created_at_navigation_unseen_index');
        $orderIndex = collect(Schema::getIndexes('orders'))
            ->firstWhere('name', 'orders_deleted_at_created_at_navigation_unseen_index');

        $this->assertSame(['created_at'], $reviewIndex['columns'] ?? null);
        $this->assertSame(['deleted_at', 'created_at'], $orderIndex['columns'] ?? null);
    }

    public function test_admin_can_get_separate_unseen_order_and_review_counts(): void
    {
        Carbon::setTestNow('2026-08-11 10:10:00');
        $admin = $this->admin();
        $admin->forceFill([
            'admin_orders_last_seen_at' => '2026-08-11 10:00:00',
            'admin_reviews_last_seen_at' => '2026-08-11 10:02:00',
        ])->save();

        Order::factory()->create(['created_at' => '2026-08-11 09:59:00', 'updated_at' => '2026-08-11 09:59:00']);
        Order::factory()->create(['created_at' => '2026-08-11 10:00:00', 'updated_at' => '2026-08-11 10:00:00']);
        Order::factory()->count(2)->create(['created_at' => '2026-08-11 10:01:00', 'updated_at' => '2026-08-11 10:01:00']);
        $deletedOrder = Order::factory()->create(['created_at' => '2026-08-11 10:03:00', 'updated_at' => '2026-08-11 10:03:00']);
        $deletedOrder->delete();

        Review::factory()->create(['created_at' => '2026-08-11 10:01:00', 'updated_at' => '2026-08-11 10:01:00']);
        Review::factory()->create(['created_at' => '2026-08-11 10:02:00', 'updated_at' => '2026-08-11 10:02:00']);
        Review::factory()->count(3)->create(['created_at' => '2026-08-11 10:03:00', 'updated_at' => '2026-08-11 10:03:00']);

        $this->actingAs($admin, 'api')
            ->getJson('/api/admin/navigation-unseen-counts')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'orders' => 2,
                    'reviews' => 3,
                ],
            ]);
    }

    public function test_records_created_later_in_the_watermark_second_are_counted(): void
    {
        Carbon::setTestNow('2026-08-11 10:01:00.000');
        $admin = $this->admin();
        $admin->forceFill([
            'admin_orders_last_seen_at' => '2026-08-11 09:59:00.000',
            'admin_reviews_last_seen_at' => '2026-08-11 09:59:00.000',
        ])->save();

        foreach (['orders', 'reviews'] as $resource) {
            $this->actingAs($admin, 'api')
                ->putJson("/api/admin/navigation-unseen-counts/{$resource}/seen", [
                    'seen_through' => '2026-08-11T10:00:00.100Z',
                ])
                ->assertOk();
        }

        Carbon::setTestNow('2026-08-11 10:00:00.900');
        $order = Order::factory()->create();
        $review = Review::factory()->create();

        $this->assertSame('2026-08-11 10:00:00.900', $order->fresh()->created_at->format('Y-m-d H:i:s.v'));
        $this->assertSame('2026-08-11 10:00:00.900', $review->fresh()->created_at->format('Y-m-d H:i:s.v'));

        $this->actingAs($admin, 'api')
            ->getJson('/api/admin/navigation-unseen-counts')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'orders' => 1,
                    'reviews' => 1,
                ],
            ]);
    }

    public function test_marking_orders_seen_through_a_boundary_preserves_reviews_and_later_orders(): void
    {
        Carbon::setTestNow('2026-08-11 10:10:00');
        $admin = $this->admin();
        $admin->forceFill([
            'admin_orders_last_seen_at' => '2026-08-11 10:00:00',
            'admin_reviews_last_seen_at' => '2026-08-11 10:00:00',
        ])->save();

        Order::factory()->create(['created_at' => '2026-08-11 10:05:00', 'updated_at' => '2026-08-11 10:05:00']);
        Order::factory()->create(['created_at' => '2026-08-11 10:06:00', 'updated_at' => '2026-08-11 10:06:00']);
        Review::factory()->create(['created_at' => '2026-08-11 10:04:00', 'updated_at' => '2026-08-11 10:04:00']);

        $this->actingAs($admin, 'api')
            ->putJson('/api/admin/navigation-unseen-counts/orders/seen', [
                'seen_through' => '2026-08-11T10:05:00.000Z',
            ])
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'orders' => 1,
                    'reviews' => 1,
                ],
            ]);

        $admin->refresh();
        $this->assertSame('2026-08-11 10:05:00.000', $admin->admin_orders_last_seen_at->format('Y-m-d H:i:s.v'));
        $this->assertSame('2026-08-11 10:00:00.000', $admin->admin_reviews_last_seen_at->format('Y-m-d H:i:s.v'));
    }

    public function test_marking_seen_is_isolated_per_admin(): void
    {
        Carbon::setTestNow('2026-08-11 10:10:00');
        $firstAdmin = $this->admin();
        $secondAdmin = $this->admin();
        $firstAdmin->forceFill([
            'admin_orders_last_seen_at' => '2026-08-11 10:00:00',
            'admin_reviews_last_seen_at' => '2026-08-11 10:00:00',
        ])->save();
        $secondAdmin->forceFill([
            'admin_orders_last_seen_at' => '2026-08-11 10:00:00',
            'admin_reviews_last_seen_at' => '2026-08-11 10:00:00',
        ])->save();
        Order::factory()->count(2)->create(['created_at' => '2026-08-11 10:04:00', 'updated_at' => '2026-08-11 10:04:00']);
        Review::factory()->create(['created_at' => '2026-08-11 10:04:00', 'updated_at' => '2026-08-11 10:04:00']);

        $this->actingAs($firstAdmin, 'api')
            ->putJson('/api/admin/navigation-unseen-counts/orders/seen', [
                'seen_through' => '2026-08-11T10:05:00.000Z',
            ])
            ->assertOk()
            ->assertExactJson(['data' => ['orders' => 0, 'reviews' => 1]]);

        $this->actingAs($secondAdmin, 'api')
            ->getJson('/api/admin/navigation-unseen-counts')
            ->assertOk()
            ->assertExactJson(['data' => ['orders' => 2, 'reviews' => 1]]);

        $this->assertSame(
            '2026-08-11 10:00:00.000',
            $secondAdmin->fresh()->admin_orders_last_seen_at->format('Y-m-d H:i:s.v')
        );
    }

    public function test_marking_reviews_seen_without_a_boundary_uses_server_now(): void
    {
        Carbon::setTestNow('2026-08-11 10:10:00');
        $admin = $this->admin();
        $admin->forceFill([
            'admin_orders_last_seen_at' => '2026-08-11 10:00:00',
            'admin_reviews_last_seen_at' => '2026-08-11 10:00:00',
        ])->save();
        Order::factory()->create(['created_at' => '2026-08-11 10:04:00', 'updated_at' => '2026-08-11 10:04:00']);
        Review::factory()->create(['created_at' => '2026-08-11 10:09:00', 'updated_at' => '2026-08-11 10:09:00']);

        $this->actingAs($admin, 'api')
            ->putJson('/api/admin/navigation-unseen-counts/reviews/seen')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'orders' => 1,
                    'reviews' => 0,
                ],
            ]);

        $admin->refresh();
        $this->assertSame('2026-08-11 10:10:00.000', $admin->admin_reviews_last_seen_at->format('Y-m-d H:i:s.v'));
        $this->assertSame('2026-08-11 10:00:00.000', $admin->admin_orders_last_seen_at->format('Y-m-d H:i:s.v'));
    }

    public function test_mark_seen_rejects_unsupported_resources_and_non_contract_timestamps(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'api')
            ->putJson('/api/admin/navigation-unseen-counts/messages/seen')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('resource');

        foreach ([null, '', 'not-a-date', '2026-08-11'] as $seenThrough) {
            $this->actingAs($admin, 'api')
                ->putJson('/api/admin/navigation-unseen-counts/orders/seen', ['seen_through' => $seenThrough])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('seen_through');
        }
    }

    public function test_future_seen_through_is_clamped_to_server_now(): void
    {
        Carbon::setTestNow('2026-08-11 10:10:00');
        $admin = $this->admin();
        $admin->forceFill(['admin_orders_last_seen_at' => '2026-08-11 10:00:00'])->save();

        $this->actingAs($admin, 'api')
            ->putJson('/api/admin/navigation-unseen-counts/orders/seen', [
                'seen_through' => '2026-08-11T11:00:00.000Z',
            ])
            ->assertOk();

        $this->assertSame(
            '2026-08-11 10:10:00.000',
            $admin->fresh()->admin_orders_last_seen_at->format('Y-m-d H:i:s.v')
        );
    }

    public function test_seen_through_preserves_non_zero_milliseconds(): void
    {
        Carbon::setTestNow('2026-08-11 10:10:00.000');
        $admin = $this->admin();
        $admin->forceFill(['admin_orders_last_seen_at' => '2026-08-11 10:00:00.000'])->save();

        $this->actingAs($admin, 'api')
            ->putJson('/api/admin/navigation-unseen-counts/orders/seen', [
                'seen_through' => '2026-08-11T10:05:00.789Z',
            ])
            ->assertOk();

        $this->assertSame(
            '2026-08-11 10:05:00.789',
            $admin->fresh()->admin_orders_last_seen_at->format('Y-m-d H:i:s.v')
        );
    }

    public function test_delayed_older_same_second_request_cannot_lose_milliseconds(): void
    {
        Carbon::setTestNow('2026-08-11 10:10:00.000');
        $admin = $this->admin();
        $admin->forceFill(['admin_orders_last_seen_at' => '2026-08-11 10:00:00.000'])->save();

        foreach (['2026-08-11T10:05:00.789Z', '2026-08-11T10:05:00.456Z'] as $seenThrough) {
            $this->actingAs($admin, 'api')
                ->putJson('/api/admin/navigation-unseen-counts/orders/seen', ['seen_through' => $seenThrough])
                ->assertOk();
        }

        $this->assertSame(
            '2026-08-11 10:05:00.789',
            $admin->fresh()->admin_orders_last_seen_at->format('Y-m-d H:i:s.v')
        );
    }

    public function test_delayed_older_seen_request_cannot_move_timestamp_backward(): void
    {
        Carbon::setTestNow('2026-08-11 10:10:00');
        $admin = $this->admin();
        $admin->forceFill(['admin_orders_last_seen_at' => '2026-08-11 10:00:00'])->save();

        $this->actingAs($admin, 'api')
            ->putJson('/api/admin/navigation-unseen-counts/orders/seen', [
                'seen_through' => '2026-08-11T10:08:00.000Z',
            ])
            ->assertOk();
        $this->actingAs($admin, 'api')
            ->putJson('/api/admin/navigation-unseen-counts/orders/seen', [
                'seen_through' => '2026-08-11T10:05:00.000Z',
            ])
            ->assertOk();

        $this->assertSame(
            '2026-08-11 10:08:00.000',
            $admin->fresh()->admin_orders_last_seen_at->format('Y-m-d H:i:s.v')
        );
    }

    public function test_navigation_unseen_endpoints_require_authentication(): void
    {
        $this->getJson('/api/admin/navigation-unseen-counts')->assertUnauthorized();
        $this->putJson('/api/admin/navigation-unseen-counts/orders/seen')->assertUnauthorized();
    }

    public function test_navigation_unseen_endpoints_require_an_admin_role(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($customer, 'api')
            ->getJson('/api/admin/navigation-unseen-counts')
            ->assertForbidden();
        $this->actingAs($customer, 'api')
            ->putJson('/api/admin/navigation-unseen-counts/orders/seen')
            ->assertForbidden();
    }
}
