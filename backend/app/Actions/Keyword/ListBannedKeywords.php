<?php

declare(strict_types=1);

namespace App\Actions\Keyword;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class ListBannedKeywords
{
      /**
       * Retrieve all non-global stop words associated with the user.
       */
      public function __invoke(User $user): Collection
      {
            return $user->bannedWords()->where('is_global', false)->latest()->get();
      }
}
