<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Enums\PaymentProvider;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\User;
use App\Services\Payments\Interfaces\PaymentProviderInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class LavaTopService implements PaymentProviderInterface
{
    private string $apiKey;
    private string $baseUrl;
    private string $webhookApiKey;

    public function __construct()
    {
        $apiKey = config('services.lavatop.api_key');
        if (!is_string($apiKey) || $apiKey === '') {
            throw new \RuntimeException('Lava.top API key is not configured.');
        }

        $this->apiKey = $apiKey;
        $this->baseUrl = config('services.lavatop.base_url', 'https://gate.lava.top/api/v3');
        $this->webhookApiKey = (string) config('services.lavatop.webhook_api_key');
    }

    public function createInvoice(User $user, Plan $plan): ?string
    {
        if (!$plan->offer_id) {
            Log::error('LavaTop: Plan does not have offer_id', ['plan_id' => $plan->id]);
            return null;
        }

        //todo сделать поле для ввода e-mail
        // Pseudo-email for Telegram users, as Lava.top requires an email.
        // Using a valid TLD (.com) because Lava.top strictly validates the email format.
        $email = $user->email ?? "tg_{$user->telegram_id}@fast-search.3d-printeri.ru";

        $response = Http::withHeaders([
            'X-Api-Key' => $this->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/invoice', [
            'offerId' => $plan->offer_id,
            'email' => $email,
            'currency' => 'RUB',
        ]);

        if ($response->successful()) {
            $data = $response->json();

            // Lava.top v3 returns id and paymentUrl
            $paymentUrl = $data['paymentUrl'] ?? null;
            $invoiceId = $data['id'] ?? null;

            if ($paymentUrl && $invoiceId) {
                PaymentTransaction::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'amount' => $plan->price,
                    'currency' => 'RUB',
                    'provider_id' => PaymentProvider::LAVA_TOP,
                    'transaction_id' => $invoiceId,
                    'status' => 'pending',
                ]);

                return $paymentUrl;
            }
        }

        Log::error('LavaTop: Failed to create invoice', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return null;
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $incomingKey = $request->header('X-Api-Key');

        if (!$incomingKey) {
             Log::warning('LavaTop Webhook: missing X-Api-Key header');
             return false;
        }

        if (!hash_equals($this->webhookApiKey, $incomingKey)) {
             Log::warning('LavaTop Webhook: X-Api-Key mismatch');
             return false;
        }

        return true;
    }

    public function extractTransactionId(array $payload): ?string
    {
        return $payload['contractId'] ?? $payload['id'] ?? $payload['invoice_id'] ?? null;
    }

    public function extractEventStatus(array $payload): ?string
    {
        $eventType = $payload['eventType'] ?? null;
        $status = $payload['status'] ?? null;

        if ($eventType === 'payment.success' || $status === 'completed') {
            return 'success';
        }

        if ($eventType === 'payment.failed' || $status === 'failed') {
            return 'failed';
        }

        return 'unknown';
    }
}
