<?php

declare(strict_types=1);

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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_premium')->default(false)->after('status_id');
            $table->timestamp('premium_expires_at')->nullable()->after('is_premium');

            $table->index(['is_premium', 'premium_expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_premium', 'premium_expires_at']);
            $table->dropColumn(['is_premium', 'premium_expires_at']);
        });
    }
};
