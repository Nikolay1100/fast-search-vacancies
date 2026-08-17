<?php

declare(strict_types=1);

namespace App\Actions\Channel;

use App\Models\Channel;
use Illuminate\Database\Eloquent\Collection;

final class ListChannels
{
      public function __invoke(): Collection
      {
            return Channel::orderBy('name')->get();
      }
}
