<?php

declare(strict_types=1);

namespace App\Actions\Keyword;

use App\Models\Keyword;
use App\Models\User;

final class CreateKeyword
{
      public function __invoke(User $user, array $data): Keyword
      {
            $keyword = Keyword::firstOrCreate(['word' => mb_strtolower($data['word'])]);

            $user->keywords()->syncWithoutDetaching([
                  $keyword->id => ['is_active' => true]
            ]);

            return $keyword;
      }
}
