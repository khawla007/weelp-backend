<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // L7 — orders: polymorphic lookups + admin filters
        $this->addIndex('orders', ['orderable_type', 'orderable_id'], 'orders_orderable_index');
        $this->addIndex('orders', ['status'], 'orders_status_index');
        $this->addIndex('orders', ['travel_date'], 'orders_travel_date_index');
        $this->addIndex('orders', ['created_at'], 'orders_created_at_index');

        // L8 — reviews: public review lookup per item + status filter
        // Note: composite + status indexes were already added by the
        // 2026_03_24 review_media_gallery migration; hasIndex() guards handle that.
        $this->addIndex('reviews', ['item_type', 'item_id'], 'reviews_item_type_item_id_index');
        $this->addIndex('reviews', ['status'], 'reviews_status_index');

        // L9 — creator_applications: status filter
        $this->addIndex('creator_applications', ['status'], 'creator_applications_status_index');

        // L10 — users: login lockout scan + admin filters
        $this->addIndex('users', ['role'], 'users_role_index');
        $this->addIndex('users', ['status'], 'users_status_index');
        $this->addIndex('users', ['locked_until'], 'users_locked_until_index');

        // L11 — blogs: public list filtered by publish + ordered by created_at
        $this->addIndex('blogs', ['publish', 'created_at'], 'blogs_publish_created_at_index');

        // L12 — token lookups (email_verifications.token already unique via M8)
        $this->addIndex('password_resets', ['token'], 'password_resets_token_index');
    }

    public function down(): void
    {
        $this->dropIndex('orders', 'orders_orderable_index');
        $this->dropIndex('orders', 'orders_status_index');
        $this->dropIndex('orders', 'orders_travel_date_index');
        $this->dropIndex('orders', 'orders_created_at_index');

        $this->dropIndex('reviews', 'reviews_item_type_item_id_index');
        $this->dropIndex('reviews', 'reviews_status_index');

        $this->dropIndex('creator_applications', 'creator_applications_status_index');

        $this->dropIndex('users', 'users_role_index');
        $this->dropIndex('users', 'users_status_index');
        $this->dropIndex('users', 'users_locked_until_index');

        $this->dropIndex('blogs', 'blogs_publish_created_at_index');

        $this->dropIndex('password_resets', 'password_resets_token_index');
    }

    private function addIndex(string $table, array $columns, string $indexName): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if (Schema::hasIndex($table, $indexName)) {
            return;
        }

        if (Schema::hasIndex($table, $columns)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    private function dropIndex(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }
};
