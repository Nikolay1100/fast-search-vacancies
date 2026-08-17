<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Class BannedWord
 *
 * @package App\Models
 *
 * @property int $id
 * @property string $word
 * @property bool $is_global
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Collection<int, User> $users
 */
class BannedWord extends Model
{
    protected $fillable = [
        'word',
        'is_global',
    ];

    protected $casts = [
        'is_global' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'banned_keywords_user', 'banned_word_id', 'user_id')
            ->withTimestamps();
    }
}

