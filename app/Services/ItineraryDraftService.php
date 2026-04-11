<?php

namespace App\Services;

use App\Models\Itinerary;
use App\Models\ItineraryActivity;
use App\Models\ItineraryLocation;
use App\Models\ItineraryMeta;
use App\Models\ItinerarySchedule;
use App\Models\ItineraryTransfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ItineraryDraftService
{
    public function createDraft(Itinerary $approved): Itinerary
    {
        return DB::transaction(function () use ($approved) {
            $approved->load(['locations', 'schedules.activities', 'schedules.transfers']);

            $draft = Itinerary::create([
                'name' => $approved->name,
                'slug' => $approved->slug . '-draft-' . time(),
                'description' => $approved->description,
                'featured_itinerary' => false,
                'private_itinerary' => true,
            ]);

            // Create meta row explicitly (not auto-created)
            ItineraryMeta::create([
                'itinerary_id' => $draft->id,
                'status' => 'draft',
                'creator_id' => $approved->creator_id,
                'parent_itinerary_id' => $approved->parent_itinerary_id,
            ]);

            $draft->load('meta');

            foreach ($approved->locations as $location) {
                ItineraryLocation::create([
                    'itinerary_id' => $draft->id,
                    'city_id' => $location->city_id,
                ]);
            }

            foreach ($approved->schedules as $schedule) {
                $newSchedule = ItinerarySchedule::create([
                    'itinerary_id' => $draft->id,
                    'day' => $schedule->day,
                    'title' => $schedule->title,
                ]);

                foreach ($schedule->activities as $activity) {
                    ItineraryActivity::create([
                        'schedule_id' => $newSchedule->id,
                        'activity_id' => $activity->activity_id,
                        'start_time' => $activity->start_time,
                        'end_time' => $activity->end_time,
                        'notes' => $activity->notes,
                        'price' => $activity->price,
                        'included' => $activity->included,
                    ]);
                }

                foreach ($schedule->transfers as $transfer) {
                    ItineraryTransfer::create([
                        'schedule_id' => $newSchedule->id,
                        'transfer_id' => $transfer->transfer_id,
                        'pickup_location' => $transfer->pickup_location,
                        'dropoff_location' => $transfer->dropoff_location,
                        'start_time' => $transfer->start_time,
                        'end_time' => $transfer->end_time,
                        'notes' => $transfer->notes,
                        'price' => $transfer->price,
                        'included' => $transfer->included,
                        'pax' => $transfer->pax,
                    ]);
                }
            }

            $approved->meta->update(['draft_itinerary_id' => $draft->id]);

            return $draft->load(['locations', 'schedules.activities', 'schedules.transfers']);
        });
    }

    public function mergeDraft(Itinerary $approved, Itinerary $draft): Itinerary
    {
        return DB::transaction(function () use ($approved, $draft) {
            $draft->load(['schedules.activities', 'schedules.transfers']);

            $slug = $approved->slug;
            if ($draft->name !== $approved->name) {
                $baseSlug = Str::slug($draft->name);
                $slug = $baseSlug;
                $counter = 1;
                while (Itinerary::where('slug', $slug)->where('id', '!=', $approved->id)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
            }

            $approved->update([
                'name' => $draft->name,
                'description' => $draft->description,
                'slug' => $slug,
            ]);

            $approved->meta->update(['draft_itinerary_id' => null]);

            $approved->schedules()->each(function ($schedule) {
                $schedule->activities()->delete();
                $schedule->transfers()->delete();
                $schedule->delete();
            });

            foreach ($draft->schedules as $schedule) {
                $newSchedule = ItinerarySchedule::create([
                    'itinerary_id' => $approved->id,
                    'day' => $schedule->day,
                    'title' => $schedule->title,
                ]);

                foreach ($schedule->activities as $activity) {
                    ItineraryActivity::create([
                        'schedule_id' => $newSchedule->id,
                        'activity_id' => $activity->activity_id,
                        'start_time' => $activity->start_time,
                        'end_time' => $activity->end_time,
                        'notes' => $activity->notes,
                        'price' => $activity->price,
                        'included' => $activity->included,
                    ]);
                }

                foreach ($schedule->transfers as $transfer) {
                    ItineraryTransfer::create([
                        'schedule_id' => $newSchedule->id,
                        'transfer_id' => $transfer->transfer_id,
                        'pickup_location' => $transfer->pickup_location,
                        'dropoff_location' => $transfer->dropoff_location,
                        'start_time' => $transfer->start_time,
                        'end_time' => $transfer->end_time,
                        'notes' => $transfer->notes,
                        'price' => $transfer->price,
                        'included' => $transfer->included,
                        'pax' => $transfer->pax,
                    ]);
                }
            }

            $this->deleteDraft($draft);

            return $approved->fresh(['schedules.activities', 'schedules.transfers']);
        });
    }

    public function deleteDraft(Itinerary $draft): void
    {
        ItineraryMeta::where('draft_itinerary_id', $draft->id)->update(['draft_itinerary_id' => null]);

        $draft->schedules()->each(function ($schedule) {
            $schedule->activities()->delete();
            $schedule->transfers()->delete();
            $schedule->delete();
        });
        $draft->locations()->delete();
        $draft->delete();
    }
}
