<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_messages', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('channel_telegram_id');
            $table->bigInteger('message_id');
            $table->text('text')->nullable();
            $table->string('link')->nullable();
            $table->timestamps();

            $table->index('channel_telegram_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_messages');
    }
};
