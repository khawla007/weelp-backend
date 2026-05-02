<?php

namespace Tests\Feature;

use App\Models\Commission;
use App\Models\Itinerary;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CreatorEarningsTest extends TestCase
{
    use RefreshDatabase;

    private function makeCreator(): User
    {
        return User::factory()->create(['is_creator' => true]);
    }

    private function authHeader(User $user): array
    {
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => "Bearer $token"];
    }

    private function makeCommissionFor(User $creator, string $status, float $amount, ?string $createdAt = null): Commission
    {
        $itinerary = Itinerary::factory()->create();
        $order = Order::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'completed',
            'orderable_type' => Itinerary::class,
            'orderable_id' => $itinerary->id,
        ]);
        OrderPayment::factory()->create([
            'order_id' => $order->id,
            'total_amount' => $amount * 10,
            'amount' => $amount * 10,
            'payment_method' => 'credit_card',
        ]);
        $commission = Commission::factory()->create([
            'creator_id' => $creator->id,
            'order_id' => $order->id,
            'commission_amount' => $amount,
            'commission_rate' => 10.0,
            'status' => $status,
        ]);

        if ($createdAt) {
            $commission->created_at = $createdAt;
            $commission->save();
        }

        return $commission;
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/creator/dashboard/earnings')->assertStatus(401);
    }

    public function test_returns_only_own_commissions(): void
    {
        $creatorA = $this->makeCreator();
        $creatorB = $this->makeCreator();
        $this->makeCommissionFor($creatorA, 'paid', 100);
        $this->makeCommissionFor($creatorB, 'paid', 200);

        $res = $this->withHeaders($this->authHeader($creatorA))->getJson('/api/creator/dashboard/earnings');
        $res->assertOk();
        $this->assertCount(1, $res->json('data.rows'));
        $this->assertEquals(100.00, (float) $res->json('data.rows.0.commission_amount'));
    }

    public function test_filters_by_status(): void
    {
        $creator = $this->makeCreator();
        $this->makeCommissionFor($creator, 'paid', 100);
        $this->makeCommissionFor($creator, 'pending', 50);

        $res = $this->withHeaders($this->authHeader($creator))->getJson('/api/creator/dashboard/earnings?status=paid');
        $this->assertCount(1, $res->json('data.rows'));
        $this->assertEquals('paid', $res->json('data.rows.0.status'));
    }

    public function test_filters_by_date_range(): void
    {
        $creator = $this->makeCreator();
        $this->makeCommissionFor($creator, 'paid', 100, '2026-01-15 00:00:00');
        $this->makeCommissionFor($creator, 'paid', 200, '2026-04-15 00:00:00');

        $res = $this->withHeaders($this->authHeader($creator))->getJson('/api/creator/dashboard/earnings?from=2026-04-01&to=2026-04-30');
        $this->assertCount(1, $res->json('data.rows'));
        $this->assertEquals(200.00, (float) $res->json('data.rows.0.commission_amount'));
    }

    public function test_summary_math(): void
    {
        $creator = $this->makeCreator();
        $this->makeCommissionFor($creator, 'paid', 100, '2026-01-15 00:00:00');
        $this->makeCommissionFor($creator, 'paid', 200, '2026-04-15 00:00:00');
        $this->makeCommissionFor($creator, 'pending', 50, '2026-01-01 00:00:00');

        $res = $this->withHeaders($this->authHeader($creator))->getJson('/api/creator/dashboard/earnings?from=2026-04-01&to=2026-04-30');
        $this->assertEquals(350.00, (float) $res->json('data.summary.lifetime'));
        $this->assertEquals(200.00, (float) $res->json('data.summary.current_period'));
        $this->assertEquals(50.00, (float) $res->json('data.summary.pending'));
    }

    public function test_validates_per_page_max(): void
    {
        $creator = $this->makeCreator();
        $this->makeCommissionFor($creator, 'paid', 1);

        $res = $this->withHeaders($this->authHeader($creator))->getJson('/api/creator/dashboard/earnings?per_page=999');
        $res->assertStatus(422);
    }
}
