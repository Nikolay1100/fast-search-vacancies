<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VacancyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'text' => $this->channelMessage->text,
            'link' => $this->channelMessage->link,
            'keyword' => $this->keyword ? $this->keyword->word : 'Unknown',
            'matched_at' => $this->created_at->format('Y-m-d H:i:s'),
            'channel_id' => $this->channelMessage->channel_telegram_id,
        ];
    }
}
