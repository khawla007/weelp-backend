<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Itinerary;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ActivityItineraryFaqSeeder extends Seeder
{
    public function run(): void
    {
        Activity::query()
            ->withCount('faqs')
            ->whereDoesntHave('faqs')
            ->get()
            ->each(function (Activity $activity): void {
                $templates = [
                    [
                        'question' => "What should I bring for {$activity->name}?",
                        'answer' => "Bring comfortable clothing, valid ID, sunscreen, water, and any personal essentials for {$activity->name}.",
                    ],
                    [
                        'question' => "Is {$activity->name} suitable for beginners?",
                        'answer' => "Yes, {$activity->name} is guided and can be adjusted for first-time guests unless a specific skill level is stated.",
                    ],
                    [
                        'question' => "How early should I arrive for {$activity->name}?",
                        'answer' => "Plan to arrive 15 to 20 minutes before the scheduled start time for check-in and briefing.",
                    ],
                    [
                        'question' => "Can I cancel or change {$activity->name}?",
                        'answer' => "Cancellation and change options depend on the booking policy shown before checkout.",
                    ],
                    [
                        'question' => "Is transport included for {$activity->name}?",
                        'answer' => "Transport is included only when pickup or transfer details are listed in the activity inclusions.",
                    ],
                    [
                        'question' => "Do I need prior experience for {$activity->name}?",
                        'answer' => "Most guests can join without prior experience, and the guide will explain the safety steps before the activity starts.",
                    ],
                ];

                $this->createFaqs($activity, $templates);
            });

        Itinerary::query()
            ->withCount('faqs')
            ->whereDoesntHave('faqs')
            ->get()
            ->each(function (Itinerary $itinerary): void {
                $templates = [
                    [
                        'question' => "What is included in {$itinerary->name}?",
                        'answer' => "{$itinerary->name} includes the planned schedule, listed activities or transfers, and any inclusions shown on the itinerary page.",
                    ],
                    [
                        'question' => "Can {$itinerary->name} be customized?",
                        'answer' => "Yes, guests can review the schedule and request adjustments before booking when customization is available.",
                    ],
                    [
                        'question' => "When should I book {$itinerary->name}?",
                        'answer' => "Book as early as possible to secure availability, especially during weekends, holidays, and peak travel dates.",
                    ],
                    [
                        'question' => "Are transfers included in {$itinerary->name}?",
                        'answer' => "Transfers are included when they appear in the itinerary schedule or inclusions section.",
                    ],
                    [
                        'question' => "Can I join {$itinerary->name} with children?",
                        'answer' => "Children can join when the listed activities and transfer capacity support the selected guest count.",
                    ],
                    [
                        'question' => "What happens if an activity changes during {$itinerary->name}?",
                        'answer' => "The team will help adjust the schedule or suggest a suitable alternative when availability changes.",
                    ],
                ];

                $this->createFaqs($itinerary, $templates);
            });
    }

    private function createFaqs(Activity|Itinerary $item, array $templates): void
    {
        foreach (array_values(Arr::random($templates, min(3, count($templates)))) as $index => $faq) {
            $item->faqs()->create([
                'question_number' => $index + 1,
                'question' => $faq['question'],
                'answer' => $faq['answer'],
            ]);
        }
    }
}
