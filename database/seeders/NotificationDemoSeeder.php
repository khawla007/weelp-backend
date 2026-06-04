<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Media;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo data for manually testing the notification system.
 *
 * Seeds one notification of every rendering path so a reviewer can exercise the
 * whole feature from a single login:
 *   - personal notifications (user_notifications): inline vs popup tiers, the
 *     custom admin-authored types (coupon Copy-code, image View-details, plain
 *     internal CTA) and the system types whose CTA is derived from `type`
 *     (application_*, itinerary_*, new_booking)
 *   - site-wide announcements: inline rows + popup tiers (guest coupon, maintenance)
 *
 * Target defaults to atul (customer + creator test account). Override the email
 * with the NOTIF_DEMO_EMAIL env var. Image URLs come from the first two Media
 * rows so this composes with MediaSeeder; if Media is empty, images are omitted.
 *
 * Re-running wipes the target user's personal notifications and ALL announcements
 * first, so it is idempotent and safe to run repeatedly during testing.
 */
class NotificationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('NOTIF_DEMO_EMAIL', 'atul@fanaticcoders.com');

        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->command->warn("NotificationDemoSeeder: user {$email} not found — skipped.");

            return;
        }

        $adminId = User::where('role', 'admin')->value('id') ?? $user->id;

        // Snapshot Media URLs the same way the admin compose path does; tolerate
        // an empty Media table by falling back to no image.
        $mediaUrls = Media::query()->orderBy('id')->take(3)->pluck('url')->all();
        $img1 = $mediaUrls[0] ?? null;
        $img2 = $mediaUrls[1] ?? $img1;
        $img3 = $mediaUrls[2] ?? $img2;

        // ---- Clean slate (idempotent re-runs) ----
        $deletedNotifs = Notification::where('user_id', $user->id)->delete();
        $deletedAnnouncements = Announcement::query()->delete();
        $user->forceFill(['notifications_last_seen_at' => null])->save(); // all unread

        $now = now();
        foreach ($this->personalNotifications($adminId, $img1, $img2) as $p) {
            Notification::create(array_merge($p, [
                'user_id' => $user->id,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        foreach ($this->announcements($adminId, $img1, $img3) as $a) {
            Announcement::create(array_merge($a, [
                'is_active' => true,
                'publish_at' => null,
                'expires_at' => null,
                'created_by' => $adminId,
            ]));
        }

        $this->command->info("NotificationDemoSeeder: target {$email} (id {$user->id}).");
        $this->command->info("  cleared {$deletedNotifs} personal notif(s) + {$deletedAnnouncements} announcement(s).");
        $this->command->info('  seeded '.Notification::where('user_id', $user->id)->count().' personal notif(s) + '.Announcement::count().' announcement(s).');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function personalNotifications(int $adminId, ?string $img1, ?string $img2): array
    {
        $couponData = ['coupon_code' => 'ATUL50'];
        if ($img1) {
            $couponData = ['images' => [$img1]] + $couponData;
        }
        $imageData = $img2 ? ['images' => [$img2]] : null;

        return [
            // custom inline (admin-authored) + internal CTA
            [
                'type' => 'custom', 'display_style' => 'inline', 'created_by' => $adminId,
                'title' => 'Welcome to Weelp',
                'message' => 'Thanks for joining Weelp! Start exploring hand-picked activities and build your first itinerary. Tap below to see a featured experience.',
                'action_url' => '/cities/dubai/activities/desert-safari',
                'data' => null,
            ],
            // custom popup + coupon (Copy-code button)
            [
                'type' => 'custom', 'display_style' => 'popup', 'created_by' => $adminId,
                'title' => 'Personal Coupon Just for You',
                'message' => 'Enjoy 50% off your next booking as a thank-you. Use the code below at checkout.',
                'action_url' => null,
                'data' => $couponData,
            ],
            // custom popup + image, no coupon (View-details CTA)
            [
                'type' => 'custom', 'display_style' => 'popup', 'created_by' => $adminId,
                'title' => 'New Feature: Itinerary Builder',
                'message' => 'We just launched a redesigned itinerary builder. Plan multi-day trips faster with drag-and-drop days and live pricing.',
                'action_url' => '/cities/paris/packages/romantic-paris-tour',
                'data' => $imageData,
            ],
            // system: application_approved (-> application-status CTA)
            [
                'type' => 'application_approved', 'display_style' => 'inline', 'created_by' => null,
                'title' => 'Creator Application Approved',
                'message' => 'Congratulations! Your creator application has been approved. You can now publish activities and itineraries.',
                'action_url' => null, 'data' => null,
            ],
            // system: application_rejected
            [
                'type' => 'application_rejected', 'display_style' => 'inline', 'created_by' => null,
                'title' => 'Application Needs More Information',
                'message' => 'Your creator application could not be approved yet. Please review the requirements and resubmit with the missing details.',
                'action_url' => null, 'data' => null,
            ],
            // system: itinerary_approved (-> my-itineraries CTA)
            [
                'type' => 'itinerary_approved', 'display_style' => 'inline', 'created_by' => null,
                'title' => 'Itinerary Published',
                'message' => "Your itinerary 'Luxury Safari in Kenya' has passed review and is now live for travellers to book.",
                'action_url' => null, 'data' => null,
            ],
            // system: new_booking (-> earnings CTA)
            [
                'type' => 'new_booking', 'display_style' => 'inline', 'created_by' => null,
                'title' => 'You Have a New Booking',
                'message' => 'A traveller just booked your Desert Safari activity for 2 guests. Check your earnings for details.',
                'action_url' => null, 'data' => null,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function announcements(int $adminId, ?string $img1, ?string $img3): array
    {
        return [
            // ---- VISIT-target announcements (have a page link, click Visit → navigate) ----
            // Item in discount (sale on a specific activity)
            [
                'type' => 'offer', 'display_style' => 'inline',
                'title' => 'Summer Sale: Desert Safari', 'message' => 'Up to 30% off our most-booked Dubai activity this week.',
                'link' => '/cities/dubai/activities/desert-safari', 'image_url' => null, 'coupon_code' => null,
            ],
            // New destination added
            [
                'type' => 'news', 'display_style' => 'inline',
                'title' => 'New Destination: Marseille', 'message' => 'Marseille just landed on Weelp — itineraries, activities, transfers all live.',
                'link' => '/cities/marseille', 'image_url' => null, 'coupon_code' => null,
            ],
            // New item / package added
            [
                'type' => 'news', 'display_style' => 'inline',
                'title' => 'New Package: Romantic Paris Tour', 'message' => 'A curated 3-day Paris package is now bookable. Check it out.',
                'link' => '/cities/paris/packages/romantic-paris-tour', 'image_url' => null, 'coupon_code' => null,
            ],
            // ---- DETAIL-target announcements (no page link OR popup OR coupon → View detail opens modal) ----
            // New feature announcement (no specific page link → View detail)
            [
                'type' => 'update', 'display_style' => 'inline',
                'title' => 'New Feature: Smarter Search', 'message' => 'We rolled out a faster, semantic search across activities, packages, and itineraries. Try it from any destination page.',
                'link' => null, 'image_url' => null, 'coupon_code' => null,
            ],
            // Site-wide guest coupon popup (image + Copy code)
            [
                'type' => 'offer', 'display_style' => 'popup',
                'title' => 'Welcome Offer for Everyone', 'message' => 'New here? Take 20% off your first booking with the code below.',
                'link' => null, 'image_url' => $img1, 'coupon_code' => 'WELCOME20',
            ],
            // Maintenance popup (informational + hero image, no CTA)
            [
                'type' => 'update', 'display_style' => 'popup',
                'title' => 'Scheduled Maintenance', 'message' => 'Weelp will be briefly unavailable 02:00-03:00 UTC tonight for routine maintenance. Bookings in progress will be paused and resumed automatically.',
                'link' => null, 'image_url' => $img3, 'coupon_code' => null,
            ],
        ];
    }
}
