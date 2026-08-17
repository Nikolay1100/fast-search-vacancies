<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * Class User
 *
 * @package App\Models
 *
 * @property int $id
 * @property UserStatus $status_id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property int|null $telegram_id
 * @property string|null $telegram_username
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $premium_expires_at
 * @property boolean $is_premium
 *
 * @property-read Collection<int, Keyword> $keywords
 * @property-read Collection<int, UserMatchedPost> $matchedPosts
 * @property-read Collection<int, BannedWord> $bannedWords
 * @property-read Collection<int, Subscription> $subscriptions
 */
final class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'status_id',
        'is_premium',
        'premium_expires_at',
        'name',
        'email',
        'password',
        'telegram_id',
        'telegram_username',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_id' => UserStatus::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'telegram_id' => 'integer',
            'telegram_username' => 'string',
            'is_premium' => 'boolean',
            'premium_expires_at' => 'datetime',
        ];
    }

    public function keywords(): BelongsToMany
    {
        return $this->belongsToMany(Keyword::class)
            ->withPivot('is_active')
            ->withTimestamps();
    }

    public function bannedWords(): BelongsToMany
    {
        return $this->belongsToMany(BannedWord::class, 'banned_keywords_user', 'user_id', 'banned_word_id')
            ->withTimestamps();
    }

    public function matchedPosts(): HasMany
    {
        return $this->hasMany(UserMatchedPost::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Check if user has an active subscription.
     */
    public function isPremium(): bool
    {
        return $this->is_premium && $this->premium_expires_at && $this->premium_expires_at->isFuture();
    }

    public function isAdmin(): bool
    {
        return $this->status_id === UserStatus::ADMIN;
    }

    public function isMember(): bool
    {
        return $this->status_id === UserStatus::MEMBER;
    }
}
