<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\BannedKeywordController;
use App\Http\Controllers\Api\V1\ChannelController;
use App\Http\Controllers\Api\V1\KeywordController;
use App\Http\Controllers\Api\V1\TelegramWebhookController;
use App\Http\Controllers\Api\V1\Payments\WebhookController;
use App\Http\Controllers\Api\V1\Payments\PaymentController;
use App\Http\Controllers\Api\V1\VacancyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:60,1')->group(function () {

      Route::middleware(['tg_webhook'])->post('/telegram/webhook', [TelegramWebhookController::class, 'handle']);

      Route::post('/webhooks/{provider}', [WebhookController::class, 'handle']);

      // Protected routes (WebApp Auth)
      Route::middleware(['tg_auth'])->group(function () {
            Route::prefix('user')->group(function () {
                  Route::get('/', function (Request $request) {
                        return $request->user();
                  });
                  Route::apiResource('keywords', KeywordController::class)->only(['index', 'store', 'destroy']);
                  Route::apiResource('banned_keywords', BannedKeywordController::class)->only(['index', 'store', 'destroy']);
                  Route::get('vacancies', [VacancyController::class, 'index']);
                  Route::get('plans', [PaymentController::class, 'index']);
                  Route::post('plans/{plan}/purchase', [PaymentController::class, 'purchase']);
            });
          // Protected routes for premium members (Need subscription)
            Route::middleware('subscription')->group(function () {
                  Route::get('channels', [ChannelController::class, 'index']);
            });
      });
});
