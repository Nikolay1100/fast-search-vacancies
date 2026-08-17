<?php

declare(strict_types=1);

namespace App\Actions\Keyword;

use App\Models\BannedWord;
use App\Models\User;

final class DeleteBannedKeyword
{
      /**
       * Detach the stop word from the user, and clean up BannedWord if unused and non-global.
       */
      public function __invoke(User $user, BannedWord $bannedWord): void
      {
            $user->bannedWords()->detach($bannedWord->id);

            // Clean up the banned word if it's no longer used by anyone and isn't global
            if (!$bannedWord->is_global && $bannedWord->users()->count() === 0) {
                  $bannedWord->delete();
            }
      }
}
