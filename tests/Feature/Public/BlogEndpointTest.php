<?php

namespace Tests\Feature\Public;

use App\Models\Blog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_blogs(): void
    {
        Blog::factory()->count(2)->create();

        $response = $this->getJson('/api/blogs');

        $response->assertOk();
    }

    public function test_show_blog_by_slug(): void
    {
        $blog = Blog::factory()->create(['slug' => 'test-blog']);

        $response = $this->getJson('/api/blogs/test-blog');

        $response->assertOk();
    }

    public function test_show_blog_returns_404_for_missing_slug(): void
    {
        $response = $this->getJson('/api/blogs/nonexistent-slug');

        $response->assertNotFound();
    }
}
