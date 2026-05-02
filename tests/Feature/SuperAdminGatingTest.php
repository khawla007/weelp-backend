<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminGatingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => User::STATUS_ACTIVE]);
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => User::ROLE_SUPER_ADMIN, 'status' => User::STATUS_ACTIVE]);
    }

    public function test_admin_blocked_from_users_bulk_delete(): void
    {
        $response = $this->actingAs($this->admin(), 'api')
            ->postJson('/api/admin/users/bulk-delete', ['ids' => []]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Forbidden — super_admin required']);
    }

    public function test_super_admin_passes_users_bulk_delete_gate(): void
    {
        $response = $this->actingAs($this->superAdmin(), 'api')
            ->postJson('/api/admin/users/bulk-delete', ['ids' => []]);

        $this->assertNotSame(403, $response->status(), 'super_admin must clear gate');
    }

    public function test_admin_blocked_from_user_destroy(): void
    {
        $target = User::factory()->create();

        $response = $this->actingAs($this->admin(), 'api')
            ->deleteJson("/api/admin/users/{$target->id}");

        $response->assertStatus(403);
    }

    public function test_admin_blocked_from_categories_bulk_delete(): void
    {
        $response = $this->actingAs($this->admin(), 'api')
            ->postJson('/api/admin/categories/bulk-delete', ['ids' => []]);

        $response->assertStatus(403);
    }

    public function test_admin_blocked_from_blogs_bulk_delete(): void
    {
        $response = $this->actingAs($this->admin(), 'api')
            ->postJson('/api/admin/blogs/bulk-delete', ['ids' => []]);

        $response->assertStatus(403);
    }

    public function test_customer_blocked_at_admin_gate_before_super_admin_check(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $response = $this->actingAs($customer, 'api')
            ->postJson('/api/admin/users/bulk-delete', ['ids' => []]);

        $response->assertStatus(403);
    }
}
