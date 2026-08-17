<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Keyword;
use App\Models\User;

class KeywordPolicy
{
    private const USER_KEY_WORDS_LIMIT = 2;
    private const PREMIUM_USER_KEY_WORDS_LIMIT = 10;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $limit = $user->isPremium() ? self::PREMIUM_USER_KEY_WORDS_LIMIT : self::USER_KEY_WORDS_LIMIT;

        return $user->keywords()->count() < $limit;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Keyword $keyword): bool
    {
        return $user->id === $keyword->user_id;
    }
}
