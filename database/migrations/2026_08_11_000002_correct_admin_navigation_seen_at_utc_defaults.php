<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = CarbonImmutable::now('UTC')->format('Y-m-d H:i:s.v');

        DB::table('users')
            ->where('admin_orders_last_seen_at', '>', $now)
            ->update(['admin_orders_last_seen_at' => $now]);
        DB::table('users')
            ->where('admin_reviews_last_seen_at', '>', $now)
            ->update(['admin_reviews_last_seen_at' => $now]);

        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement(<<<'SQL'
                ALTER TABLE users
                    MODIFY admin_orders_last_seen_at TIMESTAMP(3) NOT NULL DEFAULT (UTC_TIMESTAMP(3)),
                    MODIFY admin_reviews_last_seen_at TIMESTAMP(3) NOT NULL DEFAULT (UTC_TIMESTAMP(3))
                SQL);
        }
    }

    public function down(): void
    {
        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement(<<<'SQL'
                ALTER TABLE users
                    MODIFY admin_orders_last_seen_at TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
                    MODIFY admin_reviews_last_seen_at TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
                SQL);
        }
    }
};
