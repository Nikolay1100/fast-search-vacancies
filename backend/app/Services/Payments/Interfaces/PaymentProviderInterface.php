<?php

declare(strict_types=1);

namespace App\Services\Payments\Interfaces;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

interface PaymentProviderInterface
{
    /**
     * Create an invoice/payment link for the user and plan.
     */
    public function createInvoice(User $user, Plan $plan): ?string;

    /**
     * Verify the incoming webhook request from the payment provider.
     */
    public function verifyWebhookSignature(Request $request): bool;

    /**
     * Extract the transaction ID from the webhook payload.
     */
    public function extractTransactionId(array $payload): ?string;

    /**
     * Extract the event status from the webhook payload.
     * Returns 'success', 'failed', or null if unknown.
     */
    public function extractEventStatus(array $payload): ?string;
}
