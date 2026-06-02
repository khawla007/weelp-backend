<?php

namespace Tests\Feature;

use App\Models\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementPublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_visible_scope_filters_inactive_future_and_expired(): void
    {
        $shown = Announcement::create([
            'type' => 'offer', 'title' => 'Live', 'message' => 'm', 'is_active' => true,
        ]);
        Announcement::create([
            'type' => 'update', 'title' => 'Inactive', 'message' => 'm', 'is_active' => false,
        ]);
        Announcement::create([
            'type' => 'news', 'title' => 'Future', 'message' => 'm', 'is_active' => true,
            'publish_at' => now()->addDay(),
        ]);
        Announcement::create([
            'type' => 'offer', 'title' => 'Expired', 'message' => 'm', 'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);

        $ids = Announcement::visible()->pluck('id')->all();

        $this->assertSame([$shown->id], $ids);
    }

    public function test_public_endpoint_returns_only_visible_without_auth(): void
    {
        Announcement::create([
            'type' => 'offer', 'title' => 'Live offer', 'message' => 'Save 20%',
            'link' => 'https://example.com/deal', 'is_active' => true,
        ]);
        Announcement::create([
            'type' => 'update', 'title' => 'Hidden', 'message' => 'm', 'is_active' => false,
        ]);

        $response = $this->getJson('/api/announcements');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Live offer'])
            ->assertJsonMissing(['title' => 'Hidden']);

        $response->assertJsonStructure([
            'success',
            'data' => [
                ['id', 'type', 'title', 'message', 'link', 'created_at'],
            ],
        ]);
        $response->assertJsonMissingPath('data.0.is_active');
        $response->assertJsonMissingPath('data.0.publish_at');
        $response->assertJsonMissingPath('data.0.expires_at');
        $response->assertJsonMissingPath('data.0.created_by');
    }
}
