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
            'hero_background_image_url' => '/storage/pages/privacy-hero.jpg',
            'hero_heading' => 'Privacy at Weelp',
            'hero_text' => 'Understand how Weelp handles your information.',
            'hero_button_label' => 'Contact support',
            'hero_button_url' => '/contact',
            'hero_overlay_color' => '#000000',
            'hero_overlay_opacity' => 0.5,
            'hero_content_vertical_position' => 'middle',
            'hero_heading_size' => '56px',
            'hero_heading_color' => '#ffffff',
            'hero_heading_align' => 'center',
            'hero_heading_bold' => true,
            'hero_heading_italic' => true,
            'hero_heading_underline' => false,
            'hero_text_size' => '20px',
            'hero_text_color' => '#f8fafc',
            'hero_text_align' => 'center',
            'hero_text_bold' => false,
            'hero_text_italic' => true,
            'hero_text_underline' => false,
            'hero_button_radius' => '999px',
            'hero_button_border_width' => '2px',
            'hero_button_padding' => '14px 28px',
            'hero_button_margin' => '24px 0 0',
            'hero_button_text_color' => '#111827',
            'hero_button_bg_color' => '#ffffff',
            'hero_button_border_color' => '#ffffff',
            'hero_button_text_size' => '16px',
            'hero_button_align' => 'center',
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
            ->assertJsonPath('data.hero_background_image_url', '/storage/pages/privacy-hero.jpg')
            ->assertJsonPath('data.hero_heading', 'Privacy at Weelp')
            ->assertJsonPath('data.hero_text', 'Understand how Weelp handles your information.')
            ->assertJsonPath('data.hero_button_label', 'Contact support')
            ->assertJsonPath('data.hero_button_url', '/contact')
            ->assertJsonPath('data.hero_overlay_color', '#000000')
            ->assertJsonPath('data.hero_overlay_opacity', 0.5)
            ->assertJsonPath('data.hero_content_vertical_position', 'middle')
            ->assertJsonPath('data.hero_heading_size', '56px')
            ->assertJsonPath('data.hero_heading_align', 'center')
            ->assertJsonPath('data.hero_heading_bold', true)
            ->assertJsonPath('data.hero_text_italic', true)
            ->assertJsonPath('data.hero_button_radius', '999px')
            ->assertJsonPath('data.hero_button_align', 'center')
            ->assertJsonPath('data.seo.meta_title', 'Privacy SEO title');

        $page = Page::where('slug', 'privacy-policy')->firstOrFail();
        $this->assertSame($largeTiptapJson, $page->content);
        $this->assertSame('WebPage', $page->schema_type);
        $this->assertSame('/storage/pages/privacy-hero.jpg', $page->hero_background_image_url);
        $this->assertSame('Privacy at Weelp', $page->hero_heading);
        $this->assertSame('Understand how Weelp handles your information.', $page->hero_text);
        $this->assertSame('Contact support', $page->hero_button_label);
        $this->assertSame('/contact', $page->hero_button_url);
        $this->assertSame('#000000', $page->hero_overlay_color);
        $this->assertSame(0.5, $page->hero_overlay_opacity);
        $this->assertSame('center', $page->hero_heading_align);
        $this->assertTrue($page->hero_heading_bold);
        $this->assertTrue($page->hero_text_italic);
        $this->assertSame('999px', $page->hero_button_radius);

        $this->actingAs($admin, 'api')->getJson("/api/admin/pages/{$page->id}")
            ->assertOk()
            ->assertJsonPath('data.slug', 'privacy-policy')
            ->assertJsonPath('data.hero_background_image_url', '/storage/pages/privacy-hero.jpg')
            ->assertJsonPath('data.hero_heading', 'Privacy at Weelp')
            ->assertJsonPath('data.hero_text', 'Understand how Weelp handles your information.')
            ->assertJsonPath('data.hero_button_label', 'Contact support')
            ->assertJsonPath('data.hero_button_url', '/contact')
            ->assertJsonPath('data.hero_overlay_color', '#000000')
            ->assertJsonPath('data.hero_heading_color', '#ffffff')
            ->assertJsonPath('data.hero_button_text_size', '16px')
            ->assertJsonPath('data.seo.schema_data.@type', 'WebPage');

        $this->actingAs($admin, 'api')->putJson("/api/admin/pages/{$page->id}", [
            'title' => 'Privacy Policy Updated',
            'status' => 'draft',
            'hero_heading' => 'Privacy Updated',
            'hero_button_label' => null,
            'hero_overlay_opacity' => 0.7,
            'hero_heading_align' => 'right',
            'hero_heading_underline' => true,
            'hero_button_align' => 'right',
            'seo' => [
                'footer_code' => '<script>window.pageFooter=true</script>',
            ],
        ])->assertOk()
            ->assertJsonPath('data.title', 'Privacy Policy Updated')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.hero_heading', 'Privacy Updated')
            ->assertJsonPath('data.hero_button_label', null)
            ->assertJsonPath('data.hero_overlay_opacity', 0.7)
            ->assertJsonPath('data.hero_heading_align', 'right')
            ->assertJsonPath('data.hero_heading_underline', true)
            ->assertJsonPath('data.hero_button_align', 'right')
            ->assertJsonPath('data.seo.footer_code', '<script>window.pageFooter=true</script>');

        $page->refresh();
        $this->assertSame('Privacy SEO title', $page->meta_title);
        $this->assertSame('Privacy Updated', $page->hero_heading);
        $this->assertNull($page->hero_button_label);
        $this->assertSame(0.7, $page->hero_overlay_opacity);
        $this->assertSame('right', $page->hero_heading_align);
        $this->assertTrue($page->hero_heading_underline);
        $this->assertSame('right', $page->hero_button_align);

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

    public function test_admin_index_returns_max_three_pages_per_page(): void
    {
        $admin = $this->admin();
        Page::factory()->count(5)->create();

        $response = $this->actingAs($admin, 'api')->getJson('/api/admin/pages?per_page=10');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('per_page', 3)
            ->assertJsonPath('total', 5);
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
