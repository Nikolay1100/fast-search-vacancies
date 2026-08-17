<?php

declare(strict_types=1);

namespace App\Actions\Channel;

use App\Models\Channel;

final class SyncChannels
{
      /**
       * @param array<array{channel_id: int|string, name: string}> $channelsData
       */
      public function __invoke(array $channelsData): void
      {
            foreach ($channelsData as $syncItem) {
                  Channel::updateOrCreate(
                        ['channel_telegram_id' => $syncItem['channel_id']],
                        ['name' => $syncItem['name'], 'is_active' => true]
                  );
            }
      }
}
