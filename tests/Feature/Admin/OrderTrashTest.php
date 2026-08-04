<?php

namespace Tests\Feature\Admin;

use App\Models\Commission;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrderTrashTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    public static function adminRoles(): array
    {
        return [
            'admin' => [User::ROLE_ADMIN],
            'super admin' => [User::ROLE_SUPER_ADMIN],
        ];
    }

    public function test_small_active_result_always_returns_page_one_metadata(): void
    {
        Order::factory()->count(2)->create();

        $this->actingAs($this->admin(), 'api')
            ->getJson('/api/admin/orders?page=1')
            ->assertOk()
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 3)
            ->assertJsonPath('total', 2)
            ->assertJsonPath('last_page', 1)
            ->assertJsonCount(2, 'data');
    }

    public function test_empty_result_still_returns_page_one_metadata(): void
    {
        $this->actingAs($this->admin(), 'api')
            ->getJson('/api/admin/orders')
            ->assertOk()
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 3)
            ->assertJsonPath('total', 0)
            ->assertJsonPath('last_page', 1);
    }

    public function test_page_values_below_one_are_normalized_to_page_one(): void
    {
        Order::factory()->count(4)->create();

        foreach ([0, -2] as $page) {
            $this->actingAs($this->admin(), 'api')
                ->getJson("/api/admin/orders?page={$page}")
                ->assertOk()
                ->assertJsonPath('current_page', 1);
        }
    }

    public function test_active_and_trash_views_are_isolated(): void
    {
        $active = Order::factory()->create();
        $trashed = Order::factory()->create();
        $trashed->delete();

        $this->actingAs($this->admin(), 'api')
            ->getJson('/api/admin/orders?view=active')
            ->assertOk()
            ->assertJsonPath('data.0.id', $active->id)
            ->assertJsonMissing(['id' => $trashed->id])
            ->assertJsonPath('trash_count', 1);

        $this->actingAs($this->admin(), 'api')
            ->getJson('/api/admin/orders?view=trash')
            ->assertOk()
            ->assertJsonPath('data.0.id', $trashed->id)
            ->assertJsonMissing(['id' => $active->id]);
    }

    public function test_invalid_view_is_rejected(): void
    {
        $this->actingAs($this->admin(), 'api')
            ->getJson('/api/admin/orders?view=everything')
            ->assertUnprocessable();
    }

    #[DataProvider('adminRoles')]
    public function test_both_admin_roles_can_trash_restore_and_permanently_delete_orders(string $role): void
    {
        $actor = User::factory()->create([
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
        ]);
        $order = Order::factory()->create();

        $this->actingAs($actor, 'api')
            ->deleteJson("/api/admin/orders/{$order->id}")
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Order moved to Trash.']);
        $this->assertSoftDeleted('orders', ['id' => $order->id]);

        $this->actingAs($actor, 'api')
            ->postJson("/api/admin/orders/{$order->id}/restore")
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Order restored successfully.']);
        $this->assertNotSoftDeleted('orders', ['id' => $order->id]);

        $order->delete();

        $this->actingAs($actor, 'api')
            ->deleteJson("/api/admin/orders/{$order->id}/force")
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Order permanently deleted.']);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_mutations_reject_orders_in_the_wrong_state(): void
    {
        $actor = $this->admin();
        $active = Order::factory()->create();
        $trashed = Order::factory()->create();
        $trashed->delete();

        $this->actingAs($actor, 'api')
            ->postJson("/api/admin/orders/{$active->id}/restore")
            ->assertNotFound();

        $this->actingAs($actor, 'api')
            ->deleteJson("/api/admin/orders/{$active->id}/force")
            ->assertNotFound();

        $this->actingAs($actor, 'api')
            ->deleteJson("/api/admin/orders/{$trashed->id}")
            ->assertNotFound();
    }

    public function test_soft_delete_preserves_dependencies_and_force_delete_applies_foreign_key_rules(): void
    {
        $actor = $this->admin();
        $creator = User::factory()->create();
        $order = Order::factory()->create(['creator_id' => $creator->id]);
        $payment = $order->payment()->create([
            'payment_status' => 'paid',
            'payment_method' => 'credit_card',
            'total_amount' => 125.00,
        ]);
        $contact = $order->emergencyContact()->create([
            'contact_name' => 'Test Contact',
            'contact_phone' => '+15555550123',
            'relationship' => 'Friend',
        ]);
        $commission = Commission::create([
            'creator_id' => $creator->id,
            'order_id' => $order->id,
            'commission_rate' => 10,
            'commission_amount' => 12.50,
            'status' => 'pending',
        ]);
        $review = Review::factory()->create(['order_id' => $order->id]);

        $this->actingAs($actor, 'api')
            ->deleteJson("/api/admin/orders/{$order->id}")
            ->assertOk();

        $this->assertDatabaseHas('order_payments', ['id' => $payment->id]);
        $this->assertDatabaseHas('order_emergency_contacts', ['id' => $contact->id]);
        $this->assertDatabaseHas('commissions', ['id' => $commission->id]);
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'order_id' => $order->id]);

        $this->actingAs($actor, 'api')
            ->deleteJson("/api/admin/orders/{$order->id}/force")
            ->assertOk();

        $this->assertDatabaseMissing('order_payments', ['id' => $payment->id]);
        $this->assertDatabaseMissing('order_emergency_contacts', ['id' => $contact->id]);
        $this->assertDatabaseMissing('commissions', ['id' => $commission->id]);
        $this->assertNull($review->fresh()->order_id);
    }

    public function test_summary_excludes_trashed_orders_and_includes_restored_orders(): void
    {
        $actor = $this->admin();
        $order = Order::factory()->create(['status' => 'completed']);
        $order->payment()->create([
            'payment_status' => 'paid',
            'payment_method' => 'credit_card',
            'total_amount' => 125.00,
        ]);

        $this->actingAs($actor, 'api')->getJson('/api/admin/orders')
            ->assertOk()
            ->assertJsonPath('summary.total_orders', 1)
            ->assertJsonPath('summary.completed_orders', 1)
            ->assertJsonPath('summary.total_revenue', 125);

        $this->actingAs($actor, 'api')->deleteJson("/api/admin/orders/{$order->id}")->assertOk();

        $this->actingAs($actor, 'api')->getJson('/api/admin/orders')
            ->assertOk()
            ->assertJsonPath('summary.total_orders', 0)
            ->assertJsonPath('summary.completed_orders', 0)
            ->assertJsonPath('summary.total_revenue', 0);

        $this->actingAs($actor, 'api')->postJson("/api/admin/orders/{$order->id}/restore")->assertOk();

        $this->actingAs($actor, 'api')->getJson('/api/admin/orders')
            ->assertOk()
            ->assertJsonPath('summary.total_orders', 1)
            ->assertJsonPath('summary.completed_orders', 1)
            ->assertJsonPath('summary.total_revenue', 125);
    }
}
