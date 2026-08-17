<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\Payments\PaymentProviderFactory;
use App\Http\Response\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaymentController extends Controller
{
    /**
     * Get a list of all active subscription plans.
     */
    public function index(): JsonResponse
    {
        $plans = Plan::where('is_active', true)->get();

        return ApiResponse::data($plans);
    }

    /**
     * Generate a payment link for a specific plan.
     */
    public function purchase(Request $request, Plan $plan): JsonResponse
    {
        if (!$plan->is_active) {
            return ApiResponse::error('Plan is not active.', 400);
        }

        if (!$plan->provider_id) {
            return ApiResponse::error('Plan has no payment provider configured.', 400);
        }

        try {
            $paymentGateway = PaymentProviderFactory::make($plan->provider_id);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error('Payment provider not implemented.', 501);
        }

        $user = $request->user();
        $paymentUrl = $paymentGateway->createInvoice($user, $plan);

        if ($paymentUrl) {
            return ApiResponse::data([
                'payment_url' => $paymentUrl,
            ]);
        }

        return ApiResponse::error('Failed to create payment invoice.', 500);
    }
}
