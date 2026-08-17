<?php

declare(strict_types=1);

namespace App\Services\Telegram\Keyboard;

final class Button
{
    public static function url(string $text, string $url): array
    {
        return [
            'text' => $text,
            'url' => $url,
        ];
    }

    public static function webApp(string $text, string $url): array
    {
        return [
            'text' => $text,
            'web_app' => ['url' => $url],
        ];
    }

    public static function callback(string $text, string $callbackData): array
    {
        return [
            'text' => $text,
            'callback_data' => $callbackData,
        ];
    }
}
