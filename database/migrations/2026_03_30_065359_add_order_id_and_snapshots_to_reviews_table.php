<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Add order_id column (nullable, with foreign key)
            $table->unsignedBigInteger('order_id')->nullable()->after('user_id');
            $table->foreign('order_id', 'reviews_order_id_foreign')
                  ->references('id')
                  ->on('orders')
                  ->onDelete('set null');

            // Add index for query performance
            $table->index('order_id', 'reviews_order_id_index');

            // Add denormalized snapshot columns with length
            $table->string('item_name_snapshot', 255)->nullable()->after('item_id');
            $table->string('item_slug_snapshot', 255)->nullable()->after('item_name_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign('reviews_order_id_foreign');
            $table->dropIndex('reviews_order_id_index');
            $table->dropColumn(['order_id', 'item_name_snapshot', 'item_slug_snapshot']);
        });
    }
};
