<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Enums\PaymentProvider;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class PaymentService
{
    /**
     * Activate subscription for the user after a successful payment.
     */
    public function activateSubscription(string $transactionId): void
    {
        DB::transaction(function () use ($transactionId) {
            $transaction = PaymentTransaction::where('transaction_id', $transactionId)
                ->where('provider_id', PaymentProvider::LAVA_TOP)
                ->where('status', 'pending')
                ->first();

            if (!$transaction || !$transaction->plan_id) {
                Log::error('Cannot activate subscription: transaction not found or missing plan_id', ['transaction_id' => $transactionId]);
                return;
            }

            $transaction->update(['status' => 'success']);

            $user = $transaction->user;
            $plan = $transaction->plan;

            $existingSub = $user->subscriptions()
                ->where('plan_id', $plan->id)
                ->where('ends_at', '>', now())
                ->first();

            if ($existingSub) {
                // Extend the current subscription
                $newEndsAt = Carbon::parse($existingSub->ends_at)->addDays($plan->duration_days);
                $existingSub->update([
                    'ends_at' => $newEndsAt,
                ]);
            } else {
                // Create a new subscription
                $newEndsAt = now()->addDays($plan->duration_days);
                $user->subscriptions()->create([
                    'plan_id' => $plan->id,
                    'starts_at' => now(),
                    'ends_at' => $newEndsAt,
                ]);
            }

            $user->update([
                'is_premium' => true,
                'premium_expires_at' => $newEndsAt,
            ]);

            Log::info('Subscription activated', ['user_id' => $user->id, 'plan_id' => $plan->id]);
        });
    }

    /**
     * Manually grant a subscription (e.g. by admin).
     */
    public function grantManualSubscription(User $user, Plan $plan, int $days): void
    {
        DB::transaction(function () use ($user, $plan, $days) {
            PaymentTransaction::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'amount' => 0,
                'currency' => 'RUB',
                'provider_id' => PaymentProvider::MANUAL,
                'status' => 'success',
            ]);

            $existingSub = $user->subscriptions()
                ->where('plan_id', $plan->id)
                ->where('ends_at', '>', now())
                ->first();

            if ($existingSub) {
                $newEndsAt = Carbon::parse($existingSub->ends_at)->addDays($days);
                $existingSub->update([
                    'ends_at' => $newEndsAt,
                ]);
            } else {
                $newEndsAt = now()->addDays($days);
                $user->subscriptions()->create([
                    'plan_id' => $plan->id,
                    'starts_at' => now(),
                    'ends_at' => $newEndsAt,
                ]);
            }

            $user->update([
                'is_premium' => true,
                'premium_expires_at' => $newEndsAt,
            ]);

            Log::info('Manual subscription granted', ['user_id' => $user->id, 'plan_id' => $plan->id, 'days' => $days]);
        });
    }

    /**
     * Handle payment failure or cancellation.
     */
    public function failTransaction(string $transactionId): void
    {
        //Todo посмотреть как убрать PaymentProvider::LAVA_TOP
        $transaction = PaymentTransaction::where('transaction_id', $transactionId)
            ->where('provider_id', PaymentProvider::LAVA_TOP)
            ->where('status', 'pending')
            ->first();

        if ($transaction) {
            $transaction->update(['status' => 'failed']);
            Log::info('Transaction failed', ['transaction_id' => $transactionId]);
        }
    }
}
