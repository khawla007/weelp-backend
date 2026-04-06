<?php

namespace App\Services;

use App\Models\Itinerary;
use App\Models\ItineraryActivity;
use App\Models\ItineraryAddon;
use App\Models\ItineraryAttribute;
use App\Models\ItineraryBasePricing;
use App\Models\ItineraryCategory;
use App\Models\ItineraryInclusionExclusion;
use App\Models\ItineraryLocation;
use App\Models\ItineraryMediaGallery;
use App\Models\ItinerarySchedule;
use App\Models\ItinerarySeo;
use App\Models\ItineraryTag;
use App\Models\ItineraryTransfer;
use Illuminate\Support\Facades\DB;

class ItineraryDeepCopyService
{
    /**
     * Deep-copy an itinerary with modified schedules.
     *
     * @param  Itinerary  $original        The source itinerary to copy from
     * @param  array      $modifiedSchedules  New schedule data (not copied from original)
     * @param  int|null   $creatorId        Creator user ID (for creator copies)
     * @param  int|null   $userId           Customer user ID (for customer copies)
     * @return Itinerary  The newly created copy with relations loaded
     */
    public function deepCopy(
        Itinerary $original,
        array $modifiedSchedules,
        ?int $creatorId = null,
        ?int $userId = null,
    ): Itinerary {
        return DB::transaction(function () use ($original, $modifiedSchedules, $creatorId, $userId) {
            $copy = $this->createCopyRecord($original, $creatorId, $userId);

            $this->copyRelations($original, $copy);
            $this->createSchedules($copy, $modifiedSchedules);

            return $copy->load([
                'locations',
                'schedules.activities',
                'schedules.transfers',
                'basePricing',
                'inclusionsExclusions',
                'mediaGallery',
            ]);
        });
    }

    private function createCopyRecord(Itinerary $original, ?int $creatorId, ?int $userId): Itinerary
    {
        $suffix = $creatorId ? "-c{$creatorId}" : "-u{$userId}";
        $slug = $original->slug . $suffix . '-' . time();

        return Itinerary::create([
            'name' => $original->name,
            'description' => $original->description,
            'slug' => $slug,
            'featured_itinerary' => false,
            'private_itinerary' => (bool) $userId,
            'creator_id' => $creatorId,
            'user_id' => $userId,
            'parent_itinerary_id' => $original->id,
            'approval_status' => $creatorId ? 'pending_approval' : null,
            'views_count' => 0,
            'likes_count' => 0,
        ]);
    }

    private function copyRelations(Itinerary $original, Itinerary $copy): void
    {
        $this->copyLocations($original, $copy);
        $this->copyBasePricing($original, $copy);
        $this->copyInclusionsExclusions($original, $copy);
        $this->copyMediaGallery($original, $copy);
        $this->copySeo($original, $copy);
        $this->copyCategories($original, $copy);
        $this->copyTags($original, $copy);
        $this->copyAttributes($original, $copy);
        $this->copyAddons($original, $copy);
    }

    private function copyLocations(Itinerary $original, Itinerary $copy): void
    {
        foreach ($original->locations as $location) {
            ItineraryLocation::create([
                'itinerary_id' => $copy->id,
                'city_id' => $location->city_id,
            ]);
        }
    }

    private function copyBasePricing(Itinerary $original, Itinerary $copy): void
    {
        if (! $original->basePricing) {
            return;
        }

        ItineraryBasePricing::create([
            'itinerary_id' => $copy->id,
            'currency' => $original->basePricing->currency,
            'availability' => $original->basePricing->availability,
            'start_date' => $original->basePricing->start_date,
            'end_date' => $original->basePricing->end_date,
        ]);
    }

    private function copyInclusionsExclusions(Itinerary $original, Itinerary $copy): void
    {
        foreach ($original->inclusionsExclusions as $item) {
            ItineraryInclusionExclusion::create([
                'itinerary_id' => $copy->id,
                'type' => $item->type,
                'title' => $item->title,
                'description' => $item->description,
                'included' => $item->included,
            ]);
        }
    }

    private function copyMediaGallery(Itinerary $original, Itinerary $copy): void
    {
        foreach ($original->mediaGallery as $media) {
            ItineraryMediaGallery::create([
                'itinerary_id' => $copy->id,
                'media_id' => $media->media_id,
                'is_featured' => $media->is_featured,
            ]);
        }
    }

    private function copySeo(Itinerary $original, Itinerary $copy): void
    {
        if (! $original->seo) {
            return;
        }

        ItinerarySeo::create([
            'itinerary_id' => $copy->id,
            'meta_title' => $original->seo->meta_title,
            'meta_description' => $original->seo->meta_description,
            'keywords' => $original->seo->keywords,
            'og_image_url' => $original->seo->og_image_url,
            'canonical_url' => $original->seo->canonical_url,
            'schema_type' => $original->seo->schema_type,
            'schema_data' => $original->seo->schema_data,
        ]);
    }

    private function copyCategories(Itinerary $original, Itinerary $copy): void
    {
        foreach ($original->categories as $category) {
            ItineraryCategory::create([
                'itinerary_id' => $copy->id,
                'category_id' => $category->category_id,
            ]);
        }
    }

    private function copyTags(Itinerary $original, Itinerary $copy): void
    {
        foreach ($original->tags as $tag) {
            ItineraryTag::create([
                'itinerary_id' => $copy->id,
                'tag_id' => $tag->tag_id,
            ]);
        }
    }

    private function copyAttributes(Itinerary $original, Itinerary $copy): void
    {
        foreach ($original->attributes as $attribute) {
            ItineraryAttribute::create([
                'itinerary_id' => $copy->id,
                'attribute_id' => $attribute->attribute_id,
                'attribute_value' => $attribute->attribute_value,
            ]);
        }
    }

    private function copyAddons(Itinerary $original, Itinerary $copy): void
    {
        foreach ($original->addons as $addon) {
            ItineraryAddon::create([
                'itinerary_id' => $copy->id,
                'addon_id' => $addon->addon_id,
            ]);
        }
    }

    private function createSchedules(Itinerary $copy, array $schedules): void
    {
        foreach ($schedules as $scheduleData) {
            $schedule = ItinerarySchedule::create([
                'itinerary_id' => $copy->id,
                'day' => $scheduleData['day'],
            ]);

            if (! empty($scheduleData['activities'])) {
                $this->createScheduleActivities($schedule, $scheduleData['activities']);
            }

            if (! empty($scheduleData['transfers'])) {
                $this->createScheduleTransfers($schedule, $scheduleData['transfers']);
            }
        }
    }

    private function createScheduleActivities(ItinerarySchedule $schedule, array $activities): void
    {
        foreach ($activities as $activityData) {
            ItineraryActivity::create([
                'schedule_id' => $schedule->id,
                'activity_id' => $activityData['activity_id'],
                'start_time' => $activityData['start_time'] ?? null,
                'end_time' => $activityData['end_time'] ?? null,
                'notes' => $activityData['notes'] ?? null,
                'price' => $activityData['price'] ?? null,
                'included' => $activityData['included'] ?? true,
            ]);
        }
    }

    private function createScheduleTransfers(ItinerarySchedule $schedule, array $transfers): void
    {
        foreach ($transfers as $transferData) {
            ItineraryTransfer::create([
                'schedule_id' => $schedule->id,
                'transfer_id' => $transferData['transfer_id'],
                'pickup_location' => $transferData['pickup_location'] ?? null,
                'dropoff_location' => $transferData['dropoff_location'] ?? null,
                'start_time' => $transferData['start_time'] ?? null,
                'end_time' => $transferData['end_time'] ?? null,
                'notes' => $transferData['notes'] ?? null,
                'price' => $transferData['price'] ?? null,
                'included' => $transferData['included'] ?? true,
                'pax' => $transferData['pax'] ?? null,
            ]);
        }
    }
}
