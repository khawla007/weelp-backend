<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RepairMissingMediaAssociations extends Command
{
    protected $signature = 'media:repair-missing-associations
                            {--execute : Insert associations (default is dry-run)}';

    protected $description = 'Restore deterministic MinIO media associations for local seeded records with empty galleries';

    private const TARGETS = [
        'activities' => [
            'parent' => 'activities',
            'pivot' => 'activity_media_gallery',
            'foreign_key' => 'activity_id',
            'timestamps' => true,
        ],
        'itineraries' => [
            'parent' => 'itineraries',
            'pivot' => 'itinerary_media_gallery',
            'foreign_key' => 'itinerary_id',
            'timestamps' => true,
        ],
        'packages' => [
            'parent' => 'packages',
            'pivot' => 'package_media_gallery',
            'foreign_key' => 'package_id',
            'timestamps' => true,
        ],
        'transfers' => [
            'parent' => 'transfers',
            'pivot' => 'transfer_media_gallery',
            'foreign_key' => 'transfer_id',
            'timestamps' => true,
        ],
        'blogs' => [
            'parent' => 'blogs',
            'pivot' => 'blog_media_gallery',
            'foreign_key' => 'blog_id',
            'timestamps' => false,
        ],
    ];

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('This command is only available in local and testing environments.');

            return self::FAILURE;
        }

        $mediaCandidates = Media::query()
            ->orderBy('id')
            ->limit(161)
            ->get();

        try {
            $disk = Storage::disk('minio');
            $mediaIds = $mediaCandidates
                ->filter(fn (Media $media): bool => $disk->exists((string) $media->getRawOriginal('url')))
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->values();
        } catch (Throwable) {
            $this->error('Unable to inspect MinIO media.');

            return self::FAILURE;
        }

        $this->line(sprintf('Usable MinIO media: %d', $mediaIds->count()));

        if ($mediaIds->count() < 3) {
            $this->error('The repair requires at least 3 usable MinIO media objects.');

            return self::FAILURE;
        }

        $missingIds = collect(self::TARGETS)->mapWithKeys(function (array $target, string $type): array {
            $ids = DB::table($target['parent'])
                ->whereNotExists(function ($query) use ($target) {
                    $query->selectRaw('1')
                        ->from($target['pivot'])
                        ->whereColumn(
                            "{$target['pivot']}.{$target['foreign_key']}",
                            "{$target['parent']}.id",
                        );
                })
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->values();

            $this->line(sprintf('%s: %d missing records', $type, $ids->count()));

            return [$type => $ids];
        });

        $plannedRows = $missingIds->sum(fn (Collection $ids): int => $ids->count() * 3);

        if (! $this->option('execute')) {
            $this->warn(sprintf('Dry-run mode — pass --execute to insert %d media associations.', $plannedRows));

            return self::SUCCESS;
        }

        $inserted = DB::transaction(function () use ($mediaIds, $missingIds): int {
            $insertedRows = 0;

            foreach ($missingIds as $type => $parentIds) {
                $target = self::TARGETS[$type];

                foreach ($parentIds as $parentId) {
                    $now = now();
                    $rows = collect($this->selectMediaIds($type, $parentId, $mediaIds))
                        ->map(function (int $mediaId, int $index) use ($target, $parentId, $now): array {
                            $row = [
                                $target['foreign_key'] => $parentId,
                                'media_id' => $mediaId,
                                'is_featured' => $index === 0,
                            ];

                            if ($target['timestamps']) {
                                $row['created_at'] = $now;
                                $row['updated_at'] = $now;
                            }

                            return $row;
                        })
                        ->all();

                    DB::table($target['pivot'])->insert($rows);
                    $insertedRows += count($rows);
                }
            }

            return $insertedRows;
        });

        $this->info(sprintf('Inserted %d media associations.', $inserted));

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, int>  $mediaIds
     * @return array<int, int>
     */
    private function selectMediaIds(string $type, int $parentId, Collection $mediaIds): array
    {
        $offset = hexdec(substr(hash('sha256', "{$type}:{$parentId}"), 0, 8)) % $mediaIds->count();

        return [
            $mediaIds[$offset],
            $mediaIds[($offset + 1) % $mediaIds->count()],
            $mediaIds[($offset + 2) % $mediaIds->count()],
        ];
    }
}
