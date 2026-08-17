<?php

declare(strict_types=1);

namespace App\Actions\Telegram;

use App\Models\User;

final class RegisterTelegramUser
{
    /**
     * @param array{id: int, first_name: string, last_name?: string, username?: string} $data
     */
    public function __invoke(array $data): User
    {
        $name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

        $telegram_username = $data['username'];

        if (empty($name)) {
            $name = $data['username'] ?? "User {$data['id']}";
        }

        return User::updateOrCreate(
            ['telegram_id' => $data['id']],
            [
                'name' => $name,
                'telegram_username' => $telegram_username,
            ]
        );
    }
}
