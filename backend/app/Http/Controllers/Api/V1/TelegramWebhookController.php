<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Telegram\RegisterTelegramUser;
use App\Events\TelegramUserRegistered;
use App\Http\Controllers\Controller;
use App\Http\Response\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class TelegramWebhookController extends Controller
{
    public function handle(
        Request $request,
        RegisterTelegramUser $registerAction
    ): JsonResponse {

        $webHookUpdate = $request->all();

        $text = data_get($webHookUpdate, 'message.text');
        $telegramUser = data_get($webHookUpdate, 'message.from');
        $chatId = (string) data_get($webHookUpdate, 'message.chat.id');

        if ($text && str_starts_with($text, '/start') && $telegramUser && isset($telegramUser['id']) && $chatId) {
            $user = $registerAction($telegramUser);

            TelegramUserRegistered::dispatch($user, $chatId);

            Log::info('User Registered/Updated via Webhook', ['user_id' => $user->id, 'telegram_id' => $user->telegram_id]);
        }

        return ApiResponse::data(['status' => 'ok']);
    }
}
