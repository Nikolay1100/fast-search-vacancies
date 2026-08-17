<?php

declare(strict_types=1);

namespace App\Actions\Keyword;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class ListKeywords
{
      public function __invoke(User $user): Collection
      {
            return $user->keywords()->latest()->get();
      }
}
