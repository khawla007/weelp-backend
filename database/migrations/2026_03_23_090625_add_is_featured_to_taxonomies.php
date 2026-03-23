<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('status');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('status');
        });

        Schema::table('attributes', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('is_featured');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn('is_featured');
        });

        Schema::table('attributes', function (Blueprint $table) {
            $table->dropColumn('is_featured');
        });
    }
};
