<?php

namespace Tests\Feature;

use App\Enums\PaymentProvider;
use App\Models\Keyword;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeywordLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Disable Telegram auth middleware since we are explicitly using actingAs()
        $this->withoutMiddleware(\App\Http\Middleware\VerifyTelegramWebApp::class);
    }

    public function test_regular_user_cannot_exceed_limit()
    {
        $user = User::factory()->create();

        // Limit is 2. Let's add 2 keywords directly to DB.
        $kw1 = Keyword::create(['word' => 'word1']);
        $kw2 = Keyword::create(['word' => 'word2']);
        $user->keywords()->attach([$kw1->id, $kw2->id]);

        // Try to add a 3rd via API
        $response = $this->actingAs($user)->postJson('/api/v1/user/keywords', [
            'word' => 'word3'
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('errors.0.title', 'Keyword limit reached. Please upgrade to a premium subscription to add more keywords.');
    }

    public function test_premium_user_can_exceed_regular_limit()
    {
        $user = User::factory()->create();
        $plan = Plan::create(['name' => 'Premium', 'price' => 10, 'duration_days' => 30, 'provider_id' => PaymentProvider::LAVA_TOP]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
        ]);

        // Add 2 keywords (regular limit)
        $kw1 = Keyword::create(['word' => 'word1']);
        $kw2 = Keyword::create(['word' => 'word2']);
        $user->keywords()->attach([$kw1->id, $kw2->id]);

        // Try to add a 3rd via API (should succeed for premium)
        $response = $this->actingAs($user)->postJson('/api/v1/user/keywords', [
            'word' => 'word3'
        ]);

        $response->assertStatus(200);
        // Also verify the keyword was added
        $this->assertDatabaseHas('keywords', ['word' => 'word3']);
        $this->assertEquals(3, $user->keywords()->count());
    }
}
