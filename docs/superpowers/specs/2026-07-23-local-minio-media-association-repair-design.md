# Local MinIO Media Association Repair Design

## What broke

Local media delivery is healthy after the earlier object repair. A `media` row resolves to `/api/media/{id}`, Laravel streams the matching MinIO object, and the Next.js proxy returns the same image. The item APIs never reach that path because every scoped media pivot table is empty.

`MediaSeeder` caused the loss. Running it by itself truncates `media` and all destination, item, transfer, and blog media pivots before recreating the media rows. The earlier repair therefore restored addressable objects while deleting the associations that activity, itinerary, package, transfer, and blog controllers load.

The exact deleted rows cannot be recovered: the workspace has no SQL backup and local MySQL binary logging is disabled. The original item seeders also selected their media randomly, so replaying their algorithm would not reproduce the old choices.

## Repair boundaries

The fix has two deliberately separate parts:

1. `MediaSeeder` becomes additive and idempotent. It updates an existing row by stored path or creates a missing row, but never truncates `media` or a gallery table. Existing IDs and associations therefore survive standalone media repairs.
2. A local repair command restores only records whose gallery is empty. It draws from the same first 161 curated MinIO-backed media rows used by the original seeders, assigns three stable choices per record, and marks the first choice as featured.

The command covers activities, itineraries, packages, transfers, and blogs. It does not replace, reorder, or add to a record that already has at least one media association.

## Command behavior

`php artisan media:repair-missing-associations` is a dry run. It reports:

- how many usable media rows exist in the curated pool;
- how many records in each supported domain have no media;
- how many pivot rows an execution would add.

`php artisan media:repair-missing-associations --execute` performs the repair in a database transaction. Before selecting the pool, it confirms that each candidate row has a real object on the configured MinIO disk. If no usable media exists, the command exits unsuccessfully without changing the database.

Assignments are deterministic from the entity type and primary key. Re-running the command produces no changes because repaired records no longer qualify as missing. This mirrors the seeded development data’s original use of curated travel imagery without pretending the unrecoverable random choices are known.

## Data flow after repair

For an activity card, the restored path is:

`activity_media_gallery.media_id`
→ `Media::url`
→ Laravel activity `featured_image` and `media_gallery[].url`
→ frontend service response
→ `mapProductToItemCard`
→ `/api/media/{id}`
→ Next.js Image
→ Next proxy
→ Laravel media endpoint
→ MinIO object.

The same gallery shape is already consumed by item detail galleries and blog listing/detail components, so no frontend fallback or per-card patch is needed.

## Failure handling

- Dry-run mode is the default so the command cannot mutate data accidentally.
- A missing MinIO pool stops execution before any pivot write.
- Database writes run in one transaction so a failure cannot leave a partially repaired set.
- Existing associations are never touched.
- The command does not upload, delete, migrate, seed, or alter media objects.

## Test strategy

Regression tests will prove:

1. Running `MediaSeeder` preserves an existing item media pivot and its media ID.
2. The repair command’s dry run does not change pivots.
3. Execute mode attaches three valid media rows to each empty supported record and marks exactly one featured.
4. Execute mode leaves pre-associated records unchanged.
5. A second execution is a no-op.
6. No usable MinIO objects produces a failure with no database writes.

After focused backend tests pass, the local command will run once with `--execute`. API checks and the required headed-browser pass will confirm real `/api/media/{id}` sources across cards, item details, transfers, and blogs.
