<?php

declare(strict_types=1);

namespace App\Actions\Vacancy;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListVacancies
{
      public function __invoke(User $user): LengthAwarePaginator
      {
            return $user->matchedPosts()
                  ->with(['channelMessage', 'keyword'])
                  ->latest()
                  ->paginate();
      }
}
