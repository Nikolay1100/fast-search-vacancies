<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Telegram\TelegramAuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final readonly class VerifyTelegramWebApp
{
      public function __construct(
            private TelegramAuthService $authService
      ) {
      }

      /**
       * Handle an incoming request.
       */
      public function handle(Request $request, Closure $next): Response
      {
            $initData = $request->header('X-Telegram-Init-Data');

            if (!$initData) {
                  return response()->json([
                        'error' => 'Authorization required',
                        'code' => 'missing_init_data'
                  ], 401);
            }

            if (!$this->authService->validateInitData($initData)) {
                  return response()->json([
                        'error' => 'Invalid Telegram signature',
                        'code' => 'invalid_signature'
                  ], 401);
            }

            $telegramId = $this->authService->getTelegramIdFromInitData($initData);

            if (!$telegramId) {
                  return response()->json([
                        'error' => 'User data not found in init data',
                        'code' => 'missing_user_data'
                  ], 401);
            }

            // Find user by telegram_id
            $user = User::where('telegram_id', $telegramId)->first();

            if (!$user) {
                  return response()->json([
                        'error' => 'User not registered. Please send /start to the bot first.',
                        'code' => 'user_not_found'
                  ], 404);
            }

            // Authenticate user for the current request
            Auth::login($user);

            return $next($request);
      }
}
