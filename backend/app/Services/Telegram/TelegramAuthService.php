<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Log;

final class TelegramAuthService
{
      private string $botToken;

      public function __construct()
      {
            $this->botToken = config('services.telegram.bot_token') ?? '';
      }

      /**
       * Validates data from Telegram WebApp.
       */
      public function validateInitData(string $initData): bool
      {
            if (empty($this->botToken)) {
                  Log::error('TelegramAuthService: TELEGRAM_BOT_TOKEN not configured.');
                  return false;
            }

            $data = $this->parseInitData($initData);

            if (!isset($data['hash'])) {
                  return false;
            }

            $hash = $data['hash'];
            unset($data['hash']);

            // Sort data alphabetically
            ksort($data);

            // Build data check string
            $dataCheckString = implode("\n", array_map(
                  fn($key, $value) => "$key=$value",
                  array_keys($data),
                  $data
            ));

            // Calculate secret key based on bot_token
            $secretKey = hash_hmac('sha256', $this->botToken, 'WebAppData', true);

            // Calculate final hash
            $expectedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

            return hash_equals($expectedHash, $hash);
      }

      /**
       * Extracts telegram_id from validated data.
       */
      public function getTelegramIdFromInitData(string $initData): ?int
      {
            $data = $this->parseInitData($initData);

            if (!isset($data['user'])) {
                  return null;
            }

            $user = json_decode($data['user'], true);
            return $user['id'] ?? null;
      }

      /**
       * Helper method to parse query string.
       */
      private function parseInitData(string $initData): array
      {
            $params = [];
            parse_str($initData, $params);
            return $params;
      }
}
