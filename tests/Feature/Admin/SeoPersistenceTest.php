<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\ActivitySeo;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Itinerary;
use App\Models\ItinerarySeo;
use App\Models\Media;
use App\Models\Tag;
use App\Models\Transfer;
use App\Models\TransferSeo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_transfer_seo_update_preserves_existing_row_id(): void
    {
        $transfer = Transfer::factory()->create();
        $seo = TransferSeo::create([
            'transfer_id' => $transfer->id,
            'meta_title' => 'Old transfer title',
            'schema_data' => ['@context' => 'https://schema.org', '@type' => 'Service'],
        ]);

        $this->actingAs($this->adminUser(), 'api')
            ->putJson("/api/admin/transfers/{$transfer->id}", [
                'seo' => [
                    'meta_title' => 'New transfer title',
                    'schema_type' => 'Service',
                    'schema_data' => [
                        '@context' => 'https://schema.org',
                        '@type' => 'Service',
                        'name' => 'New transfer title',
                    ],
                    'head_code' => '<meta name="x-test" content="head">',
                    'body_code' => '<script>window.bodySeo=true</script>',
                    'footer_code' => '<script>window.footerSeo=true</script>',
                ],
            ])
            ->assertOk();

        $seo->refresh();

        $this->assertSame($seo->id, $transfer->seo()->first()?->id);
        $this->assertSame('New transfer title', $seo->meta_title);
        $this->assertSame('New transfer title', $seo->schema_data['name']);
        $this->assertSame('<meta name="x-test" content="head">', $seo->head_code);
        $this->assertSame(1, TransferSeo::where('transfer_id', $transfer->id)->count());
    }

    public function test_activity_create_persists_seo_schema_and_script_slots(): void
    {
        $this->actingAs($this->adminUser(), 'api')
            ->postJson('/api/admin/activities', [
                'name' => 'SEO Activity',
                'slug' => 'seo-activity',
                'description' => 'Activity description',
                'short_description' => 'Short activity description',
                'featured_activity' => false,
                'seo' => [
                    'meta_title' => 'Activity meta title',
                    'meta_description' => 'Activity meta description',
                    'schema_type' => 'Product',
                    'schema_data' => ['@context' => 'https://schema.org', '@type' => 'Product', 'name' => 'SEO Activity'],
                    'head_code' => '<meta name="activity-create" content="head">',
                    'body_code' => '<script>window.activityCreateBody=true</script>',
                    'footer_code' => '<script>window.activityCreateFooter=true</script>',
                ],
            ])
            ->assertCreated();

        $activity = Activity::where('slug', 'seo-activity')->firstOrFail();

        $this->assertDatabaseHas('activity_seo', [
            'activity_id' => $activity->id,
            'meta_title' => 'Activity meta title',
            'schema_type' => 'Product',
            'head_code' => '<meta name="activity-create" content="head">',
            'body_code' => '<script>window.activityCreateBody=true</script>',
            'footer_code' => '<script>window.activityCreateFooter=true</script>',
        ]);
        $this->assertSame('SEO Activity', $activity->seo()->first()?->schema_data['name']);
    }

    public function test_itinerary_create_persists_seo_schema_and_script_slots(): void
    {
        $this->actingAs($this->adminUser(), 'api')
            ->postJson('/api/admin/itineraries', [
                'name' => 'SEO Itinerary',
                'slug' => 'seo-itinerary',
                'description' => 'Itinerary description',
                'featured_itinerary' => false,
                'private_itinerary' => false,
                'seo' => [
                    'meta_title' => 'Itinerary meta title',
                    'meta_description' => 'Itinerary meta description',
                    'schema_type' => 'TouristTrip',
                    'schema_data' => ['@context' => 'https://schema.org', '@type' => 'TouristTrip', 'name' => 'SEO Itinerary'],
                    'head_code' => '<meta name="itinerary-create" content="head">',
                    'body_code' => '<script>window.itineraryCreateBody=true</script>',
                    'footer_code' => '<script>window.itineraryCreateFooter=true</script>',
                ],
            ])
            ->assertCreated();

        $itinerary = Itinerary::where('slug', 'seo-itinerary')->firstOrFail();

        $this->assertDatabaseHas('itinerary_seo', [
            'itinerary_id' => $itinerary->id,
            'meta_title' => 'Itinerary meta title',
            'schema_type' => 'TouristTrip',
            'head_code' => '<meta name="itinerary-create" content="head">',
            'body_code' => '<script>window.itineraryCreateBody=true</script>',
            'footer_code' => '<script>window.itineraryCreateFooter=true</script>',
        ]);
        $this->assertSame('SEO Itinerary', $itinerary->seo()->first()?->schema_data['name']);
    }

    public function test_transfer_create_persists_seo_schema_and_script_slots(): void
    {
        $this->actingAs($this->adminUser(), 'api')
            ->postJson('/api/admin/transfers', [
                'name' => 'SEO Transfer',
                'slug' => 'seo-transfer',
                'description' => 'Transfer description',
                'transfer_type' => 'private',
                'is_vendor' => false,
                'pickup_location' => 'Airport',
                'dropoff_location' => 'Hotel',
                'vehicle_type' => 'Sedan',
                'inclusion' => 'Driver included',
                'base_price' => 100,
                'currency' => 'USD',
                'price_type' => 'per_vehicle',
                'extra_luggage_charge' => 10,
                'waiting_charge' => 5,
                'seo' => [
                    'meta_title' => 'Transfer meta title',
                    'meta_description' => 'Transfer meta description',
                    'schema_type' => 'Service',
                    'schema_data' => ['@context' => 'https://schema.org', '@type' => 'Service', 'name' => 'SEO Transfer'],
                    'head_code' => '<meta name="transfer-create" content="head">',
                    'body_code' => '<script>window.transferCreateBody=true</script>',
                    'footer_code' => '<script>window.transferCreateFooter=true</script>',
                ],
            ])
            ->assertOk();

        $transfer = Transfer::where('slug', 'seo-transfer')->firstOrFail();

        $this->assertDatabaseHas('transfer_seo', [
            'transfer_id' => $transfer->id,
            'meta_title' => 'Transfer meta title',
            'schema_type' => 'Service',
            'head_code' => '<meta name="transfer-create" content="head">',
            'body_code' => '<script>window.transferCreateBody=true</script>',
            'footer_code' => '<script>window.transferCreateFooter=true</script>',
        ]);
        $this->assertSame('SEO Transfer', $transfer->seo()->first()?->schema_data['name']);
    }

    public function test_blog_create_persists_seo_schema_and_script_slots(): void
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $media = Media::create([
            'name' => 'SEO Blog Image',
            'url' => 'blog/seo.jpg',
        ]);

        $this->actingAs($this->adminUser(), 'api')
            ->postJson('/api/admin/blogs', [
                'name' => 'SEO Blog',
                'content' => 'Blog content',
                'excerpt' => 'Blog excerpt',
                'publish' => true,
                'media_gallery' => [['media_id' => $media->id, 'is_featured' => true]],
                'categories' => [$category->id],
                'tags' => [$tag->id],
                'seo' => [
                    'meta_title' => 'Blog meta title',
                    'meta_description' => 'Blog meta description',
                    'schema_type' => 'BlogPosting',
                    'schema_data' => ['@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => 'SEO Blog'],
                    'head_code' => '<meta name="blog-create" content="head">',
                    'body_code' => '<script>window.blogCreateBody=true</script>',
                    'footer_code' => '<script>window.blogCreateFooter=true</script>',
                ],
            ])
            ->assertCreated();

        $blog = Blog::where('name', 'SEO Blog')->firstOrFail();

        $this->assertSame('Blog meta title', $blog->meta_title);
        $this->assertSame('BlogPosting', $blog->schema_type);
        $this->assertSame('SEO Blog', $blog->schema_data['headline']);
        $this->assertSame('<meta name="blog-create" content="head">', $blog->head_code);
        $this->assertSame('<script>window.blogCreateBody=true</script>', $blog->body_code);
        $this->assertSame('<script>window.blogCreateFooter=true</script>', $blog->footer_code);
    }

    public function test_transfer_seo_update_creates_missing_row_once(): void
    {
        $transfer = Transfer::factory()->create();

        $this->actingAs($this->adminUser(), 'api')
            ->putJson("/api/admin/transfers/{$transfer->id}", [
                'seo' => [
                    'meta_title' => 'Created transfer SEO',
                    'schema_data' => ['@context' => 'https://schema.org', '@type' => 'Service'],
                ],
            ])
            ->assertOk();

        $this->actingAs($this->adminUser(), 'api')
            ->putJson("/api/admin/transfers/{$transfer->id}", [
                'seo' => [
                    'meta_title' => 'Updated transfer SEO',
                    'schema_data' => ['@context' => 'https://schema.org', '@type' => 'Service'],
                ],
            ])
            ->assertOk();

        $this->assertSame(1, TransferSeo::where('transfer_id', $transfer->id)->count());
        $this->assertSame('Updated transfer SEO', $transfer->seo()->first()?->meta_title);
    }

    public function test_transfer_partial_seo_update_preserves_omitted_existing_fields(): void
    {
        $transfer = Transfer::factory()->create();
        $seo = TransferSeo::create([
            'transfer_id' => $transfer->id,
            'meta_title' => 'Keep transfer title',
            'meta_description' => 'Keep transfer description',
            'schema_data' => ['@context' => 'https://schema.org', '@type' => 'Service', 'name' => 'Keep schema'],
        ]);

        $this->actingAs($this->adminUser(), 'api')
            ->putJson("/api/admin/transfers/{$transfer->id}", [
                'seo' => [
                    'head_code' => '<meta name="partial" content="head">',
                ],
            ])
            ->assertOk();

        $seo->refresh();

        $this->assertSame('Keep transfer title', $seo->meta_title);
        $this->assertSame('Keep transfer description', $seo->meta_description);
        $this->assertSame('Keep schema', $seo->schema_data['name']);
        $this->assertSame('<meta name="partial" content="head">', $seo->head_code);
    }

    public function test_transfer_script_only_seo_update_can_create_missing_row(): void
    {
        $transfer = Transfer::factory()->create();

        $this->actingAs($this->adminUser(), 'api')
            ->putJson("/api/admin/transfers/{$transfer->id}", [
                'seo' => [
                    'head_code' => '<meta name="script-only" content="head">',
                ],
            ])
            ->assertOk();

        $seo = $transfer->seo()->first();

        $this->assertNotNull($seo);
        $this->assertSame('', $seo->meta_title);
        $this->assertSame('<meta name="script-only" content="head">', $seo->head_code);
        $this->assertSame(1, TransferSeo::where('transfer_id', $transfer->id)->count());
    }

    public function test_itinerary_seo_update_preserves_existing_row_id(): void
    {
        $itinerary = Itinerary::factory()->create();
        $seo = ItinerarySeo::create([
            'itinerary_id' => $itinerary->id,
            'meta_title' => 'Old itinerary title',
            'schema_data' => ['@context' => 'https://schema.org', '@type' => 'TouristTrip'],
        ]);

        $this->actingAs($this->adminUser(), 'api')
            ->putJson("/api/admin/itineraries/{$itinerary->id}", [
                'seo' => [
                    'meta_title' => 'New itinerary title',
                    'schema_type' => 'TouristTrip',
                    'schema_data' => [
                        '@context' => 'https://schema.org',
                        '@type' => 'TouristTrip',
                        'name' => 'New itinerary title',
                    ],
                    'head_code' => '<meta name="itinerary" content="head">',
                    'body_code' => '<script>window.itineraryBodySeo=true</script>',
                    'footer_code' => '<script>window.itineraryFooterSeo=true</script>',
                ],
            ])
            ->assertOk();

        $seo->refresh();

        $this->assertSame($seo->id, $itinerary->seo()->first()?->id);
        $this->assertSame('New itinerary title', $seo->meta_title);
        $this->assertSame('New itinerary title', $seo->schema_data['name']);
        $this->assertSame('<script>window.itineraryFooterSeo=true</script>', $seo->footer_code);
        $this->assertSame(1, ItinerarySeo::where('itinerary_id', $itinerary->id)->count());
    }

    public function test_activity_seo_update_preserves_existing_row_id(): void
    {
        $activity = Activity::factory()->create();
        $seo = ActivitySeo::create([
            'activity_id' => $activity->id,
            'meta_title' => 'Old activity title',
            'schema_data' => ['@context' => 'https://schema.org', '@type' => 'TouristAttraction'],
        ]);

        $this->actingAs($this->adminUser(), 'api')
            ->putJson("/api/admin/activities/{$activity->id}", [
                'seo' => [
                    'meta_title' => 'New activity title',
                    'schema_type' => 'TouristAttraction',
                    'schema_data' => [
                        '@context' => 'https://schema.org',
                        '@type' => 'TouristAttraction',
                        'name' => 'New activity title',
                    ],
                    'head_code' => '<meta name="activity" content="head">',
                    'body_code' => '<script>window.activityBodySeo=true</script>',
                    'footer_code' => '<script>window.activityFooterSeo=true</script>',
                ],
            ])
            ->assertOk();

        $seo->refresh();

        $this->assertSame($seo->id, $activity->seo()->first()?->id);
        $this->assertSame('New activity title', $seo->meta_title);
        $this->assertSame('New activity title', $seo->schema_data['name']);
        $this->assertSame('<meta name="activity" content="head">', $seo->head_code);
        $this->assertSame(1, ActivitySeo::where('activity_id', $activity->id)->count());
    }

    public function test_admin_activity_show_returns_seo_payload_after_update(): void
    {
        $activity = Activity::factory()->create();

        $this->actingAs($this->adminUser(), 'api')
            ->putJson("/api/admin/activities/{$activity->id}", [
                'seo' => [
                    'meta_title' => 'Visible activity SEO title',
                    'meta_description' => 'Visible activity SEO description',
                    'schema_type' => 'Product',
                    'schema_data' => [
                        '@context' => 'https://schema.org',
                        '@type' => 'Product',
                        'name' => 'Visible activity SEO title',
                    ],
                ],
            ])
            ->assertOk();

        $this->actingAs($this->adminUser(), 'api')
            ->getJson("/api/admin/activities/{$activity->id}")
            ->assertOk()
            ->assertJsonPath('seo.meta_title', 'Visible activity SEO title')
            ->assertJsonPath('seo.meta_description', 'Visible activity SEO description')
            ->assertJsonPath('seo.schema_data.name', 'Visible activity SEO title');
    }

    public function test_admin_activity_update_persists_featured_media_in_show_payload(): void
    {
        $activity = Activity::factory()->create();
        $firstMedia = Media::create([
            'name' => 'First activity media',
            'url' => 'activity/first.jpg',
        ]);
        $featuredMedia = Media::create([
            'name' => 'Featured activity media',
            'url' => 'activity/featured.jpg',
        ]);

        $this->actingAs($this->adminUser(), 'api')
            ->putJson("/api/admin/activities/{$activity->id}", [
                'media_gallery' => [
                    ['media_id' => $firstMedia->id, 'is_featured' => false],
                    ['media_id' => $featuredMedia->id, 'is_featured' => true],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('activity_media_gallery', [
            'activity_id' => $activity->id,
            'media_id' => $featuredMedia->id,
            'is_featured' => true,
        ]);

        $this->actingAs($this->adminUser(), 'api')
            ->getJson("/api/admin/activities/{$activity->id}")
            ->assertOk()
            ->assertJsonPath('feature_image', "/api/media/{$featuredMedia->id}")
            ->assertJsonFragment([
                'media_id' => $featuredMedia->id,
                'is_featured' => 1,
            ]);
    }

    public function test_blog_seo_update_persists_on_existing_blog_row(): void
    {
        $blog = Blog::factory()->create([
            'meta_title' => 'Old blog title',
            'schema_data' => ['@context' => 'https://schema.org', '@type' => 'Article'],
        ]);

        $this->actingAs($this->adminUser(), 'api')
            ->putJson("/api/admin/blogs/{$blog->id}", [
                'seo' => [
                    'meta_title' => 'New blog title',
                    'schema_type' => 'Article',
                    'schema_data' => [
                        '@context' => 'https://schema.org',
                        '@type' => 'Article',
                        'headline' => 'New blog title',
                    ],
                    'head_code' => '<meta name="blog" content="head">',
                    'body_code' => '<script>window.blogBodySeo=true</script>',
                    'footer_code' => '<script>window.blogFooterSeo=true</script>',
                ],
            ])
            ->assertOk();

        $blog->refresh();

        $this->assertSame($blog->id, Blog::where('slug', $blog->slug)->first()?->id);
        $this->assertSame('New blog title', $blog->meta_title);
        $this->assertSame('New blog title', $blog->schema_data['headline']);
        $this->assertSame('<script>window.blogBodySeo=true</script>', $blog->body_code);
    }

    public function test_blog_partial_seo_update_preserves_omitted_existing_fields(): void
    {
        $blog = Blog::factory()->create([
            'meta_title' => 'Keep blog title',
            'meta_description' => 'Keep blog description',
            'schema_data' => ['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => 'Keep schema'],
        ]);

        $this->actingAs($this->adminUser(), 'api')
            ->putJson("/api/admin/blogs/{$blog->id}", [
                'seo' => [
                    'footer_code' => '<script>window.partialFooter=true</script>',
                ],
            ])
            ->assertOk();

        $blog->refresh();

        $this->assertSame('Keep blog title', $blog->meta_title);
        $this->assertSame('Keep blog description', $blog->meta_description);
        $this->assertSame('Keep schema', $blog->schema_data['headline']);
        $this->assertSame('<script>window.partialFooter=true</script>', $blog->footer_code);
    }
}
