<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ChannelMessage;
use App\Services\Telegram\Keyboard\Button;
use App\Services\Telegram\Keyboard\InlineKeyboard;
use App\Services\Telegram\TelegramClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendTelegramNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $telegramId,
        private readonly ChannelMessage $message,
        private readonly ?string $matchedKeyword = null
    ) {
    }

    public function handle(TelegramClient $client): void
    {
        $notification = view('telegram.vacancy', [
            'extractedData' => $this->message->extracted_data ?? [],
            'matchedKeyword' => $this->matchedKeyword,
        ])->render();

        $keyboard = InlineKeyboard::make();

        if (!empty($this->message->link)) {
            $keyboard->addRow(Button::url('➡️ Оригинал поста', $this->message->link));
        }

        $client->sendMessage($this->telegramId, $notification, 'HTML', $keyboard->toArray());
    }
}
