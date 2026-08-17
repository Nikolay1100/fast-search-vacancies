<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyTelegramWebApp;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckSubscriptionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Disable Telegram auth middleware since we are explicitly using actingAs()
        $this->withoutMiddleware(VerifyTelegramWebApp::class);
    }

    public function test_user_without_subscription_gets_403()
    {
        $user = User::factory()->create();

        // The channels route is protected by 'subscription' middleware
        // First we must act as user (which passes tg_auth due to test mode actingAs)
        $response = $this->actingAs($user)->getJson('/api/v1/channels');

        $response->assertStatus(403);
        $response->assertJsonPath('errors.0.title', 'Active subscription required to access this resource.');
    }

    public function test_user_with_active_subscription_can_access()
    {
        $user = User::factory()->create();
        $plan = Plan::create(['name' => 'Premium', 'price' => 10, 'duration_days' => 30, 'provider_id' => \App\Enums\PaymentProvider::LAVA_TOP]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/channels');

        $response->assertStatus(200);
    }
}
