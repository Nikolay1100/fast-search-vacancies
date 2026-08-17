<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
      public function up(): void
      {
            Schema::table('channel_messages', function (Blueprint $table) {
                  // Меняем тип поля на text, чтобы влезали длинные посты
                  $table->text('text')->change();
            });
      }

      public function down(): void
      {
            Schema::table('channel_messages', function (Blueprint $table) {
                  $table->string('text')->change();
            });
      }
};
