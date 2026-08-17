<?php

declare(strict_types=1);

namespace App\Actions\Keyword;

use App\Models\BannedWord;
use App\Models\Keyword;
use App\Models\User;
use Illuminate\Support\Collection;

final class FindMatches
{
      /**
       * Returns a collection of users whose keywords are found in the text.
       */
      public function __invoke(string $text): Collection
      {
            // 0. Check for global banned words
            $bannedWords = BannedWord::query()->where('is_global', true)->pluck('word');
            foreach ($bannedWords as $bannedWord) {
                  if (mb_stripos($text, $bannedWord) !== false) {
                        return collect();
                  }
            }

            $matchingKeywordIds = Keyword::query()
                  ->whereHas('users', function ($query) {
                        $query->where('keyword_user.is_active', true);
                  })
                  ->get()
                  ->filter(fn($keyword) => mb_stripos($text, $keyword->word) !== false)
                  ->pluck('id');

            if ($matchingKeywordIds->isEmpty()) {
                  return collect();
            }

            // 2. Fetch all unique users who have any of these keywords active
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

            // 3. Filter out users who have a matching user-specific stop word
            return $users->filter(function ($user) use ($text) {
                  foreach ($user->bannedWords as $bannedWord) {
                        if (mb_stripos($text, $bannedWord->word) !== false) {
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
