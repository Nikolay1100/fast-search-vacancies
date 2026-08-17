<?php

declare(strict_types=1);

namespace App\Services\Messages;

use App\Support\Text;
use App\Actions\Keyword\FindMatches;
use App\Actions\Keyword\FindMatchesByEntities;
use App\Services\AI\MessageMatchingService;
use App\Jobs\SendTelegramNotificationJob;
use App\Models\ChannelMessage;
use App\Models\UserMatchedPost;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

readonly class IncomingMessageService
{
    public const  REGULAR_USER_DELAY_MINUTES = 1;
    public const  PREMIUM_USER_DELAY_MINUTES = 0;

    public function __construct(
        private FindMatches            $findMatchesFallback,
        private FindMatchesByEntities  $findMatchesByEntities,
        private MessageMatchingService $aiService,
        private Text                   $textHelper
    )
    {
    }

    /**
     * Process the incoming message.
     *
     * @param array{message_id: int, channel_telegram_id: int|string, link?: ?string} $params
     * @throws Throwable
     */
    public function handle(string $text, array $params): void
    {
        // 0. Check if message is already in the DB
        if ($this->isMessageAlreadyProcessed($params)) {
            return;
        }

        // 1. Text normalize (used for hashing and fallback)
        $normalizedText = $this->textHelper->normalize($text);

        // 2. Extract entities via AI
        $entities = $this->aiService->extractEntities($text);

        //TODO убрать логирование извленченного JSON позже
        \Log::info($entities);

        // 3. Search for matches
        if ($entities !== null) {
            $users = ($this->findMatchesByEntities)($normalizedText, $entities);
        } else {
            // Fallback to old regex matching if AI is down or returns null
            $users = ($this->findMatchesFallback)($normalizedText);
        }

        if ($users->isEmpty()) {
            return;
        }

        // 4. ONLY save the message if we have matches
        $msg = $this->storeMessage($text, $params, $entities);
        if ($msg === null) {
            return;
        }

        // 5. Calculate a normalized hash of the text to prevent duplicates (same text in different channels)
        $textHash = $this->hash($normalizedText);

        // 6. Notify matched users and save matches
        foreach ($users as $user) {
            $cacheKey = "user_{$user->id}_msg_{$textHash}";

            // If user recently received this exact text, skip
            if (Cache::has($cacheKey)) {
                continue;
            }

            // Save match for archive
            $match = UserMatchedPost::firstOrCreate([
                'user_id' => $user->id,
                'channel_message_id' => $msg->id,
            ], [
                'keyword_id' => $user->matched_keyword_id ?? null,
            ]);

            // Only send notification if this is a NEW match
            if ($match->wasRecentlyCreated) {
                $delayMinutes = ($user->isPremium() || $user->isMember())
                    ? self::PREMIUM_USER_DELAY_MINUTES
                    : self::REGULAR_USER_DELAY_MINUTES;

                $job = SendTelegramNotificationJob::dispatch(
                    (string)$user->telegram_id,
                    $msg,
                    $user->keywords->first()?->word
                );

                if ($delayMinutes > 0) {
                    $job->delay(now()->addMinutes($delayMinutes));
                }

                // Remember that user got this message text for 24 hours
                Cache::put($cacheKey, true, now()->addDay());
            }
        }
    }

    /**
     * Check if the message was already processed and stored.
     */
    private function isMessageAlreadyProcessed(array $params): bool
    {
        return ChannelMessage::where('channel_telegram_id', $params['channel_telegram_id'] ?? 0)
            ->where('message_id', $params['message_id'] ?? 0)
            ->exists();
    }

    /**
     * Persist the channel message to the database safely.
     */
    private function storeMessage(string $text, array $params, ?array $entities): ?ChannelMessage
    {
        try {
            return ChannelMessage::updateOrCreate(
                [
                    'channel_telegram_id' => $params['channel_telegram_id'] ?? 0,
                    'message_id' => $params['message_id'] ?? 0,
                ],
                [
                    'text' => $text,
                    'link' => $params['link'] ?? null,
                    'extracted_data' => $entities,
                ]
            );
        } catch (\Exception $e) {
            Log::error("IncomingMessageService: Failed to sync channel message.", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Calculate a normalized hash of the text.
     */
    private function hash($text): string
    {
        return md5($text);
    }
}
