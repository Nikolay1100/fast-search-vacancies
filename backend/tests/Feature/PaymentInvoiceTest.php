<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyTelegramWebApp;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Disable Telegram auth middleware since we are explicitly using actingAs()
        $this->withoutMiddleware(VerifyTelegramWebApp::class);
    }

    public function test_can_create_invoice_for_plan()
    {
        Config::set('services.lavatop.api_key', 'some-api-key');

        $user = User::factory()->create([
            'email' => 'test@example.com' // Valid email is needed for LavaTop
        ]);

        $plan = Plan::create([
            'name' => 'Premium',
            'price' => 10,
            'duration_days' => 30,
            'provider_id' => \App\Enums\PaymentProvider::LAVA_TOP,
            'offer_id' => 'offer-123'
        ]);

        // Mock Lava.top API to avoid actual requests
        Http::fake([
            '*' => Http::response([
                'paymentUrl' => 'https://app.lava.top/invoice/123',
                'id' => 'tx-12345'
            ], 200)
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/user/plans/{$plan->id}/purchase");

        $response->assertStatus(200);
        $response->assertJsonPath('data.payment_url', 'https://app.lava.top/invoice/123');

        // Assert transaction was created in DB
        $this->assertDatabaseHas('payment_transactions', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'pending'
        ]);
    }
}
