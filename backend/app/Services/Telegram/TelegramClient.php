<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class TelegramClient
{
      private string $botToken;
      private string $baseUrl;

      public function __construct()
      {
            $this->botToken = config('services.telegram.bot_token') ?? '';
            $this->baseUrl = "https://api.telegram.org/bot{$this->botToken}";
      }

      /**
       * Send a message via Telegram Bot API.
       */
      public function sendMessage(string $chatId, string $text, string $parseMode = 'HTML', array $replyMarkup = []): bool
      {
            if (empty($this->botToken)) {
                  Log::error('TelegramClient: BOT_TOKEN not configured.');
                  return false;
            }

            $payload = [
                  'chat_id' => $chatId,
                  'text' => $text,
                  'parse_mode' => $parseMode,
            ];

            if (!empty($replyMarkup)) {
                  $payload['reply_markup'] = $replyMarkup;
            }

            $response = Http::post("{$this->baseUrl}/sendMessage", $payload);

            if ($response->failed()) {
                  Log::error('TelegramClient: Failed to send message.', [
                        'chat_id' => $chatId,
                        'response' => $response->body(),
                  ]);
                  return false;
            }

            return true;
      }
}
