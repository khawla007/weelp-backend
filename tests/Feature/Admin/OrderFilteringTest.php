<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\Order;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrderFilteringTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function createOrder(
        string $customerName,
        string $itemName,
        string $status = 'pending',
        bool $trashed = false,
        ?Model $orderable = null,
    ): Order {
        $customer = User::factory()->create(['name' => $customerName]);
        $orderable ??= Activity::factory()->create();
        $orderable->update(['name' => $itemName]);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'orderable_type' => $orderable->getMorphClass(),
            'orderable_id' => $orderable->getKey(),
            'status' => $status,
        ]);

        if ($trashed) {
            $order->delete();
        }

        return $order;
    }

    public static function supportedStatuses(): array
    {
        return [
            'pending' => ['pending'],
            'processing' => ['processing'],
            'completed' => ['completed'],
            'cancelled' => ['cancelled'],
        ];
    }

    #[DataProvider('supportedStatuses')]
    public function test_it_filters_orders_by_each_supported_status(string $status): void
    {
        $matching = $this->createOrder('Matching Customer', 'Matching Tour', $status);
        $this->createOrder('Other Customer', 'Other Tour', $status === 'pending' ? 'cancelled' : 'pending');

        $this->actingAs($this->admin, 'api')
            ->getJson("/api/admin/orders?status={$status}")
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $matching->id);
    }

    public function test_it_rejects_invalid_filter_values(): void
    {
        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/orders?status=confirmed')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/orders?search='.str_repeat('a', 101))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('search');
    }

    public function test_it_searches_order_number_customer_name_and_item_name(): void
    {
        $target = $this->createOrder('Khawla Traveller', 'Desert Safari Deluxe');
        $this->createOrder('Another Customer', 'Mountain Escape');

        foreach ([(string) $target->id, 'kHaWlA', 'sAfArI'] as $search) {
            $this->actingAs($this->admin, 'api')
                ->getJson('/api/admin/orders?search='.urlencode($search))
                ->assertOk()
                ->assertJsonPath('total', 1)
                ->assertJsonPath('data.0.id', $target->id);
        }
    }

    public function test_item_name_search_supports_legacy_class_morph_types(): void
    {
        $target = $this->createOrder('Legacy Customer', 'Scuba Diving Tour');
        $target->update(['orderable_type' => Activity::class]);

        $this->assertDatabaseHas('orders', [
            'id' => $target->id,
            'orderable_type' => Activity::class,
        ]);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/orders?search=scuba')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $target->id);
    }

    public function test_search_treats_like_wildcards_and_escape_marker_as_literal_characters(): void
    {
        $percentMatch = $this->createOrder('Percent Customer', 'Save 100% Adventure');
        $this->createOrder('Percent Other', 'Save 1000 Adventure');
        $underscoreMatch = $this->createOrder('Underscore Customer', 'Route A_B');
        $this->createOrder('Underscore Other', 'Route ACB');
        $escapeMatch = $this->createOrder('Escape Customer', 'Bang!Trip');
        $this->createOrder('Escape Other', 'BangTrip');

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/orders?search='.urlencode('100%'))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $percentMatch->id);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/orders?search='.urlencode('A_B'))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $underscoreMatch->id);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/orders?search='.urlencode('Bang!'))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $escapeMatch->id);
    }

    public function test_status_and_search_are_grouped_inside_each_order_view(): void
    {
        $activeMatch = $this->createOrder('Active Traveller', 'Desert Safari', 'completed');
        $this->createOrder('Active Traveller', 'Desert Safari', 'pending');
        $trashedMatch = $this->createOrder('Trash Traveller', 'Desert Safari', 'completed', true);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/orders?view=active&status=completed&search=desert')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $activeMatch->id);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/orders?view=trash&status=completed&search=desert')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $trashedMatch->id);
    }

    public function test_trash_item_search_finds_a_morph_type_with_no_active_orders(): void
    {
        $package = Package::factory()->create(['name' => 'Trash Only Package']);
        $trashed = $this->createOrder('Trash Customer', $package->name, 'completed', true, $package);
        $this->createOrder('Other Trash Customer', 'Unrelated Activity', 'completed', true);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/orders?view=trash&search=only+package')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $trashed->id);
    }

    public function test_filtered_pagination_does_not_filter_global_metrics(): void
    {
        foreach (range(1, 4) as $index) {
            $this->createOrder("Safari Customer {$index}", "Safari Trip {$index}", 'completed');
        }

        $this->createOrder('Other Customer', 'City Walk', 'pending');
        $this->createOrder('Deleted Customer', 'Deleted Trip', 'cancelled', true);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/orders?page=1&status=completed&search=safari')
            ->assertOk()
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 3)
            ->assertJsonPath('total', 4)
            ->assertJsonPath('last_page', 2)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('summary.total_orders', 5)
            ->assertJsonPath('trash_count', 1);
    }

    public function test_empty_filtered_results_keep_page_one_metadata(): void
    {
        $this->createOrder('Existing Customer', 'Existing Trip', 'pending');

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/orders?page=1&status=completed&search=missing')
            ->assertOk()
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 3)
            ->assertJsonPath('total', 0)
            ->assertJsonPath('last_page', 1)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('message', 'No orders match the selected filters.');
    }
}
