<?php

namespace App\Console\Commands;

use App\Models\Itinerary;
use App\Services\CreatorItineraryLifecycleService;
use Illuminate\Console\Command;

class PruneTrashedCreatorItineraries extends Command
{
    protected $signature = 'itineraries:prune-trash
                            {--execute : Permanently delete expired Trash items}
                            {--days=30 : Retention period in days}';

    protected $description = 'List or permanently delete expired creator itinerary Trash items';

    public function handle(CreatorItineraryLifecycleService $lifecycle): int
    {
        $days = (int) $this->option('days');
        if ($days < 1) {
            $this->error('The retention period must be at least one day.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $ids = Itinerary::onlyTrashed()
            ->standaloneCreator()
            ->where('deleted_at', '<=', $cutoff)
            ->orderBy('id')
            ->pluck('id');

        if (! $this->option('execute')) {
            $this->info("Dry run: {$ids->count()} creator itinerary(s) eligible for permanent deletion.");
            $ids->each(fn (int $id) => $this->line((string) $id));

            return self::SUCCESS;
        }

        $deleted = 0;
        foreach ($ids as $id) {
            if ($lifecycle->forceDelete((int) $id, $cutoff)) {
                $deleted++;
            }
        }

        $this->info("Permanently deleted {$deleted} creator itinerary(s).");

        return self::SUCCESS;
    }
}
