<?php

namespace Tests\Feature\Customer;

use App\Models\Activity;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_list_own_orders(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
        $activity = Activity::factory()->create();

        Order::factory()->count(3)->create([
            'user_id' => $user->id,
            'orderable_type' => 'activity',
            'orderable_id' => $activity->id,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/customer/userorders');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_customer_orders_excludes_other_users_orders(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
        $otherUser = User::factory()->create(['role' => 'customer']);
        $activity = Activity::factory()->create();

        Order::factory()->create([
            'user_id' => $user->id,
            'orderable_type' => 'activity',
            'orderable_id' => $activity->id,
        ]);
        Order::factory()->create([
            'user_id' => $otherUser->id,
            'orderable_type' => 'activity',
            'orderable_id' => $activity->id,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/customer/userorders');

        $response->assertOk();
        $orders = $response->json('orders');
        $this->assertCount(1, $orders);
    }

    public function test_order_list_returns_401_without_auth(): void
    {
        $response = $this->getJson('/api/customer/userorders');

        $response->assertUnauthorized();
    }
}
