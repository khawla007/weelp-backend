<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table): void {
            if (! Schema::hasColumn('blogs', 'meta_title')) {
                $table->string('meta_title', 255)->nullable()->after('excerpt');
            }
            if (! Schema::hasColumn('blogs', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('meta_title');
            }
            if (! Schema::hasColumn('blogs', 'keywords')) {
                $table->text('keywords')->nullable()->after('meta_description');
            }
            if (! Schema::hasColumn('blogs', 'og_image_url')) {
                $table->text('og_image_url')->nullable()->after('keywords');
            }
            if (! Schema::hasColumn('blogs', 'canonical_url')) {
                $table->text('canonical_url')->nullable()->after('og_image_url');
            }
            if (! Schema::hasColumn('blogs', 'schema_type')) {
                $table->string('schema_type', 255)->nullable()->after('canonical_url');
            }
            if (! Schema::hasColumn('blogs', 'schema_data')) {
                $table->json('schema_data')->nullable()->after('schema_type');
            }
            if (! Schema::hasColumn('blogs', 'head_code')) {
                $table->longText('head_code')->nullable()->after('schema_data');
            }
            if (! Schema::hasColumn('blogs', 'body_code')) {
                $table->longText('body_code')->nullable()->after('head_code');
            }
            if (! Schema::hasColumn('blogs', 'footer_code')) {
                $table->longText('footer_code')->nullable()->after('body_code');
            }
        });
    }

    public function down(): void
    {
        $columns = array_values(array_filter([
            'meta_title',
            'meta_description',
            'keywords',
            'og_image_url',
            'canonical_url',
            'schema_type',
            'schema_data',
            'head_code',
            'body_code',
            'footer_code',
        ], fn (string $column): bool => Schema::hasColumn('blogs', $column)));

        if ($columns === []) {
            return;
        }

        Schema::table('blogs', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }
};
