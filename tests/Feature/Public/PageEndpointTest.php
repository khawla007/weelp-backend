<?php

namespace Tests\Feature\Public;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_published_page_by_slug(): void
    {
        Page::factory()->create([
            'title' => 'Privacy Policy',
            'slug' => 'privacy-policy',
            'status' => 'published',
            'content' => '{"type":"doc","content":[]}',
            'hero_background_image_url' => '/storage/pages/privacy-hero.jpg',
            'hero_heading' => 'Privacy at Weelp',
            'hero_text' => 'Understand how Weelp handles your information.',
            'hero_button_label' => 'Contact support',
            'hero_button_url' => '/contact',
            'hero_overlay_color' => '#111827',
            'hero_overlay_opacity' => 0.35,
            'hero_content_vertical_position' => 'bottom',
            'hero_heading_size' => '48px',
            'hero_heading_color' => '#ffffff',
            'hero_heading_align' => 'left',
            'hero_heading_bold' => true,
            'hero_text_size' => '18px',
            'hero_text_color' => '#f8fafc',
            'hero_button_radius' => '12px',
            'hero_button_text_color' => '#ffffff',
            'hero_button_bg_color' => '#111827',
            'hero_button_align' => 'left',
            'meta_title' => 'Privacy SEO title',
            'schema_data' => ['@context' => 'https://schema.org', '@type' => 'WebPage'],
        ]);

        $this->getJson('/api/pages/privacy-policy')
            ->assertOk()
            ->assertJsonPath('data.slug', 'privacy-policy')
            ->assertJsonPath('data.hero_background_image_url', '/storage/pages/privacy-hero.jpg')
            ->assertJsonPath('data.hero_heading', 'Privacy at Weelp')
            ->assertJsonPath('data.hero_button_url', '/contact')
            ->assertJsonPath('data.hero_overlay_color', '#111827')
            ->assertJsonPath('data.hero_overlay_opacity', 0.35)
            ->assertJsonPath('data.hero_content_vertical_position', 'bottom')
            ->assertJsonPath('data.hero_heading_size', '48px')
            ->assertJsonPath('data.hero_heading_bold', true)
            ->assertJsonPath('data.hero_button_bg_color', '#111827')
            ->assertJsonPath('data.hero_button_align', 'left')
            ->assertJsonPath('data.seo.meta_title', 'Privacy SEO title')
            ->assertJsonPath('data.seo.schema_data.@type', 'WebPage');
    }

    public function test_show_draft_page_returns_404(): void
    {
        Page::factory()->create([
            'slug' => 'terms',
            'status' => 'draft',
        ]);

        $this->getJson('/api/pages/terms')->assertNotFound();
    }

    public function test_show_missing_page_returns_404(): void
    {
        $this->getJson('/api/pages/missing-page')->assertNotFound();
    }
}
