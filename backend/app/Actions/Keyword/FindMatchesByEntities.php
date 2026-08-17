<?php

declare(strict_types=1);

namespace App\Actions\Keyword;

use App\Models\BannedWord;
use App\Models\Keyword;
use App\Models\User;
use Illuminate\Support\Collection;

final class FindMatchesByEntities
{
    /**
     * Returns a collection of users whose keywords match the extracted entities.
     *
     * @param string $originalText The raw message text (used for stop-words)
     * @param array $entities Structured data from AI
     * @return Collection
     */
    public function __invoke(string $originalText, array $entities): Collection
    {
        // 0. Check for global banned words on original text
        $bannedWords = BannedWord::query()->where('is_global', true)->pluck('word');
        foreach ($bannedWords as $bannedWord) {
            if (mb_stripos($originalText, $bannedWord) !== false) {
                return collect();
            }
        }

        // 1. Prepare tags from AI entities for matching
        $tags = $entities['technologies'] ?? [];
        if (!empty($entities['role'])) {
            $tags[] = $entities['role'];
        }
        if (!empty($entities['grade'])) {
            $tags[] = $entities['grade'];
        }
        if (!empty($entities['format'])) {
            $tags[] = $entities['format'];
        }

        // Clean and lowercase all tags
        $tags = array_filter(array_map(fn($tag) => mb_strtolower(trim((string)$tag)), $tags));

        // 2. Find matching keyword IDs
        $matchingKeywordIds = Keyword::query()
            ->whereHas('users', function ($query) {
                $query->where('keyword_user.is_active', true);
            })
            ->get()
            ->filter(function ($keyword) use ($tags) {
                $word = mb_strtolower(trim($keyword->word));
                foreach ($tags as $tag) {
                    if ($tag === $word) {
                        return true;
                    }

                    $pattern = '/(?<![a-z0-9а-яё_])' . preg_quote($word, '/') . '(?![a-z0-9а-яё_])/iu';
                    if (preg_match($pattern, $tag)) {
                        return true;
                    }
                }
                return false;
            })
            ->pluck('id');

        if ($matchingKeywordIds->isEmpty()) {
            return collect();
        }

        // 3. Fetch all unique users who have any of these keywords active
        $users = User::query()
            ->select('users.id', 'users.telegram_id')
            ->with(['bannedWords' => function ($query) {
                $query->where('is_global', false);
            }])
            ->with(['keywords' => function ($query) use ($matchingKeywordIds) {
                $query->whereIn('keyword_id', $matchingKeywordIds)
                    ->where('keyword_user.is_active', true);
            }])
            ->whereHas('keywords', function ($query) use ($matchingKeywordIds) {
                $query->whereIn('keyword_id', $matchingKeywordIds)
                    ->where('keyword_user.is_active', true);
            })
            ->whereNotNull('telegram_id')
            ->get();

        // 4. Filter out users who have a matching user-specific stop word
        return $users->filter(function ($user) use ($originalText) {
            foreach ($user->bannedWords as $bannedWord) {
                if (mb_stripos($originalText, $bannedWord->word) !== false) {
                    return false;
                }
            }
            return true;
        })->map(function ($user) {
            $user->matched_keyword_id = $user->keywords->first()?->id;
            return $user;
        })->values();
    }
}
