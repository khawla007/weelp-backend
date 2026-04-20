<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_route_rejects_customer(): void
    {
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer, 'api')
            ->getJson('/api/admin/dashboard/metrics');

        $response->assertForbidden();
    }

    public function test_customer_route_rejects_unauthenticated(): void
    {
        $response = $this->getJson('/api/customer/profile');

        $response->assertUnauthorized();
    }

    public function test_creator_route_rejects_non_creator(): void
    {
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer, 'api')
            ->getJson('/api/creator/dashboard/stats');

        $response->assertForbidden();
    }
}
