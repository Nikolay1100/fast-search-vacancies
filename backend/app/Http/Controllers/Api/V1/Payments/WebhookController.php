<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Payments;

use App\Enums\PaymentProvider;
use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentProviderFactory;
use App\Services\Payments\PaymentService;
use App\Http\Response\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class WebhookController extends Controller
{
    public function handle(string $providerAlias, Request $request, PaymentService $paymentService): JsonResponse
    {
        $provider = PaymentProvider::fromAlias($providerAlias);

        if (!$provider) {
            return ApiResponse::error('Unknown provider', 404);
        }

        try {
            $gateway = PaymentProviderFactory::make($provider);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error('Provider implementation not found', 501);
        }

        if (!$gateway->verifyWebhookSignature($request)) {
            Log::warning("{$providerAlias} Webhook: Invalid signature or missing header", ['payload' => $request->all()]);
            return ApiResponse::error('Invalid signature', 403);
        }

        $payload = $request->all();
        $transactionId = $gateway->extractTransactionId($payload);
        $status = $gateway->extractEventStatus($payload);

        if (!$transactionId || !$status) {
            Log::warning("{$providerAlias} Webhook: Missing required fields", ['payload' => $payload]);
            return ApiResponse::error('Missing required fields', 400);
        }

        Log::info("{$providerAlias} Webhook Received", ['status' => $status, 'transaction_id' => $transactionId]);

        if ($status === 'success') {
            $paymentService->activateSubscription($transactionId);
        } elseif ($status === 'failed') {
            $paymentService->failTransaction($transactionId);
        } else {
            Log::info("{$providerAlias} Webhook: Unhandled status '{$status}'");
        }

        return ApiResponse::data(['status' => 'ok']);
    }
}
