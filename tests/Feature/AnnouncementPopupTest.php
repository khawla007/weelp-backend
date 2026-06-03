<?php

namespace Tests\Feature;

use App\Models\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementPopupTest extends TestCase
{
    use RefreshDatabase;

    public function test_popup_endpoint_returns_only_visible_popup_style_no_auth(): void
    {
        Announcement::create(['type' => 'offer', 'title' => 'Coupon', 'message' => 'm', 'is_active' => true, 'display_style' => 'popup', 'image_url' => 'https://cdn/x.jpg', 'coupon_code' => 'SAVE10']);
        Announcement::create(['type' => 'news', 'title' => 'Inline', 'message' => 'm', 'is_active' => true, 'display_style' => 'inline']);
        Announcement::create(['type' => 'update', 'title' => 'Hidden', 'message' => 'm', 'is_active' => false, 'display_style' => 'popup']);

        $res = $this->getJson('/api/announcements/popup');

        $res->assertOk()->assertJsonCount(1, 'data')->assertJsonFragment(['title' => 'Coupon', 'coupon_code' => 'SAVE10']);
        $res->assertJsonStructure(['success', 'data' => [['id', 'type', 'title', 'message', 'link', 'image_url', 'coupon_code', 'display_style', 'created_at']]]);
        $res->assertJsonMissingPath('data.0.is_active');
        $res->assertJsonMissingPath('data.0.publish_at');
    }
}
