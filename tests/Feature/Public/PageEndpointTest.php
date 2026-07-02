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
            'meta_title' => 'Privacy SEO title',
            'schema_data' => ['@context' => 'https://schema.org', '@type' => 'WebPage'],
        ]);

        $this->getJson('/api/pages/privacy-policy')
            ->assertOk()
            ->assertJsonPath('data.slug', 'privacy-policy')
            ->assertJsonPath('data.hero_background_image_url', '/storage/pages/privacy-hero.jpg')
            ->assertJsonPath('data.hero_heading', 'Privacy at Weelp')
            ->assertJsonPath('data.hero_button_url', '/contact')
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
