<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Class ChannelMessage
 *
 * @package App\Models
 *
 * @property int $id
 * @property int $channel_telegram_id
 * @property int $message_id
 * @property string $text
 * @property string|null $link
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ChannelMessage extends Model
{
    protected $fillable = [
        'channel_telegram_id',
        'message_id',
        'text',
        'link',
        'extracted_data',
    ];

    protected $casts = [
        'extracted_data' => 'array',
    ];
}
