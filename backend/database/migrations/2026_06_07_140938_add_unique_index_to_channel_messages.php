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
        Schema::table('channel_messages', function (Blueprint $table) {
            $table->dropIndex(['channel_telegram_id']);
            $table->unique(['channel_telegram_id', 'message_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('channel_messages', function (Blueprint $table) {
            $table->dropUnique(['channel_telegram_id', 'message_id']);
            $table->index('channel_telegram_id');
        });
    }
};
