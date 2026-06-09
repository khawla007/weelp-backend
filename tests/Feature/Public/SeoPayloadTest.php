<?php

namespace Tests\Feature\Public;

use App\Models\Activity;
use App\Models\ActivityFaq;
use App\Models\ActivitySeo;
use App\Models\Blog;
use App\Models\Itinerary;
use App\Models\ItineraryFaq;
use App\Models\ItinerarySeo;
use App\Models\Review;
use App\Models\Transfer;
use App\Models\TransferSeo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_activity_show_returns_seo_payload(): void
    {
        $activity = Activity::factory()->create(['slug' => 'seo-activity']);
        ActivitySeo::create([
            'activity_id' => $activity->id,
            'meta_title' => 'Activity SEO title',
            'schema_type' => 'TouristAttraction',
            'schema_data' => ['@context' => 'https://schema.org', '@type' => 'TouristAttraction'],
            'head_code' => '<meta name="x-test" content="head">',
            'body_code' => '<script>window.activityBodySeo=true</script>',
            'footer_code' => '<script>window.activityFooterSeo=true</script>',
        ]);
        Review::factory()->create([
            'user_id' => User::factory()->create(['name' => 'Activity Reviewer'])->id,
            'item_type' => 'activity',
            'item_id' => $activity->id,
            'rating' => 5,
            'review_text' => 'Excellent activity.',
            'status' => 'approved',
        ]);
        ActivityFaq::create([
            'activity_id' => $activity->id,
            'question_number' => 1,
            'question' => 'Is pickup included?',
            'answer' => 'Yes, pickup is included.',
        ]);

        $this->getJson('/api/activities/seo-activity')
            ->assertOk()
            ->assertJsonPath('data.seo.meta_title', 'Activity SEO title')
            ->assertJsonPath('data.seo.schema_data.@type', 'TouristAttraction')
            ->assertJsonPath('data.seo.head_code', '<meta name="x-test" content="head">')
            ->assertJsonPath('data.faqs.0.question', 'Is pickup included?')
            ->assertJsonPath('data.reviews.0.review_text', 'Excellent activity.')
            ->assertJsonPath('data.reviews.0.user_name', 'Activity Reviewer');
    }

    public function test_public_itinerary_show_returns_seo_payload_with_script_slots(): void
    {
        $itinerary = Itinerary::factory()->create(['slug' => 'seo-itinerary']);
        ItinerarySeo::create([
            'itinerary_id' => $itinerary->id,
            'meta_title' => 'Itinerary SEO title',
            'schema_type' => 'TouristTrip',
            'schema_data' => ['@context' => 'https://schema.org', '@type' => 'TouristTrip'],
            'head_code' => '<meta name="itinerary" content="head">',
            'body_code' => '<script>window.itineraryBodySeo=true</script>',
            'footer_code' => '<script>window.itineraryFooterSeo=true</script>',
        ]);
        ItineraryFaq::create([
            'itinerary_id' => $itinerary->id,
            'question_number' => 1,
            'question' => 'Can this itinerary be customized?',
            'answer' => 'Yes, customization is available.',
        ]);
        Review::factory()->create([
            'user_id' => User::factory()->create(['name' => 'Itinerary Reviewer'])->id,
            'item_type' => 'itinerary',
            'item_id' => $itinerary->id,
            'rating' => 4,
            'review_text' => 'Well planned itinerary.',
            'status' => 'approved',
        ]);

        $this->getJson('/api/itineraries/seo-itinerary')
            ->assertOk()
            ->assertJsonPath('data.seo.meta_title', 'Itinerary SEO title')
            ->assertJsonPath('data.seo.schema_data.@type', 'TouristTrip')
            ->assertJsonPath('data.seo.footer_code', '<script>window.itineraryFooterSeo=true</script>')
            ->assertJsonPath('data.faqs.0.question', 'Can this itinerary be customized?')
            ->assertJsonPath('data.reviews.0.review_text', 'Well planned itinerary.')
            ->assertJsonPath('data.review_summary.total_reviews', 1);
    }

    public function test_public_transfer_show_returns_seo_payload(): void
    {
        $transfer = Transfer::factory()->create();
        TransferSeo::create([
            'transfer_id' => $transfer->id,
            'meta_title' => 'Transfer SEO title',
            'schema_type' => 'Service',
            'schema_data' => ['@context' => 'https://schema.org', '@type' => 'Service'],
            'head_code' => '<meta name="transfer" content="head">',
            'body_code' => '<script>window.transferBodySeo=true</script>',
            'footer_code' => '<script>window.transferFooterSeo=true</script>',
        ]);

        $this->getJson("/api/transfers/{$transfer->id}")
            ->assertOk()
            ->assertJsonPath('data.seo.meta_title', 'Transfer SEO title')
            ->assertJsonPath('data.seo.schema_data.@type', 'Service')
            ->assertJsonPath('data.seo.body_code', '<script>window.transferBodySeo=true</script>');
    }

    public function test_public_blog_show_returns_seo_payload(): void
    {
        Blog::factory()->create([
            'slug' => 'seo-blog',
            'meta_title' => 'Blog SEO title',
            'schema_type' => 'Article',
            'schema_data' => ['@context' => 'https://schema.org', '@type' => 'Article'],
            'head_code' => '<meta name="blog" content="head">',
            'body_code' => '<script>window.blogBodySeo=true</script>',
            'footer_code' => '<script>window.blogFooterSeo=true</script>',
        ]);

        $this->getJson('/api/blogs/seo-blog')
            ->assertOk()
            ->assertJsonPath('seo.meta_title', 'Blog SEO title')
            ->assertJsonPath('seo.schema_data.@type', 'Article')
            ->assertJsonPath('seo.head_code', '<meta name="blog" content="head">');
    }
}
