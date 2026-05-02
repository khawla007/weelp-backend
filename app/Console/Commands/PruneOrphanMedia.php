<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PruneOrphanMedia extends Command
{
    protected $signature = 'media:prune-orphans
                            {--execute : Perform the deletes (default is dry-run)}
                            {--days=7 : Only consider media older than N days}';

    protected $description = 'Find media rows with zero references older than --days and delete the row + MinIO object';

    /**
     * Tables that reference media.id; each entry is [table, foreign_key].
     * Audit-doc enumerated: users.avatar + every *_media_gallery + posts.media_id.
     */
    private const REFERENCE_MAP = [
        ['users', 'avatar'],
        ['posts', 'media_id'],
        ['country_media_gallery', 'media_id'],
        ['state_media_gallery', 'media_id'],
        ['city_media_gallery', 'media_id'],
        ['place_media_gallery', 'media_id'],
        ['activity_media_gallery', 'media_id'],
        ['itinerary_media_gallery', 'media_id'],
        ['package_media_gallery', 'media_id'],
        ['transfer_media_gallery', 'media_id'],
        ['blog_media_gallery', 'media_id'],
        ['review_media_gallery', 'media_id'],
    ];

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('--days must be >= 1');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $orphans = $this->findOrphans($cutoff);
        $count = $orphans->count();

        $this->line(sprintf('Found %d orphan media rows older than %d day(s) (cutoff: %s)', $count, $days, $cutoff->toDateTimeString()));

        if ($count === 0) {
            return self::SUCCESS;
        }

        if (! $execute) {
            $this->warn('Dry-run mode — pass --execute to actually delete.');
            $orphans->take(20)->each(fn ($row) => $this->line(sprintf('  [%d] %s — %s', $row->id, $row->name, $row->url)));
            if ($count > 20) {
                $this->line(sprintf('  …and %d more', $count - 20));
            }

            return self::SUCCESS;
        }

        $deleted = 0;
        $storageFailures = 0;

        foreach ($orphans as $row) {
            try {
                DB::transaction(function () use ($row, &$storageFailures) {
                    $media = Media::find($row->id);
                    if (! $media) {
                        return;
                    }

                    $path = $media->getRawOriginal('url');
                    $path = $this->normalizePath($path);

                    if ($path) {
                        try {
                            if (Storage::disk('minio')->exists($path)) {
                                Storage::disk('minio')->delete($path);
                            }
                        } catch (Throwable $e) {
                            $storageFailures++;
                            Log::warning('PruneOrphanMedia: MinIO delete failed; proceeding with DB delete', [
                                'media_id' => $media->id,
                                'path' => $path,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    $media->delete();
                });
                $deleted++;
            } catch (Throwable $e) {
                Log::error('PruneOrphanMedia: row delete failed', [
                    'media_id' => $row->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info(sprintf('Deleted %d media rows. Storage failures (kept-row mismatch): %d', $deleted, $storageFailures));

        return self::SUCCESS;
    }

    /**
     * Build a single query that returns media ids unreferenced by any known parent table.
     */
    private function findOrphans(\DateTimeInterface $cutoff)
    {
        $query = DB::table('media')
            ->select('media.id', 'media.name', 'media.url')
            ->where('media.created_at', '<', $cutoff);

        foreach (self::REFERENCE_MAP as [$table, $fk]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $fk)) {
                continue;
            }

            $query->whereNotIn('media.id', function ($sub) use ($table, $fk) {
                $sub->select($fk)->from($table)->whereNotNull($fk);
            });
        }

        return $query->get();
    }

    private function normalizePath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            $parsed = parse_url($path, PHP_URL_PATH);
            $path = ltrim((string) $parsed, '/');
            $bucket = config('filesystems.disks.minio.bucket');
            if ($bucket) {
                $path = preg_replace('#^'.preg_quote($bucket, '#').'/#', '', $path);
            }
        }

        return $path ?: null;
    }
}
