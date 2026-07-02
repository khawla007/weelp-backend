<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('pages')
            ->whereNotNull('hero_overlay_opacity')
            ->where('hero_overlay_opacity', '>', 1)
            ->update([
                'hero_overlay_opacity' => DB::raw('hero_overlay_opacity / 100'),
            ]);
    }

    public function down(): void
    {
        //
    }
};
