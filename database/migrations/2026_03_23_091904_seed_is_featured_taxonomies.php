<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Mark first 5 categories as featured (subquery for MySQL compatibility)
        DB::table('categories')
            ->whereIn('id', DB::table('categories')->orderBy('id', 'asc')->limit(5)->pluck('id'))
            ->update(['is_featured' => true]);

        // Mark first 5 tags as featured
        DB::table('tags')
            ->whereIn('id', DB::table('tags')->orderBy('id', 'asc')->limit(5)->pluck('id'))
            ->update(['is_featured' => true]);

        // Mark first 5 attributes as featured
        DB::table('attributes')
            ->whereIn('id', DB::table('attributes')->orderBy('id', 'asc')->limit(5)->pluck('id'))
            ->update(['is_featured' => true]);
    }

    public function down(): void
    {
        DB::table('categories')->update(['is_featured' => false]);
        DB::table('tags')->update(['is_featured' => false]);
        DB::table('attributes')->update(['is_featured' => false]);
    }
};
