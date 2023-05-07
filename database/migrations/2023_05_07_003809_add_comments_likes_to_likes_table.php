<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('likes', function (Blueprint $table) {
            $table->unsignedBigInteger('likeable_id')->nullable()->change();
            $table->string('likeable_type')->nullable();

            $table->index(['likeable_id', 'likeable_type']);

            $table->foreign('likeable_id')
                ->references('id')
                ->on('posts')
                ->onDelete('cascade');

            $table->foreign('likeable_id')
                ->references('id')
                ->on('comments')
                ->onDelete('cascade')
                ->name('likes_comment_likeable_id_foreign');;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('likes', function (Blueprint $table) {
            $table->dropForeign(['likeable_id']);
            $table->dropIndex(['likeable_id', 'likeable_type']);

            $table->unsignedBigInteger('likeable_id')->change();
            $table->dropColumn('likeable_type');
        });
    }
};
