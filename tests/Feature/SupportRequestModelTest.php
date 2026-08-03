<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SupportRequestModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_request_persists_user_and_item_relationships(): void
    {
        $user = User::factory()->create();
        /** @var Activity $activity */
        $activity = Activity::factory()->create();

        $ticket = SupportRequest::create([
            'client_request_id' => (string) Str::uuid(),
            'reference' => 'WLP-260731-ABC123',
            'user_id' => $user->id,
            'name' => 'Test Traveler',
            'email' => 'traveler@example.com',
            'topic' => 'booking-question',
            'message' => 'Can you help me understand the booking details?',
            'item_type' => 'activity',
            'item_id' => $activity->id,
            'item_title' => $activity->name,
            'city_slug' => 'dubai',
            'item_slug' => $activity->slug,
            'page_url' => "https://weelp.netlify.app/cities/dubai/activities/{$activity->slug}",
            'status' => 'open',
        ]);

        $this->assertTrue($ticket->user->is($user));
        $this->assertTrue($ticket->item->is($activity));
        $this->assertSame('open', $ticket->status);
    }
}
