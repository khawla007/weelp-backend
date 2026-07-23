# Local MinIO Media Association Repair Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Preserve media associations during standalone media repairs and restore deterministic MinIO-backed galleries for local countries, states, cities, places, activities, itineraries, packages, transfers, and blogs that currently have none.

**Architecture:** Make `MediaSeeder` additive with path-based upserts. Add one dry-run-first Artisan command that discovers empty galleries, filters the original 161-row media pool to objects that exist on MinIO, and transactionally inserts three deterministic associations per missing record.

**Tech Stack:** Laravel 12, PHP 8.2+, Eloquent/Query Builder, PHPUnit, Flysystem MinIO disk

---

### Task 1: Protect associations when MediaSeeder runs

**Files:**
- Modify: `database/seeders/MediaSeeder.php`
- Create: `tests/Feature/MediaSeederAssociationTest.php`

- [ ] **Step 1: Write the failing regression test**

Create an activity, a media row whose raw path is the seeder’s first curated path, and an `activity_media_gallery` row. Run `MediaSeeder` and assert that the original media ID and pivot still exist:

```php
<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityMediaGallery;
use App\Models\Media;
use Database\Seeders\MediaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaSeederAssociationTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_seeder_preserves_existing_media_ids_and_associations(): void
    {
        $activity = Activity::create([
            'name' => 'Seed preservation activity',
            'slug' => 'seed-preservation-activity',
        ]);
        $media = Media::create([
            'name' => 'Existing curated image',
            'url' => 'countries/random-tourist-places/argentina/01-argentina-tourist-place-1-52a7e30197.jpg',
        ]);
        ActivityMediaGallery::create([
            'activity_id' => $activity->id,
            'media_id' => $media->id,
            'is_featured' => true,
        ]);

        $this->seed(MediaSeeder::class);
        $this->seed(MediaSeeder::class);

        $this->assertDatabaseHas('media', [
            'id' => $media->id,
            'url' => $media->getRawOriginal('url'),
        ]);
        $this->assertSame(1, Media::where('url', $media->getRawOriginal('url'))->count());
        $this->assertDatabaseHas('activity_media_gallery', [
            'activity_id' => $activity->id,
            'media_id' => $media->id,
            'is_featured' => true,
        ]);
        $this->assertSame(1, ActivityMediaGallery::where('activity_id', $activity->id)->count());
    }
}
```

- [ ] **Step 2: Run the test and verify RED**

Run:

```bash
php artisan test tests/Feature/MediaSeederAssociationTest.php
```

Expected: FAIL because the current `MediaSeeder` truncates `activity_media_gallery`.

- [ ] **Step 3: Make MediaSeeder additive**

Remove the foreign-key disabling and all `truncate()` calls. Remove the now-unused `DB` and `Schema` imports. Replace `Media::create()` in the path loop with:

```php
Media::updateOrCreate(
    ['url' => $path],
    [
        'name' => self::generateName($path),
        'alt_text' => self::generateAltText($path),
    ],
);
```

Update the method comment to say the seeder preserves existing IDs and associations.

- [ ] **Step 4: Run the focused test and verify GREEN**

Run:

```bash
php artisan test tests/Feature/MediaSeederAssociationTest.php
```

Expected: PASS.

### Task 2: Add a safe missing-association repair command

**Files:**
- Create: `app/Console/Commands/RepairMissingMediaAssociations.php`
- Create: `tests/Feature/RepairMissingMediaAssociationsTest.php`

- [ ] **Step 1: Write command regression tests**

Use `RefreshDatabase` and `Storage::fake('minio')`. Create four `Media` rows but only three matching fake objects, plus one minimal record in each supported parent table. This makes the happy-path test prove that a database row with a missing MinIO object is excluded.

Cover these behaviors:

```php
public function test_dry_run_reports_missing_records_without_writing_pivots(): void;
public function test_execute_repairs_every_supported_empty_gallery_without_using_missing_objects(): void;
public function test_execute_preserves_records_that_already_have_media(): void;
public function test_second_execute_is_a_no_op(): void;
public function test_repair_assignments_are_deterministic(): void;
public function test_execute_fails_without_three_usable_minio_objects(): void;
public function test_execute_is_rejected_outside_local_and_testing_environments(): void;
public function test_storage_failure_returns_failure_without_writes(): void;
```

The execute assertion for each pivot table is:

```php
$this->assertSame(3, DB::table($pivotTable)->where($foreignKey, $parentId)->count());
$this->assertSame(1, DB::table($pivotTable)
    ->where($foreignKey, $parentId)
    ->where('is_featured', true)
    ->count());
```

The dry-run and no-pool tests assert that every scoped pivot remains empty.

The missing-object assertion is:

```php
$this->assertDatabaseMissing($pivotTable, ['media_id' => $missingObjectMedia->id]);
```

For determinism, record each pivot table’s ordered `media_id` and featured state, delete only those test pivots, execute again, and assert the same ordered values. For the environment guard, temporarily set `app()['env'] = 'production'`, assert failure and no writes, and restore `testing` in `finally`. Mock `Storage::disk('minio')` to throw for the storage-failure test and assert a concise command error, failure exit code, and zero writes.

- [ ] **Step 2: Run command tests and verify RED**

Run:

```bash
php artisan test tests/Feature/RepairMissingMediaAssociationsTest.php
```

Expected: FAIL because `media:repair-missing-associations` does not exist.

- [ ] **Step 3: Implement the command**

Create `RepairMissingMediaAssociations` with:

```php
protected $signature = 'media:repair-missing-associations
                        {--execute : Insert associations (default is dry-run)}';
```

Define one scoped configuration:

```php
private const TARGETS = [
    'countries' => ['parent' => 'countries', 'pivot' => 'country_media_gallery', 'foreign_key' => 'country_id', 'timestamps' => true],
    'states' => ['parent' => 'states', 'pivot' => 'state_media_gallery', 'foreign_key' => 'state_id', 'timestamps' => true],
    'cities' => ['parent' => 'cities', 'pivot' => 'city_media_gallery', 'foreign_key' => 'city_id', 'timestamps' => true],
    'places' => ['parent' => 'places', 'pivot' => 'place_media_gallery', 'foreign_key' => 'place_id', 'timestamps' => true],
    'activities' => ['parent' => 'activities', 'pivot' => 'activity_media_gallery', 'foreign_key' => 'activity_id', 'timestamps' => true],
    'itineraries' => ['parent' => 'itineraries', 'pivot' => 'itinerary_media_gallery', 'foreign_key' => 'itinerary_id', 'timestamps' => true],
    'packages' => ['parent' => 'packages', 'pivot' => 'package_media_gallery', 'foreign_key' => 'package_id', 'timestamps' => true],
    'transfers' => ['parent' => 'transfers', 'pivot' => 'transfer_media_gallery', 'foreign_key' => 'transfer_id', 'timestamps' => true],
    'blogs' => ['parent' => 'blogs', 'pivot' => 'blog_media_gallery', 'foreign_key' => 'blog_id', 'timestamps' => false],
];
```

Implementation rules:

- Return `FAILURE` before storage or database work unless `app()->environment(['local', 'testing'])`.
- Load the first 161 media rows by ID.
- Use `getRawOriginal('url')` and retain only rows for which `Storage::disk('minio')->exists($path)` is true. Catch storage exceptions, print a concise error, and return `FAILURE` before any write.
- Require at least three usable rows; otherwise print an error and return `self::FAILURE`.
- Find parent IDs using `whereNotExists` against the corresponding pivot.
- Print a per-domain count and total planned pivot rows.
- Return after reporting unless `--execute` is present.
- In one `DB::transaction`, insert three rows per missing parent.
- Choose the starting pool offset from the first eight hexadecimal characters of `hash('sha256', "{$type}:{$id}")`; take three consecutive IDs with wraparound.
- Set `is_featured` only on the first row.
- Add `created_at` and `updated_at` only for the four timestamped item pivots.
- Print the inserted total and return `self::SUCCESS`.

- [ ] **Step 4: Run command tests and verify GREEN**

Run:

```bash
php artisan test tests/Feature/RepairMissingMediaAssociationsTest.php
```

Expected: all eight tests PASS.

- [ ] **Step 5: Run both regression suites together**

Run:

```bash
php artisan test tests/Feature/MediaSeederAssociationTest.php tests/Feature/RepairMissingMediaAssociationsTest.php
```

Expected: all tests PASS.

### Task 3: Repair the local database and verify every boundary

**Files:**
- No source changes expected

- [ ] **Step 1: Confirm the command’s dry-run report**

Run:

```bash
php artisan media:repair-missing-associations
```

Expected: non-zero missing counts for countries, states, cities, places, activities, itineraries, packages, transfers, and blogs; zero database writes.

- [ ] **Step 2: Execute the local repair once**

Run:

```bash
php artisan media:repair-missing-associations --execute
```

Expected: three pivot rows per previously empty record and exactly one featured row per repaired gallery.

- [ ] **Step 3: Prove idempotence**

Run the dry run again:

```bash
php artisan media:repair-missing-associations
```

Expected: zero missing records and zero planned inserts.

- [ ] **Step 4: Verify API and proxy boundaries**

Check representative activity, itinerary, package, transfer, and blog responses. Confirm `featured_image` and/or `media_gallery[].url` contain `/api/media/{id}`. Check the corresponding Laravel and Next.js media endpoints return `200` image content.

- [ ] **Step 5: Verify the actual frontend service and mapper boundary**

From `frontend/`, run a one-off `npx --yes --package=tsx tsx` evaluation with `API_BASE_URL=http://localhost:8000/` set before process startup. `tsx` is not a project dependency, so this uses a temporary npx cache download and does not modify `package.json` or the lockfile. Import `getAllFeaturedActivities` from `src/lib/services/activites.js`, normalize its result with `Array.isArray(response) ? response : (response.data ?? [])`, select the first record, pass it to `mapProductToItemCard`, and print only:

```js
{
  service_featured_image: item.featured_image,
  service_media_gallery: item.media_gallery,
  mapped_image: card.image,
}
```

Expected: the service payload contains `/api/media/{id}` and `mapped_image` selects the same path. Repeat the response-shape comparison for the Next.js public blog route and `mapBlogToItemCard`.

### Task 4: Quality gates and visible browser verification

**Files:**
- Modify only if a gate finds a defect directly caused by this change

- [ ] **Step 1: Apply error-handling review**

Invoke `error-handling-patterns` and verify dry-run safety, empty-pool failure, transaction boundaries, and non-destructive behavior.

- [ ] **Step 2: Run backend formatting and tests**

Run:

```bash
vendor/bin/pint --test app/Console/Commands/RepairMissingMediaAssociations.php database/seeders/MediaSeeder.php tests/Feature/MediaSeederAssociationTest.php tests/Feature/RepairMissingMediaAssociationsTest.php
php artisan test tests/Feature/MediaSeederAssociationTest.php tests/Feature/RepairMissingMediaAssociationsTest.php tests/Feature/Public/MediaEndpointTest.php
```

Expected: formatting and tests PASS.

- [ ] **Step 3: Run frontend static checks because rendered behavior is part of acceptance**

From `frontend/` run:

```bash
npm run type-check
npm run lint
```

Expected: both PASS with no new errors.

- [ ] **Step 4: Run mandatory code review and simplification**

Dispatch the project code-review agent against the backend diff. Address critical findings and re-review if needed. Invoke `simplify` if available; if it is unavailable in the installed skill set, perform the same surgical clarity/reuse/efficiency pass manually and record that limitation.

- [ ] **Step 5: Verify in the existing visible headed browser**

Using session `weelp-visible`, discover representative URLs from the repaired API/database and inspect:

- `http://localhost:3000/` — Top activities
- `http://localhost:3000/` — Top Destinations city cards
- an existing city detail page such as `http://localhost:3000/cities/dubai`
- an existing activity detail URL using its returned primary `city_slug` and activity slug; use `/cities/abu-dhabi/activities/desert-safari-adventure` when it still resolves
- an existing city page that returns itinerary cards
- an existing city package listing that returns package cards
- `http://localhost:3000/transfers` with pickup/drop-off inputs selected from available local data so the result dropdown is populated
- `http://localhost:3000/blogs`
- an existing blog detail URL returned by the repaired blog API
- `http://localhost:3000/blogs/dolore-eos-at-aliquid-repellat-4` and record whether it exists

For each available domain, inspect rendered `src` values and confirm real `/api/media/{id}` paths. A 404 candidate does not satisfy verification; choose another data-derived record when one exists. Inspect console errors and failed network requests.

### Task 5: Commit and push main

**Files:**
- All verified backend files from Tasks 1–4

- [ ] **Step 1: Review the final diff**

Run:

```bash
git diff --check
git status --short
git diff
```

Expected: only the scoped backend design, plan, command, seeder, and tests are changed.

- [ ] **Step 2: Commit on backend main**

Stage only scoped files and commit:

```bash
git commit -m "Repair local media associations"
```

- [ ] **Step 3: Push backend main**

Run:

```bash
git push origin main
```

Expected: push succeeds. The frontend repository remains unchanged unless verification exposed a separate confirmed frontend defect.
