<?php

declare(strict_types=1);

namespace App\Actions\Keyword;

use App\Models\BannedWord;
use App\Models\User;

final class CreateBannedKeyword
{
      /**
       * Create a stop word (or fetch it if already exists as non-global) and link it to the user.
       */
      public function __invoke(User $user, array $data): BannedWord
      {
            $bannedWord = BannedWord::firstOrCreate(
                  ['word' => $data['word']],
                  ['is_global' => false]
            );

            $user->bannedWords()->syncWithoutDetaching([$bannedWord->id]);

            return $bannedWord;
      }
}
