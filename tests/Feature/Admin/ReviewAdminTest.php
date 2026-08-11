<?php

namespace Tests\Feature\Admin;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReviewAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_review_list_uses_newest_first_stable_ordering_and_iso_created_at(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $middle = Review::factory()->create([
            'created_at' => Carbon::parse('2026-08-09T10:20:30Z'),
        ]);
        $middle->refresh();
        $newestLowerId = Review::factory()->create([
            'created_at' => Carbon::parse('2026-08-10T10:20:30Z'),
        ]);
        $newestLowerId->refresh();
        $oldest = Review::factory()->create([
            'created_at' => Carbon::parse('2026-08-08T10:20:30Z'),
        ]);
        $oldest->refresh();
        $newestHigherId = Review::factory()->create([
            'created_at' => $newestLowerId->created_at,
        ]);
        $newestHigherId->refresh();

        $response = $this->actingAs($admin, 'api')
            ->getJson('/api/admin/reviews?page=1')
            ->assertOk();

        $this->assertSame(
            [
                'ids' => [$newestHigherId->id, $newestLowerId->id, $middle->id, $oldest->id],
                'created_at' => $newestHigherId->created_at->toISOString(),
            ],
            [
                'ids' => array_column($response->json('data'), 'id'),
                'created_at' => $response->json('data.0.created_at'),
            ],
        );
    }

    public function test_admin_can_approve_review_with_null_order_id(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $review = Review::factory()->create([
            'order_id' => null,
            'status' => 'pending',
            'is_featured' => false,
        ]);

        $this->actingAs($admin, 'api')
            ->putJson("/api/admin/reviews/{$review->id}", [
                'order_id' => null,
                'status' => 'approved',
                'is_featured' => true,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'order_id' => null,
            'status' => 'approved',
            'is_featured' => true,
        ]);
    }
}
