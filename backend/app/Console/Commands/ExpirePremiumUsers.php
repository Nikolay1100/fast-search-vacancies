<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

final class ExpirePremiumUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:expire-premium-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for expired premium subscriptions and updates the is_premium flag on users';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $updated = User::where('is_premium', true)
            ->where('premium_expires_at', '<', now())
            ->update(['is_premium' => false]);

        $this->info("Expired premium status for {$updated} users.");
    }
}
