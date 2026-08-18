<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\Order;
use App\Models\Package;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardBookingMixTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-18 12:00:00');
        $this->admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_it_maps_supported_current_month_non_cancelled_orders_and_ignores_other_types(): void
    {
        $activity = Activity::factory()->create(['name' => 'Safari']);
        $package = Package::factory()->create(['name' => 'Escape']);
        $itinerary = Itinerary::factory()->create(['name' => 'City trip']);

        $this->order(Activity::class, $activity->id, '2026-08-02');
        $this->order('activity', $activity->id, '2026-08-03');
        $this->order(Package::class, $package->id, '2026-08-04');
        $this->order('itinerary', $itinerary->id, '2026-08-05');
        $this->order('itinerary', $itinerary->id, '2026-08-06');
        $this->order('activity', $activity->id, '2026-08-07', 'cancelled');
        $this->order('transfer', 44, '2026-08-08');
        $this->order('package', $package->id, '2026-07-08');

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/dashboard/booking-mix')
            ->assertOk()
            ->assertJsonPath('data.total', 5)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total',
                    'categories' => [['key', 'label', 'count']],
                    'leaders' => [['type', 'id', 'name', 'bookings', 'change']],
                ],
            ])
            ->assertJsonPath('data.categories.0', ['key' => 'activities', 'label' => 'Activities', 'count' => 2])
            ->assertJsonPath('data.categories.1', ['key' => 'packages', 'label' => 'Packages', 'count' => 1])
            ->assertJsonPath('data.categories.2', ['key' => 'trips', 'label' => 'Trips', 'count' => 2]);
    }

    public function test_it_ranks_two_leaders_deterministically_and_calculates_month_over_month_change(): void
    {
        $first = Activity::factory()->create(['name' => 'Zulu experience']);
        $second = Activity::factory()->create(['name' => 'Alpha experience']);
        $trip = Itinerary::factory()->create(['name' => 'Third']);

        foreach (range(1, 3) as $day) {
            $this->order('activity', $first->id, "2026-08-0{$day}");
            $this->order('activity', $second->id, "2026-08-1{$day}");
        }
        foreach (range(1, 2) as $day) {
            $this->order('activity', $first->id, "2026-07-0{$day}");
            $this->order('itinerary', $trip->id, "2026-08-2{$day}");
        }

        $response = $this->actingAs($this->admin, 'api')->getJson('/api/admin/dashboard/booking-mix');

        $response->assertOk()
            ->assertJsonCount(2, 'data.leaders')
            ->assertJsonPath('data.leaders.0.id', $second->id)
            ->assertJsonPath('data.leaders.0.name', 'Alpha experience')
            ->assertJsonPath('data.leaders.0.bookings', 3)
            ->assertJsonPath('data.leaders.0.change', 100)
            ->assertJsonPath('data.leaders.1.id', $first->id)
            ->assertJsonPath('data.leaders.1.name', 'Zulu experience')
            ->assertJsonPath('data.leaders.1.change', 50);
    }

    public function test_it_uses_id_as_the_final_tiebreaker_for_leaders_with_the_same_name(): void
    {
        $first = Activity::factory()->create(['name' => 'Same experience']);
        $second = Activity::factory()->create(['name' => 'Same experience']);
        $zulu = Activity::factory()->create(['name' => 'Zulu experience']);

        foreach ([$first, $second, $zulu] as $activity) {
            foreach (range(1, 3) as $day) {
                $this->order('activity', $activity->id, "2026-08-0{$day}");
            }
        }

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/dashboard/booking-mix')
            ->assertOk()
            ->assertJsonCount(2, 'data.leaders')
            ->assertJsonPath('data.leaders.0.id', $first->id)
            ->assertJsonPath('data.leaders.0.name', 'Same experience')
            ->assertJsonPath('data.leaders.1.id', $second->id)
            ->assertJsonPath('data.leaders.1.name', 'Same experience');
    }

    public function test_it_excludes_soft_deleted_current_and_previous_orders_from_mix_and_change(): void
    {
        $activity = Activity::factory()->create(['name' => 'Live experience']);
        $package = Package::factory()->create(['name' => 'Deleted package']);

        $this->order('activity', $activity->id, '2026-08-01');
        $this->order('activity', $activity->id, '2026-08-02');
        $this->order('activity', $activity->id, '2026-07-01');

        $this->order('activity', $activity->id, '2026-08-03')->delete();
        $this->order('activity', $activity->id, '2026-07-02')->delete();
        $this->order('package', $package->id, '2026-08-04')->delete();

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/dashboard/booking-mix')
            ->assertOk()
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.categories.0.count', 2)
            ->assertJsonPath('data.categories.1.count', 0)
            ->assertJsonPath('data.leaders.0.id', $activity->id)
            ->assertJsonPath('data.leaders.0.bookings', 2)
            ->assertJsonPath('data.leaders.0.change', 100);
    }

    public function test_it_uses_the_missing_name_fallback_and_returns_the_stable_empty_contract(): void
    {
        foreach (range(1, 3) as $day) {
            $this->order('package', 999999, "2026-08-0{$day}");
        }

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/dashboard/booking-mix')
            ->assertOk()
            ->assertJsonPath('data.leaders.0.name', 'Unavailable item');

        Order::withTrashed()->forceDelete();

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/dashboard/booking-mix')
            ->assertExactJson([
                'success' => true,
                'data' => [
                    'total' => 0,
                    'categories' => [
                        ['key' => 'activities', 'label' => 'Activities', 'count' => 0],
                        ['key' => 'packages', 'label' => 'Packages', 'count' => 0],
                        ['key' => 'trips', 'label' => 'Trips', 'count' => 0],
                    ],
                    'leaders' => [],
                ],
            ]);
    }

    public function test_it_returns_the_dashboard_error_contract_when_aggregation_fails(): void
    {
        Log::spy();
        Schema::drop('orders');

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/dashboard/booking-mix')
            ->assertStatus(500)
            ->assertExactJson([
                'success' => false,
                'message' => 'Failed to fetch booking mix',
            ]);

        $this->assertStringNotContainsString('SQL', $response->getContent());
        Log::shouldHaveReceived('error')
            ->once()
            ->with('Failed to fetch booking mix', \Mockery::on(fn (array $context): bool => ($context['exception'] ?? null) instanceof \Throwable));
    }

    private function order(string $type, int $id, string $createdAt, string $status = 'completed'): Order
    {
        return Order::factory()->create([
            'orderable_type' => $type,
            'orderable_id' => $id,
            'status' => $status,
            'created_at' => "{$createdAt} 10:00:00",
            'updated_at' => "{$createdAt} 10:00:00",
        ]);
    }
}
