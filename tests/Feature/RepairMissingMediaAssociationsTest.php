<?php

namespace Tests\Feature;

use App\Models\Media;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class RepairMissingMediaAssociationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_reports_missing_records_without_writing_pivots(): void
    {
        Storage::fake('minio');
        $this->createMediaPool();
        $targets = $this->createParentRecords();

        $this->artisan('media:repair-missing-associations')
            ->expectsOutputToContain('Usable MinIO media: 3')
            ->expectsOutputToContain('Dry-run mode')
            ->assertSuccessful();

        $this->assertTargetCounts($targets, 0);
    }

    public function test_execute_repairs_every_supported_empty_gallery_without_using_missing_objects(): void
    {
        Storage::fake('minio');
        $media = $this->createMediaPool();
        $targets = $this->createParentRecords();

        $this->artisan('media:repair-missing-associations --execute')
            ->expectsOutputToContain('Inserted 15 media associations')
            ->assertSuccessful();

        foreach ($targets as $target) {
            $this->assertSame(3, DB::table($target['pivot'])->where($target['foreign_key'], $target['id'])->count());
            $this->assertSame(1, DB::table($target['pivot'])
                ->where($target['foreign_key'], $target['id'])
                ->where('is_featured', true)
                ->count());
            $this->assertDatabaseMissing($target['pivot'], [
                $target['foreign_key'] => $target['id'],
                'media_id' => $media->last()->id,
            ]);
        }
    }

    public function test_execute_preserves_records_that_already_have_media(): void
    {
        Storage::fake('minio');
        $media = $this->createMediaPool();
        $targets = $this->createParentRecords();
        $activity = $targets['activities'];

        DB::table($activity['pivot'])->insert([
            $activity['foreign_key'] => $activity['id'],
            'media_id' => $media->last()->id,
            'is_featured' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('media:repair-missing-associations --execute')->assertSuccessful();

        $this->assertSame(1, DB::table($activity['pivot'])->where($activity['foreign_key'], $activity['id'])->count());
        $this->assertDatabaseHas($activity['pivot'], [
            $activity['foreign_key'] => $activity['id'],
            'media_id' => $media->last()->id,
            'is_featured' => true,
        ]);
    }

    public function test_second_execute_is_a_no_op(): void
    {
        Storage::fake('minio');
        $this->createMediaPool();
        $targets = $this->createParentRecords();

        $this->artisan('media:repair-missing-associations --execute')->assertSuccessful();
        $firstIds = $this->targetPivotIds($targets);

        $this->artisan('media:repair-missing-associations --execute')
            ->expectsOutputToContain('Inserted 0 media associations')
            ->assertSuccessful();

        $this->assertSame($firstIds, $this->targetPivotIds($targets));
    }

    public function test_repair_assignments_are_deterministic(): void
    {
        Storage::fake('minio');
        $this->createMediaPool();
        $targets = $this->createParentRecords();

        $this->artisan('media:repair-missing-associations --execute')->assertSuccessful();
        $firstAssignments = $this->targetAssignments($targets);

        foreach ($targets as $target) {
            DB::table($target['pivot'])->where($target['foreign_key'], $target['id'])->delete();
        }

        $this->artisan('media:repair-missing-associations --execute')->assertSuccessful();

        $this->assertSame($firstAssignments, $this->targetAssignments($targets));
    }

    public function test_execute_fails_without_three_usable_minio_objects(): void
    {
        Storage::fake('minio');
        $this->createMediaPool(2, 2);
        $targets = $this->createParentRecords();

        $this->artisan('media:repair-missing-associations --execute')
            ->expectsOutputToContain('at least 3 usable MinIO media objects')
            ->assertFailed();

        $this->assertTargetCounts($targets, 0);
    }

    public function test_execute_is_rejected_outside_local_and_testing_environments(): void
    {
        Storage::fake('minio');
        $this->createMediaPool();
        $targets = $this->createParentRecords();
        $originalEnvironment = $this->app->environment();

        try {
            $this->app->detectEnvironment(fn () => 'production');

            $this->artisan('media:repair-missing-associations --execute')
                ->expectsOutputToContain('only available in local and testing environments')
                ->assertFailed();
        } finally {
            $this->app->detectEnvironment(fn () => $originalEnvironment);
        }

        $this->assertTargetCounts($targets, 0);
    }

    public function test_storage_failure_returns_failure_without_writes(): void
    {
        Media::create([
            'name' => 'Unavailable MinIO media',
            'url' => 'countries/random-tourist-places/test/unavailable.jpg',
        ]);
        $targets = $this->createParentRecords();
        Storage::shouldReceive('disk')
            ->once()
            ->with('minio')
            ->andThrow(new RuntimeException('MinIO is offline'));

        $this->artisan('media:repair-missing-associations --execute')
            ->expectsOutputToContain('Unable to inspect MinIO media')
            ->assertFailed();

        $this->assertTargetCounts($targets, 0);
    }

    public function test_database_failure_is_not_reported_as_a_minio_failure(): void
    {
        Storage::fake('minio');
        Schema::drop('media');

        $this->expectException(QueryException::class);

        Artisan::call('media:repair-missing-associations');
    }

    public function test_insert_failure_rolls_back_every_target(): void
    {
        Storage::fake('minio');
        $this->createMediaPool();
        $targets = $this->createParentRecords();
        DB::statement(<<<'SQL'
            CREATE TRIGGER fail_itinerary_media_insert
            BEFORE INSERT ON itinerary_media_gallery
            BEGIN
                SELECT RAISE(ABORT, 'forced failure');
            END
            SQL);

        try {
            Artisan::call('media:repair-missing-associations', ['--execute' => true]);
            $this->fail('Expected the forced itinerary insert failure.');
        } catch (QueryException) {
            $this->assertTargetCounts($targets, 0);
        }
    }

    /**
     * @return Collection<int, Media>
     */
    private function createMediaPool(int $rows = 4, int $objects = 3): Collection
    {
        return collect(range(1, $rows))->map(function (int $index) use ($objects): Media {
            $path = sprintf('countries/random-tourist-places/test/%02d-test-place.jpg', $index);
            $media = Media::create([
                'name' => "Test media {$index}",
                'url' => $path,
            ]);

            if ($index <= $objects) {
                Storage::disk('minio')->put($path, "image-{$index}");
            }

            return $media;
        });
    }

    /**
     * @return array<string, array{pivot: string, foreign_key: string, id: int}>
     */
    private function createParentRecords(): array
    {
        $now = now();

        return [
            'activities' => [
                'pivot' => 'activity_media_gallery',
                'foreign_key' => 'activity_id',
                'id' => DB::table('activities')->insertGetId([
                    'name' => 'Test activity',
                    'slug' => 'test-activity',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
            ],
            'itineraries' => [
                'pivot' => 'itinerary_media_gallery',
                'foreign_key' => 'itinerary_id',
                'id' => DB::table('itineraries')->insertGetId([
                    'name' => 'Test itinerary',
                    'slug' => 'test-itinerary',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
            ],
            'packages' => [
                'pivot' => 'package_media_gallery',
                'foreign_key' => 'package_id',
                'id' => DB::table('packages')->insertGetId([
                    'name' => 'Test package',
                    'slug' => 'test-package',
                    'description' => 'Test package description',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
            ],
            'transfers' => [
                'pivot' => 'transfer_media_gallery',
                'foreign_key' => 'transfer_id',
                'id' => DB::table('transfers')->insertGetId([
                    'name' => 'Test transfer',
                    'slug' => 'test-transfer',
                    'description' => 'Test transfer description',
                    'transfer_type' => 'private',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
            ],
            'blogs' => [
                'pivot' => 'blog_media_gallery',
                'foreign_key' => 'blog_id',
                'id' => DB::table('blogs')->insertGetId([
                    'name' => 'Test blog',
                    'slug' => 'test-blog',
                    'content' => 'Test blog content',
                    'excerpt' => 'Test blog excerpt',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
            ],
        ];
    }

    /**
     * @param  array<string, array{pivot: string, foreign_key: string, id: int}>  $targets
     */
    private function assertTargetCounts(array $targets, int $expected): void
    {
        foreach ($targets as $target) {
            $this->assertSame($expected, DB::table($target['pivot'])->where($target['foreign_key'], $target['id'])->count());
        }
    }

    /**
     * @param  array<string, array{pivot: string, foreign_key: string, id: int}>  $targets
     * @return array<string, array<int, int>>
     */
    private function targetPivotIds(array $targets): array
    {
        return collect($targets)->map(fn (array $target): array => DB::table($target['pivot'])
            ->where($target['foreign_key'], $target['id'])
            ->orderBy('id')
            ->pluck('id')
            ->all())->all();
    }

    /**
     * @param  array<string, array{pivot: string, foreign_key: string, id: int}>  $targets
     * @return array<string, array<int, array{media_id: int, is_featured: int}>>
     */
    private function targetAssignments(array $targets): array
    {
        return collect($targets)->map(fn (array $target): array => DB::table($target['pivot'])
            ->where($target['foreign_key'], $target['id'])
            ->orderBy('id')
            ->get(['media_id', 'is_featured'])
            ->map(fn (object $row): array => [
                'media_id' => (int) $row->media_id,
                'is_featured' => (int) $row->is_featured,
            ])
            ->all())->all();
    }
}
