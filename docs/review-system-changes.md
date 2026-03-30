# Review System Changes

## Overview

The review system has been enhanced to preserve review data even when the reviewed items (Activities/Packages/Itineraries) are deleted from the database.

## New Database Fields

### reviews table

| Column | Type | Description |
|--------|------|-------------|
| `order_id` | `bigint(20) unsigned` nullable | Links review to the order (booking). Foreign key to `orders.id` with `onDelete('set null')` |
| `item_name_snapshot` | `varchar(255)` nullable | Preserves the item name at review creation time. Used as fallback when item is deleted. |
| `item_slug_snapshot` | `varchar(255)` nullable | Preserves the item slug at review creation time. Used as fallback when item is deleted. |

### Indexes

- `reviews_order_id_index` on `order_id` column for query performance

## Review Model Helpers

### `getDisplayName(): string`

Returns the item name for display:
1. First tries: `$this->item->name` (live item)
2. Fallback to: `$this->item_name_snapshot` (saved snapshot)
3. Final fallback: `'Archived Item'`

### `getDisplaySlug(): ?string`

Returns the item slug:
1. First tries: `$this->item->slug` (live item)
2. Fallback to: `$this->item_slug_snapshot` (saved snapshot)

### `hasLiveItem(): bool`

Returns `true` if the reviewed item still exists in the database, `false` if deleted.

### `order(): BelongsTo`

Relationship to the Order model. Allows reviews to be linked to actual bookings.

## API Changes

### Customer Endpoints

**GET /api/customer/review**

Response now includes:
```json
{
  "id": 1,
  "order_id": 7,
  "item_type": "activity",
  "item_id": 5,
  "item_name": "Desert Safari Adventure",
  "item_slug": "desert-safari-adventure",
  "has_live_item": true,
  "rating": 5,
  "review_text": "Amazing experience!",
  "status": "approved",
  "media_gallery": [...],
  "created_at": "2026-03-30",
  "updated_at": "2026-03-30"
}
```

**POST /api/customer/review**

Request now accepts optional `order_id`:
```json
{
  "item_type": "activity",
  "item_id": 5,
  "order_id": 7,
  "rating": 5,
  "review_text": "Great experience!"
}
```

The `item_name_snapshot` and `item_slug_snapshot` are automatically populated from the item at creation time.

### Admin Endpoints

**GET /api/admin/reviews**

Response now includes:
```json
{
  "id": 1,
  "order_id": 7,
  "item": {
    "id": 5,
    "name": "Desert Safari Adventure",
    "has_live_item": true,
    "type": "activity",
    "frontend_url": "..."
  },
  ...
}
```

## Frontend Changes

### UserDashboardReviewCard Component

**Props:**
- `order_id` - The actual booking/order ID (not review ID)
- `has_live_item` - Boolean indicating if the reviewed item still exists
- `item_slug` - Item slug for linking

**Display Logic:**
- "Booking Id" now shows the actual `order_id` from the booking
- When `has_live_item` is `false`, shows "(Archived)" badge next to item type
- Item name always displays (uses snapshot when item deleted)

## Behavior

### When Order is Deleted
- Review persists in database
- `order_id` becomes `null` (SET NULL foreign key)
- Review remains accessible to user
- Booking Id shows as "N/A" or hidden (frontend handles null)

### When Item (Activity/Package/Itinerary) is Deleted
- Review persists in database
- `item` relationship returns `null`
- `getDisplayName()` uses `item_name_snapshot`
- `getDisplaySlug()` uses `item_slug_snapshot`
- `hasLiveItem()` returns `false`
- Frontend shows "(Archived)" badge

### When User Account is Deleted
- All user's reviews are cascade deleted (foreign key constraint)
- Privacy compliance

## Migration Notes

### Existing Reviews

- `ReviewSnapshotSeeder` backfills `item_name_snapshot` and `item_slug_snapshot` for 23 existing reviews
- 4 reviews could not be backfilled (items no longer exist)
- `order_id` remains `null` for existing reviews (no clear mapping available)
- New reviews created through checkout will have `order_id` populated

## Future Considerations

1. **Order linking in checkout flow** - When implementing review creation after booking, pass the `order_id`
2. **Admin review creation** - Admin can manually link reviews to orders
3. **Data cleanup** - Consider soft-deleting items instead of hard delete for better historical tracking
