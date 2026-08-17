<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Response\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerifyTelegramWebhook
{
      /**
       * Handle an incoming request.
       */
      public function handle(Request $request, Closure $next): Response
      {
            $secretToken = config('services.telegram.webhook_secret');

            if (empty($secretToken)) {
                  return ApiResponse::error(
                        title: 'Unauthorized',
                        status: 401,
                        detail: 'Webhook secret token is not configured on the server'
                  );
            }

            $requestToken = $request->header('X-Telegram-Bot-Api-Secret-Token');

            if (!$requestToken || $requestToken !== $secretToken) {
                  return ApiResponse::error(
                        title: 'Unauthorized',
                        status: 401,
                        detail: 'Invalid secret token'
                  );
            }

            return $next($request);
      }
}
