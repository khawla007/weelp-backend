<?php

namespace Tests\Feature\Public;

use App\Models\Blog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_blogs_only_returns_published_items(): void
    {
        $published = Blog::factory()->create(['slug' => 'published-blog', 'publish' => true]);
        $draft = Blog::factory()->create(['slug' => 'draft-blog', 'publish' => false]);

        $response = $this->getJson('/api/blogs');

        $response->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonFragment(['id' => $published->id, 'slug' => 'published-blog'])
            ->assertJsonMissing(['id' => $draft->id, 'slug' => 'draft-blog']);
    }

    public function test_show_blog_by_slug(): void
    {
        $blog = Blog::factory()->create(['slug' => 'test-blog', 'publish' => true]);

        $response = $this->getJson('/api/blogs/test-blog');

        $response->assertOk();
    }

    public function test_show_blog_returns_404_for_draft_slug(): void
    {
        Blog::factory()->create(['slug' => 'draft-blog', 'publish' => false]);

        $this->getJson('/api/blogs/draft-blog')->assertNotFound();
    }

    public function test_show_blog_returns_404_for_missing_slug(): void
    {
        $response = $this->getJson('/api/blogs/nonexistent-slug');

        $response->assertNotFound();
    }
}
