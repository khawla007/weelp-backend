<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->string('action_url', 2048)->nullable()->after('data');
            $table->unsignedBigInteger('created_by')->nullable()->after('action_url');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['action_url', 'created_by']);
        });
    }
};
