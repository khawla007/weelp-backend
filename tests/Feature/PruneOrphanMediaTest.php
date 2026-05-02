<?php

namespace Tests\Feature;

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PruneOrphanMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_does_not_delete_orphans(): void
    {
        Storage::fake('minio');

        $orphan = Media::create([
            'name' => 'orphan-dry',
            'url' => 'orphans/orphan-dry.jpg',
        ]);
        DB::table('media')->where('id', $orphan->id)->update(['created_at' => now()->subDays(30)]);
        Storage::disk('minio')->put('orphans/orphan-dry.jpg', 'data');

        $this->artisan('media:prune-orphans')
            ->expectsOutputToContain('Found 1 orphan media rows')
            ->expectsOutputToContain('Dry-run mode')
            ->assertSuccessful();

        $this->assertDatabaseHas('media', ['id' => $orphan->id]);
        Storage::disk('minio')->assertExists('orphans/orphan-dry.jpg');
    }

    public function test_execute_deletes_orphan_db_row_and_minio_object(): void
    {
        Storage::fake('minio');

        $orphan = Media::create([
            'name' => 'orphan-exec',
            'url' => 'orphans/orphan-exec.jpg',
        ]);
        DB::table('media')->where('id', $orphan->id)->update(['created_at' => now()->subDays(30)]);
        Storage::disk('minio')->put('orphans/orphan-exec.jpg', 'data');

        $this->artisan('media:prune-orphans --execute')
            ->expectsOutputToContain('Deleted 1 media rows')
            ->assertSuccessful();

        $this->assertDatabaseMissing('media', ['id' => $orphan->id]);
        Storage::disk('minio')->assertMissing('orphans/orphan-exec.jpg');
    }

    public function test_referenced_media_is_not_pruned(): void
    {
        Storage::fake('minio');

        $referenced = Media::create([
            'name' => 'referenced',
            'url' => 'referenced.jpg',
        ]);
        DB::table('media')->where('id', $referenced->id)->update(['created_at' => now()->subDays(30)]);

        DB::table('country_media_gallery')->insert([
            'country_id' => DB::table('countries')->insertGetId([
                'name' => 'Test Country',
                'slug' => 'test-country',
                'code' => 'TC',
                'created_at' => now(),
                'updated_at' => now(),
            ]),
            'media_id' => $referenced->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('media:prune-orphans --execute')->assertSuccessful();

        $this->assertDatabaseHas('media', ['id' => $referenced->id]);
    }

    public function test_recent_media_is_not_pruned_even_if_orphan(): void
    {
        Storage::fake('minio');

        $recent = Media::create([
            'name' => 'recent-orphan',
            'url' => 'recent.jpg',
        ]);

        $this->artisan('media:prune-orphans --execute')
            ->expectsOutputToContain('Found 0 orphan media rows')
            ->assertSuccessful();

        $this->assertDatabaseHas('media', ['id' => $recent->id]);
    }
}
