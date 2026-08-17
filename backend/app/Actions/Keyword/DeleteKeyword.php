<?php

declare(strict_types=1);

namespace App\Actions\Keyword;

use App\Models\Keyword;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class DeleteKeyword
{
      /**
       * @throws AuthorizationException
       */
      public function __invoke(User $user, Keyword $keyword): void
      {
            // Instead of deleting the keyword globally, we detach it from the specific user
            $user->keywords()->detach($keyword->id);

            // Optional: Clean up keyword if no users are using it anymore
            if ($keyword->users()->count() === 0) {
                  $keyword->delete();
            }
      }
}
