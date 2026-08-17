<?php

declare(strict_types=1);

namespace App\Support;

class Text
{
    public function normalize(string $text): string
    {
        // 0. Remove URLs (http/https/ftp)
        $text = strip_tags($text);

        // 1. Remove URLs (http/https/ftp)
        $text = preg_replace('/https?:\/\/[^\s]+/i', '', $text);

        // 2. Remove t.me links specifically (even without https)
        $text = preg_replace('/t\.me\/[^\s]+/i', '', $text);

        // 3. Remove emails
        $text = preg_replace('/[\w\.-]+@[\w\.-]+\.\w+/i', '', $text);

        // 4. Remove Telegram mentions (@username)
        $text = preg_replace('/@\w+/i', '', $text);

        // 5. Lowercase
        $text = mb_strtolower($text, 'UTF-8');

        // 6. Remove garbage, emojis, punctuation (keep only letters, numbers, spaces, and #)
        $text = preg_replace('/[^\p{L}\p{N}\s#]+/u', '', $text);

        // Normalize multiple spaces into single space and trim
        return trim(preg_replace('/\s+/', ' ', $text));
    }
}
