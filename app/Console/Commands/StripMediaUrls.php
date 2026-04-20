<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Models\UserProfile;
use Illuminate\Console\Command;

class StripMediaUrls extends Command
{
    protected $signature = 'media:strip-urls {--dry-run : Preview changes without modifying the database}';

    protected $description = 'Convert full URLs to relative paths in media.url and user_profiles.avatar columns';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $bucket = config('filesystems.disks.minio.bucket');

        if (! $bucket) {
            $this->error('MINIO_BUCKET is not configured. Check your .env file.');

            return 1;
        }

        if ($dryRun) {
            $this->info('=== DRY RUN MODE — no changes will be made ===');
            $this->newLine();
        }

        $mediaCount = $this->processMediaTable($bucket, $dryRun);
        $avatarCount = $this->processAvatarTable($bucket, $dryRun);

        $this->newLine();
        $this->info('Summary:');
        $this->info('  Media records '.($dryRun ? 'to update' : 'updated').": {$mediaCount}");
        $this->info('  Avatar records '.($dryRun ? 'to update' : 'updated').": {$avatarCount}");

        if ($dryRun && ($mediaCount > 0 || $avatarCount > 0)) {
            $this->newLine();
            $this->info('Run without --dry-run to apply changes.');
        }

        return 0;
    }

    private function processMediaTable(string $bucket, bool $dryRun): int
    {
        $this->info('Processing media table...');
        $count = 0;

        Media::where('url', 'like', 'http%')->chunkById(500, function ($records) use ($bucket, $dryRun, &$count) {
            foreach ($records as $record) {
                $originalUrl = $record->getRawOriginal('url');
                $path = $this->stripBaseUrl($originalUrl, $bucket);

                if ($path === null) {
                    $this->warn("  Skipping media #{$record->id}: could not parse URL '{$originalUrl}'");

                    continue;
                }

                if ($dryRun) {
                    if ($count < 5) {
                        $this->line("  #{$record->id}: '{$originalUrl}' → '{$path}'");
                    }
                } else {
                    Media::where('id', $record->id)->update(['url' => $path]);
                }

                $count++;
            }
        });

        if ($dryRun && $count > 5) {
            $this->line('  ... and '.($count - 5).' more');
        }

        $this->info("  Found {$count} media records with full URLs.");

        return $count;
    }

    private function processAvatarTable(string $bucket, bool $dryRun): int
    {
        $this->info('Processing user_profiles.avatar column...');
        $count = 0;

        UserProfile::whereNotNull('avatar')->where('avatar', 'like', 'http%')->chunkById(500, function ($records) use ($bucket, $dryRun, &$count) {
            foreach ($records as $record) {
                $originalUrl = $record->getRawOriginal('avatar');
                $path = $this->stripBaseUrl($originalUrl, $bucket);

                if ($path === null) {
                    $this->warn("  Skipping profile #{$record->id}: could not parse URL '{$originalUrl}'");

                    continue;
                }

                if ($dryRun) {
                    if ($count < 5) {
                        $this->line("  #{$record->id}: '{$originalUrl}' → '{$path}'");
                    }
                } else {
                    UserProfile::where('id', $record->id)->update(['avatar' => $path]);
                }

                $count++;
            }
        });

        if ($dryRun && $count > 5) {
            $this->line('  ... and '.($count - 5).' more');
        }

        $this->info("  Found {$count} avatar records with full URLs.");

        return $count;
    }

    private function stripBaseUrl(string $url, string $bucket): ?string
    {
        $parsedPath = parse_url($url, PHP_URL_PATH);

        if ($parsedPath === false || $parsedPath === null) {
            return null;
        }

        $path = ltrim($parsedPath, '/');

        // Remove bucket prefix if present
        $path = preg_replace('#^'.preg_quote($bucket, '#').'/#', '', $path);

        return $path ?: null;
    }
}
