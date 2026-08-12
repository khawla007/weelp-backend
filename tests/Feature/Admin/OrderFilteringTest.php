<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\Order;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
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
        foreach (range(1, 6) as $index) {
            $this->createOrder("Safari Customer {$index}", "Safari Trip {$index}", 'completed');
        }

        $this->createOrder('Other Customer', 'City Walk', 'pending');
        $this->createOrder('Deleted Customer', 'Deleted Trip', 'cancelled', true);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/orders?page=1&status=completed&search=safari')
            ->assertOk()
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 5)
            ->assertJsonPath('total', 6)
            ->assertJsonPath('last_page', 2)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('summary.total_orders', 7)
            ->assertJsonPath('trash_count', 1);
    }

    public function test_admin_order_list_uses_newest_first_stable_ordering_and_iso_created_at(): void
    {
        $createdAtValues = [
            '2026-08-05T10:20:30Z',
            '2026-08-06T10:20:30Z',
            '2026-08-07T10:20:30Z',
            '2026-08-08T10:20:30Z',
            '2026-08-10T10:20:30Z',
            '2026-08-10T10:20:30Z',
        ];
        $orders = [];

        foreach ($createdAtValues as $index => $createdAt) {
            $number = $index + 1;
            $order = $this->createOrder("Customer {$number}", "Trip {$number}");
            $order->forceFill(['created_at' => Carbon::parse($createdAt)])->saveQuietly();
            $orders[] = $order->refresh();
        }

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/orders?page=1')
            ->assertOk()
            ->assertJsonPath('per_page', 5)
            ->assertJsonCount(5, 'data');

        $this->assertSame(
            [
                'ids' => [$orders[5]->id, $orders[4]->id, $orders[3]->id, $orders[2]->id, $orders[1]->id],
                'created_at' => $orders[5]->created_at->toISOString(),
            ],
            [
                'ids' => array_column($response->json('data'), 'id'),
                'created_at' => $response->json('data.0.created_at'),
            ],
        );
    }

    public function test_admin_can_view_active_order_details_with_relations_and_timestamps(): void
    {
        $order = $this->createOrder('Detail Customer', 'Detail Safari');
        $order->user->profile()->create([
            'phone' => '+15555550000',
        ]);
        $payment = $order->payment()->create([
            'payment_status' => 'paid',
            'payment_method' => 'card',
            'total_amount' => 185,
        ]);
        $contact = $order->emergencyContact()->create([
            'contact_name' => 'Emergency Person',
            'contact_phone' => '+15555550123',
            'relationship' => 'Sibling',
        ]);
        $order->refresh();

        $this->actingAs($this->admin, 'api')
            ->getJson("/api/admin/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.user.id', $order->user_id)
            ->assertJsonPath('data.user.name', 'Detail Customer')
            ->assertJsonPath('data.user.profile.phone', '+15555550000')
            ->assertJsonPath('data.orderable.id', $order->orderable_id)
            ->assertJsonPath('data.orderable.name', 'Detail Safari')
            ->assertJsonPath('data.payment.id', $payment->id)
            ->assertJsonPath('data.emergency_contact.id', $contact->id)
            ->assertJsonPath('data.is_trashed', false)
            ->assertJsonPath('data.created_at', $order->created_at->toISOString());
    }

    public function test_admin_order_status_updates_enforce_payment_transition_rules(): void
    {
        Mail::fake();

        $pendingPaymentOrder = $this->createOrder('Pending Payment Customer', 'Pending Payment Trip');
        $pendingPaymentOrder->payment()->create([
            'payment_status' => 'pending',
            'payment_method' => 'card',
            'total_amount' => 185,
        ]);

        foreach (['pending', 'processing'] as $status) {
            $this->actingAs($this->admin, 'api')
                ->putJson("/api/admin/orders/{$pendingPaymentOrder->id}", ['status' => $status])
                ->assertStatus(400)
                ->assertJsonPath('success', false);
        }

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/orders/{$pendingPaymentOrder->id}", ['status' => 'completed'])
            ->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot mark order as completed. Payment not paid yet.',
            ]);
        $this->assertSame('pending', $pendingPaymentOrder->fresh()->status);

        $paidOrder = $this->createOrder('Paid Customer', 'Paid Trip');
        $paidOrder->payment()->create([
            'payment_status' => 'paid',
            'payment_method' => 'card',
            'total_amount' => 185,
        ]);

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/orders/{$paidOrder->id}", ['status' => 'cancelled'])
            ->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot cancel order. Payment is not pending.',
            ]);
        $this->assertSame('pending', $paidOrder->fresh()->status);

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/orders/{$pendingPaymentOrder->id}", ['status' => 'cancelled'])
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('orders', [
            'id' => $pendingPaymentOrder->id,
            'status' => 'cancelled',
        ]);

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/orders/{$paidOrder->id}", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('orders', [
            'id' => $paidOrder->id,
            'status' => 'completed',
        ]);
    }

    public function test_empty_filtered_results_keep_page_one_metadata(): void
    {
        $this->createOrder('Existing Customer', 'Existing Trip', 'pending');

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/orders?page=1&status=completed&search=missing')
            ->assertOk()
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 5)
            ->assertJsonPath('total', 0)
            ->assertJsonPath('last_page', 1)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('message', 'No orders match the selected filters.');
    }
}
