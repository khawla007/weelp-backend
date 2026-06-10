<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guest_cannot_create_page(): void
    {
        $this->postJson('/api/admin/pages', [
            'title' => 'Privacy Policy',
            'slug' => 'privacy-policy',
            'content' => '{"type":"doc","content":[]}',
            'status' => 'draft',
        ])->assertStatus(401);
    }

    public function test_admin_can_create_show_update_and_delete_page_with_seo(): void
    {
        $admin = $this->admin();
        $largeTiptapJson = json_encode([
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [[
                    'type' => 'text',
                    'text' => str_repeat('Legal policy copy. ', 400),
                ]],
            ]],
        ]);

        $createResponse = $this->actingAs($admin, 'api')->postJson('/api/admin/pages', [
            'title' => 'Privacy Policy',
            'slug' => 'privacy-policy',
            'content' => $largeTiptapJson,
            'excerpt' => 'How Weelp handles privacy.',
            'status' => 'published',
            'published_at' => '2026-06-10 10:00:00',
            'seo' => [
                'meta_title' => 'Privacy SEO title',
                'meta_description' => 'Privacy SEO description',
                'schema_type' => 'WebPage',
                'schema_data' => ['@context' => 'https://schema.org', '@type' => 'WebPage'],
                'head_code' => '<meta name="privacy" content="head">',
            ],
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.title', 'Privacy Policy')
            ->assertJsonPath('data.seo.meta_title', 'Privacy SEO title');

        $page = Page::where('slug', 'privacy-policy')->firstOrFail();
        $this->assertSame($largeTiptapJson, $page->content);
        $this->assertSame('WebPage', $page->schema_type);

        $this->actingAs($admin, 'api')->getJson("/api/admin/pages/{$page->id}")
            ->assertOk()
            ->assertJsonPath('data.slug', 'privacy-policy')
            ->assertJsonPath('data.seo.schema_data.@type', 'WebPage');

        $this->actingAs($admin, 'api')->putJson("/api/admin/pages/{$page->id}", [
            'title' => 'Privacy Policy Updated',
            'status' => 'draft',
            'seo' => [
                'footer_code' => '<script>window.pageFooter=true</script>',
            ],
        ])->assertOk()
            ->assertJsonPath('data.title', 'Privacy Policy Updated')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.seo.footer_code', '<script>window.pageFooter=true</script>');

        $page->refresh();
        $this->assertSame('Privacy SEO title', $page->meta_title);

        $this->actingAs($admin, 'api')->deleteJson("/api/admin/pages/{$page->id}")
            ->assertOk();

        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    public function test_admin_index_lists_pages_and_filters_by_search_and_status(): void
    {
        $admin = $this->admin();

        Page::factory()->create(['title' => 'Privacy Policy', 'slug' => 'privacy-policy', 'status' => 'published']);
        Page::factory()->create(['title' => 'Terms of Service', 'slug' => 'terms', 'status' => 'draft']);

        $this->actingAs($admin, 'api')->getJson('/api/admin/pages?search=privacy&status=published')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'privacy-policy')
            ->assertJsonPath('total', 1);
    }

    public function test_admin_validation_rejects_duplicate_slug_and_invalid_status(): void
    {
        $admin = $this->admin();
        Page::factory()->create(['slug' => 'privacy-policy']);

        $this->actingAs($admin, 'api')->postJson('/api/admin/pages', [
            'title' => 'Privacy Policy',
            'slug' => 'privacy-policy',
            'content' => '{"type":"doc","content":[]}',
            'status' => 'archived',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['slug', 'status']);
    }

    public function test_admin_validation_rejects_empty_rich_text_content(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'api')->postJson('/api/admin/pages', [
            'title' => 'Empty Policy',
            'slug' => 'empty-policy',
            'content' => '{"type":"doc","content":[]}',
            'status' => 'draft',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }
}
