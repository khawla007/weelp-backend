<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->decimal('hero_overlay_opacity', 5, 2)->nullable()->change();
        });

        DB::table('pages')
            ->whereNotNull('hero_overlay_opacity')
            ->where('hero_overlay_opacity', '>', 1)
            ->update([
                'hero_overlay_opacity' => DB::raw('hero_overlay_opacity / 100'),
            ]);
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->unsignedTinyInteger('hero_overlay_opacity')->nullable()->change();
        });
    }
};
