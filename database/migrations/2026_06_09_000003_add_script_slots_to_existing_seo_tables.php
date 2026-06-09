<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['itinerary_seo', 'transfer_seo', 'package_seo'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'head_code')) {
                    $table->longText('head_code')->nullable()->after('schema_data');
                }
                if (! Schema::hasColumn($tableName, 'body_code')) {
                    $table->longText('body_code')->nullable()->after('head_code');
                }
                if (! Schema::hasColumn($tableName, 'footer_code')) {
                    $table->longText('footer_code')->nullable()->after('body_code');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['itinerary_seo', 'transfer_seo', 'package_seo'] as $tableName) {
            $columns = array_values(array_filter([
                'head_code',
                'body_code',
                'footer_code',
            ], fn (string $column): bool => Schema::hasColumn($tableName, $column)));

            if ($columns === []) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
