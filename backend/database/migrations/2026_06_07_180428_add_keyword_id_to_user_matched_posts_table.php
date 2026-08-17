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
        Schema::table('user_matched_posts', function (Blueprint $table) {
            $table->unsignedBigInteger('keyword_id')->nullable()->after('channel_message_id');
            $table->foreign('keyword_id')->references('id')->on('keywords')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_matched_posts', function (Blueprint $table) {
            $table->dropForeign(['keyword_id']);
            $table->dropColumn('keyword_id');
        });
    }
};
