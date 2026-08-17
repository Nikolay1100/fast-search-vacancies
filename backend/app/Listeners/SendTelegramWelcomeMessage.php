<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TelegramUserRegistered;
use App\Services\Telegram\Keyboard\Button;
use App\Services\Telegram\Keyboard\InlineKeyboard;
use App\Services\Telegram\TelegramClient;
use Illuminate\Support\Facades\View;

readonly class SendTelegramWelcomeMessage
{
    public function __construct(
        private TelegramClient $telegramClient
    ) {}

    public function handle(TelegramUserRegistered $event): void
    {
        $welcomeText = View::make('telegram.welcome', ['user' => $event->user])->render();

        $webAppUrl = config('services.telegram.webapp_url');

        $replyMarkup = InlineKeyboard::make()
            ->addRow(Button::webApp('⚙️ Настроить слова для поиска', $webAppUrl))
            ->toArray();

        $this->telegramClient->sendMessage(
            chatId: $event->chatId,
            text: $welcomeText,
            replyMarkup: $replyMarkup
        );
    }
}
