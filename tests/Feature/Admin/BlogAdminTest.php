<?php

namespace Tests\Feature\Admin;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Media;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class BlogAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        $media = Media::create([
            'name' => 'Blog QA image',
            'alt_text' => 'Blog QA image',
            'url' => 'blog-qa-image.jpg',
        ]);
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        return array_merge([
            'name' => 'Custom Blog Title',
            'slug' => 'custom-blog-slug',
            'content' => '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Blog body"}]}]}',
            'excerpt' => 'Blog excerpt.',
            'publish' => true,
            'media_gallery' => [['media_id' => $media->id, 'is_featured' => true]],
            'categories' => [$category->id],
            'tags' => [$tag->id],
        ], $overrides);
    }

    public function test_admin_show_returns_seo_payload(): void
    {
        $admin = $this->admin();
        $blog = Blog::factory()->create([
            'meta_title' => 'SEO title',
            'schema_data' => ['@type' => 'BlogPosting'],
        ]);

        $this->actingAs($admin, 'api')->getJson("/api/admin/blogs/{$blog->id}")
            ->assertOk()
            ->assertJsonPath('seo.meta_title', 'SEO title')
            ->assertJsonPath('seo.schema_data.@type', 'BlogPosting');
    }

    public function test_admin_index_supports_title_sort_aliases(): void
    {
        $admin = $this->admin();
        Blog::factory()->create(['name' => 'Zulu Guide']);
        Blog::factory()->create(['name' => 'Alpha Guide']);

        $this->actingAs($admin, 'api')->getJson('/api/admin/blogs?sort_by=title_asc')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Alpha Guide');
    }

    public function test_admin_can_create_blog_with_custom_slug(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'api')->postJson('/api/admin/blogs', $this->validPayload([
            'name' => 'Title Does Not Become Slug',
            'slug' => 'manual-blog-slug',
        ]))->assertCreated();

        $this->assertDatabaseHas('blogs', [
            'name' => 'Title Does Not Become Slug',
            'slug' => 'manual-blog-slug',
        ]);
    }

    public function test_admin_can_update_blog_slug_and_duplicate_slug_is_rejected(): void
    {
        $admin = $this->admin();
        $blog = Blog::factory()->create(['slug' => 'original-blog-slug']);
        Blog::factory()->create(['slug' => 'taken-blog-slug']);

        $this->actingAs($admin, 'api')->putJson("/api/admin/blogs/{$blog->id}", [
            'slug' => 'updated-blog-slug',
        ])->assertOk();

        $this->assertDatabaseHas('blogs', [
            'id' => $blog->id,
            'slug' => 'updated-blog-slug',
        ]);

        $this->actingAs($admin, 'api')->putJson("/api/admin/blogs/{$blog->id}", [
            'slug' => 'taken-blog-slug',
        ])->assertStatus(400)
            ->assertJson(fn (AssertableJson $json) => $json->has('slug')->etc());
    }

    public function test_admin_validation_rejects_empty_rich_text_content(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'api')->postJson('/api/admin/blogs', [
            'name' => 'Empty Blog',
            'content' => '{"type":"doc","content":[]}',
            'excerpt' => 'Empty blog excerpt.',
            'publish' => true,
            'media_gallery' => [],
            'categories' => [],
            'tags' => [],
        ])->assertStatus(400)
            ->assertJson(fn (AssertableJson $json) => $json->has('content')->etc());
    }
}
