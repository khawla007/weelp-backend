<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\Activity;
use App\Models\Package;
use App\Models\Itinerary;
use Illuminate\Database\Seeder;

class ReviewSnapshotSeeder extends Seeder
{
    public function run(): void
    {
        // Get reviews that don't have snapshots
        $reviews = Review::whereNull('item_name_snapshot')
            ->orWhereNull('item_slug_snapshot')
            ->get();

        $this->command->info("Found {$reviews->count()} reviews to backfill.");

        foreach ($reviews as $review) {
            $item = null;

            switch ($review->item_type) {
                case 'activity':
                    $item = Activity::find($review->item_id);
                    break;
                case 'package':
                    $item = Package::find($review->item_id);
                    break;
                case 'itinerary':
                    $item = Itinerary::find($review->item_id);
                    break;
            }

            if ($item) {
                $review->item_name_snapshot = $item->name;
                $review->item_slug_snapshot = $item->slug;
                $review->saveQuietly(); // Skip updated_at timestamp
                $this->command->info("Backfilled review ID: {$review->id}");
            } else {
                $this->command->warn("Could not find item for review ID: {$review->id}");
            }
        }

        $this->command->info('Review snapshot backfill complete.');
    }
}
