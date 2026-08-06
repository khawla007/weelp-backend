<?php

namespace Tests\Feature\Admin;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewAdminTest extends TestCase
{
    use RefreshDatabase;

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
