<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardOverviewChartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-08-18 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_overview_chart_returns_only_current_year_revenue_and_non_cancelled_booking_counts(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $januaryOrder = Order::factory()->create([
            'status' => 'completed',
            'created_at' => '2026-01-15 10:00:00',
            'updated_at' => '2026-01-15 10:00:00',
        ]);
        OrderPayment::factory()->for($januaryOrder)->create([
            'payment_status' => 'paid',
            'payment_method' => 'credit_card',
            'amount' => 1200,
            'total_amount' => 1200,
        ]);

        $februaryOrder = Order::factory()->create([
            'status' => 'pending',
            'created_at' => '2026-02-10 10:00:00',
            'updated_at' => '2026-02-10 10:00:00',
        ]);
        OrderPayment::factory()->for($februaryOrder)->create([
            'payment_status' => 'pending',
            'payment_method' => 'credit_card',
            'amount' => 700,
            'total_amount' => 700,
        ]);

        $cancelledJanuaryOrder = Order::factory()->create([
            'status' => 'cancelled',
            'created_at' => '2026-01-20 10:00:00',
            'updated_at' => '2026-01-20 10:00:00',
        ]);
        OrderPayment::factory()->for($cancelledJanuaryOrder)->create([
            'payment_status' => 'paid',
            'payment_method' => 'credit_card',
            'amount' => 900,
            'total_amount' => 900,
        ]);

        $deletedJanuaryOrder = Order::factory()->create([
            'status' => 'completed',
            'created_at' => '2026-01-25 10:00:00',
            'updated_at' => '2026-01-25 10:00:00',
        ]);
        OrderPayment::factory()->for($deletedJanuaryOrder)->create([
            'payment_status' => 'paid',
            'payment_method' => 'credit_card',
            'amount' => 999,
            'total_amount' => 999,
        ]);
        $deletedJanuaryOrder->delete();

        $previousYearOrder = Order::factory()->create([
            'status' => 'completed',
            'created_at' => '2025-03-12 10:00:00',
            'updated_at' => '2025-03-12 10:00:00',
        ]);
        OrderPayment::factory()->for($previousYearOrder)->create([
            'payment_status' => 'paid',
            'payment_method' => 'credit_card',
            'amount' => 5432,
            'total_amount' => 5432,
        ]);

        $response = $this->actingAs($superAdmin, 'api')
            ->getJson('/api/admin/dashboard/overview-chart');

        $response->assertOk()
            ->assertJsonCount(12, 'data')
            ->assertExactJson([
                'success' => true,
                'data' => [
                    ['name' => 'Jan', 'total' => 1200, 'bookings' => 1],
                    ['name' => 'Feb', 'total' => 0, 'bookings' => 1],
                    ['name' => 'Mar', 'total' => 0, 'bookings' => 0],
                    ['name' => 'Apr', 'total' => 0, 'bookings' => 0],
                    ['name' => 'May', 'total' => 0, 'bookings' => 0],
                    ['name' => 'Jun', 'total' => 0, 'bookings' => 0],
                    ['name' => 'Jul', 'total' => 0, 'bookings' => 0],
                    ['name' => 'Aug', 'total' => 0, 'bookings' => 0],
                    ['name' => 'Sep', 'total' => 0, 'bookings' => 0],
                    ['name' => 'Oct', 'total' => 0, 'bookings' => 0],
                    ['name' => 'Nov', 'total' => 0, 'bookings' => 0],
                    ['name' => 'Dec', 'total' => 0, 'bookings' => 0],
                ],
            ]);
    }

    public function test_overview_chart_requires_authentication(): void
    {
        $this->getJson('/api/admin/dashboard/overview-chart')
            ->assertUnauthorized();
    }

    public function test_overview_chart_rejects_authenticated_customers(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($customer, 'api')
            ->getJson('/api/admin/dashboard/overview-chart')
            ->assertForbidden();
    }
}
