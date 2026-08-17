<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class UserMatchedPost
 *
 * @package App\Models
 *
 * @property int $id
 * @property int $user_id
 * @property int $channel_message_id
 * @property int|null $keyword_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read User $user
 * @property-read ChannelMessage $channelMessage
 * @property-read Keyword|null $keyword
 */
class UserMatchedPost extends Model
{
    use MassPrunable;

    protected $fillable = [
        'user_id',
        'channel_message_id',
        'keyword_id',
    ];

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        return $this->newQuery()->where('created_at', '<=', now()->subDays(3));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function channelMessage(): BelongsTo
    {
        return $this->belongsTo(ChannelMessage::class);
    }

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }
}
