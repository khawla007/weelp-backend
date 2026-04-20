<?php

namespace App\Console\Commands;

use App\Models\Activity;
use Illuminate\Console\Command;

class SetFeaturedActivityImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activities:set-featured-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the first image as is_featured for all activities that do not have a featured image';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to set featured images for activities...');

        $activities = Activity::with('mediaGallery')->get();

        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($activities as $activity) {
            $mediaGallery = $activity->mediaGallery;

            if ($mediaGallery->isEmpty()) {
                $this->warn("Activity ID {$activity->id} ({$activity->name}) has no media gallery items. Skipping.");
                $skippedCount++;

                continue;
            }

            // Check if already has a featured image
            $hasFeatured = $mediaGallery->contains('is_featured', true);

            if ($hasFeatured) {
                $this->line("Activity ID {$activity->id} ({$activity->name}) already has a featured image. Skipping.");
                $skippedCount++;

                continue;
            }

            // Get the first media item (order by id ascending)
            $firstMedia = $mediaGallery->first();

            if ($firstMedia) {
                $firstMedia->update(['is_featured' => true]);
                $updatedCount++;
                $this->info("✓ Set featured image for Activity ID {$activity->id} ({$activity->name})");
            }
        }

        $this->newLine();
        $this->info('--------------------------------------------------');
        $this->info('Completed!');
        $this->info("Updated: {$updatedCount} activities");
        $this->info("Skipped: {$skippedCount} activities");
        $this->info('--------------------------------------------------');

        return Command::SUCCESS;
    }
}
