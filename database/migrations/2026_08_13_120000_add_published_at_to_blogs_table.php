<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table): void {
            $table->timestamp('published_at')->nullable()->after('publish');
        });

        DB::table('blogs')
            ->where('publish', true)
            ->whereNull('published_at')
            ->update(['published_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table): void {
            $table->dropColumn('published_at');
        });
    }
};
