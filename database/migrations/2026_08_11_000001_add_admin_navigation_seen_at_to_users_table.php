<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('users', function (Blueprint $table): void {
                $table->timestamp('admin_orders_last_seen_at', 3)
                    ->default(DB::raw('(UTC_TIMESTAMP(3))'))
                    ->after('notifications_last_seen_at');
                $table->timestamp('admin_reviews_last_seen_at', 3)
                    ->default(DB::raw('(UTC_TIMESTAMP(3))'))
                    ->after('admin_orders_last_seen_at');
            });
        } else {
            Schema::table('users', function (Blueprint $table): void {
                $table->timestamp('admin_orders_last_seen_at', 3)
                    ->nullable()
                    ->after('notifications_last_seen_at');
                $table->timestamp('admin_reviews_last_seen_at', 3)
                    ->nullable()
                    ->after('admin_orders_last_seen_at');
            });

            DB::table('users')->update([
                'admin_orders_last_seen_at' => DB::raw('CURRENT_TIMESTAMP'),
                'admin_reviews_last_seen_at' => DB::raw('CURRENT_TIMESTAMP'),
            ]);

            Schema::table('users', function (Blueprint $table): void {
                $table->timestamp('admin_orders_last_seen_at', 3)
                    ->nullable(false)
                    ->useCurrent()
                    ->after('notifications_last_seen_at')
                    ->change();
                $table->timestamp('admin_reviews_last_seen_at', 3)
                    ->nullable(false)
                    ->useCurrent()
                    ->after('admin_orders_last_seen_at')
                    ->change();
            });
        }

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('reviews', function (Blueprint $table): void {
                $table->timestamp('created_at', 3)->nullable()->change();
            });

            Schema::table('orders', function (Blueprint $table): void {
                $table->timestamp('created_at', 3)->nullable()->change();
            });
        }

        Schema::table('reviews', function (Blueprint $table): void {
            $table->index('created_at', 'reviews_created_at_navigation_unseen_index');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->index(
                ['deleted_at', 'created_at'],
                'orders_deleted_at_created_at_navigation_unseen_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropIndex('reviews_created_at_navigation_unseen_index');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_deleted_at_created_at_navigation_unseen_index');
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('reviews', function (Blueprint $table): void {
                $table->timestamp('created_at')->nullable()->change();
            });

            Schema::table('orders', function (Blueprint $table): void {
                $table->timestamp('created_at')->nullable()->change();
            });
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'admin_orders_last_seen_at',
                'admin_reviews_last_seen_at',
            ]);
        });
    }
};
