<?php

namespace Tests\Feature;

use App\Enums\PaymentProvider;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\User;
use App\Models\Subscription;
use App\Services\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_activate_subscription_creates_new_subscription()
    {
        $user = User::factory()->create();
        $plan = Plan::create(['name' => 'Premium', 'price' => 10, 'duration_days' => 30, 'provider_id' => PaymentProvider::LAVA_TOP]);

        PaymentTransaction::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => 10,
            'currency' => 'RUB',
            'provider_id' => PaymentProvider::LAVA_TOP,
            'transaction_id' => 'tx-123',
            'status' => 'pending'
        ]);

        $service = new PaymentService();
        $service->activateSubscription('tx-123');

        $this->assertTrue($user->isPremium());

        $subscription = $user->subscriptions()->first();
        $this->assertNotNull($subscription);
        $this->assertEquals($plan->id, $subscription->plan_id);
        $this->assertTrue(Carbon::parse($subscription->ends_at)->isSameDay(now()->addDays(30)));
    }

    public function test_activate_subscription_extends_existing_subscription()
    {
        $user = User::factory()->create();
        $plan = Plan::create(['name' => 'Premium', 'price' => 10, 'duration_days' => 30, 'provider_id' => PaymentProvider::LAVA_TOP]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(10),
        ]);

        PaymentTransaction::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => 10,
            'currency' => 'RUB',
            'provider_id' => PaymentProvider::LAVA_TOP,
            'transaction_id' => 'tx-456',
            'status' => 'pending'
        ]);

        $service = new PaymentService();
        $service->activateSubscription('tx-456');

        $subscription = $user->subscriptions()->first();
        // Should be extended by 30 days from the previous end date (10 + 30 = 40 days from now)
        $this->assertTrue(Carbon::parse($subscription->ends_at)->isSameDay(now()->addDays(40)));
    }
}
