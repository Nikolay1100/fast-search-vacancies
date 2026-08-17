<?php

namespace Tests\Feature;

use App\Enums\PaymentProvider;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Config;

class LavaTopWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_invalid_signature()
    {
        Config::set('services.lavatop.api_key', 'some-api-key');
        Config::set('services.lavatop.webhook_api_key', 'secret-key');

        $response = $this->postJson('/api/v1/webhooks/lavatop', [], [
            'X-Api-Key' => 'wrong-key'
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('errors.0.title', 'Invalid signature');
    }

    public function test_processes_valid_success_webhook()
    {
        Config::set('services.lavatop.api_key', 'some-api-key');
        Config::set('services.lavatop.webhook_api_key', 'secret-key');

        $user = User::factory()->create();
        $plan = Plan::create(['name' => 'Premium', 'price' => 10, 'duration_days' => 30, 'provider_id' => PaymentProvider::LAVA_TOP]);

        // Create pending transaction
        $transaction = PaymentTransaction::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => 10,
            'currency' => 'RUB',
            'provider_id' => PaymentProvider::LAVA_TOP,
            'transaction_id' => 'tx-12345',
            'status' => 'pending'
        ]);

        $payload = [
            'eventType' => 'payment.success',
            'contractId' => 'tx-12345',
            'status' => 'completed'
        ];

        $response = $this->postJson('/api/v1/webhooks/lavatop', $payload, [
            'X-Api-Key' => 'secret-key'
        ]);

        $response->assertStatus(200);

        $transaction->refresh();
        $this->assertEquals('success', $transaction->status);
        $this->assertTrue($user->isPremium());
    }
}
