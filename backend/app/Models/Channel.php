<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Class Channel
 *
 * @package App\Models
 *
 * @property int $id
 * @property string $name
 * @property int $channel_telegram_id
 * @property string|null $username
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Channel extends Model
{
    protected $fillable = [
        'name',
        'channel_telegram_id',
        'username',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
