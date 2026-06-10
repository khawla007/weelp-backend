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
            'meta_title' => 'Privacy SEO title',
            'schema_data' => ['@context' => 'https://schema.org', '@type' => 'WebPage'],
        ]);

        $this->getJson('/api/pages/privacy-policy')
            ->assertOk()
            ->assertJsonPath('data.slug', 'privacy-policy')
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
