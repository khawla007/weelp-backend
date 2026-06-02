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
}
