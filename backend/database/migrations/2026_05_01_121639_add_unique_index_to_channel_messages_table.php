<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('channel_messages', function (Blueprint $table) {
            // Создаем уникальный индекс на связку канала и ID сообщения
            $table->unique(['channel_telegram_id', 'message_id'], 'unique_msg_per_channel');
        });
    }

    public function down(): void
    {
        Schema::table('channel_messages', function (Blueprint $table) {
            $table->dropUnique('unique_msg_per_channel');
        });
    }
};
