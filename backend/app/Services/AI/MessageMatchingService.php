<?php

declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MessageMatchingService
{
    private string $apiKey;
    private string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-lite:generateContent';

    public function __construct()
    {
        $keys = config('services.ai.api_keys', []);

        if (is_array($keys) && count($keys) > 0) {
            if (count($keys) > 1) {
                $index = Cache::increment('gemini_api_key_index');
                $this->apiKey = $keys[$index % count($keys)];
            } else {
                $this->apiKey = $keys[0];
            }
        } else {
            $this->apiKey = config('services.ai.api_key', '');
        }
    }

    /**
     * Extracts structured data from a job vacancy text.
     *
     * @param string $text
     * @return array|null
     */
    public function extractEntities(string $text): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('Gemini API key is not set. Skipping AI extraction.');
            return null;
        }

        $systemPrompt = <<<PROMPT
Ты опытный IT-рекрутер. Твоя задача - извлечь информацию из текста. Понять, что это текст именно вакансии, а не какой-то другой (например, рекламный пост или сообщение от соискателя с резюме) и вернуть строго валидный JSON.
Не пиши ничего кроме JSON.

Формат JSON, который ты должен вернуть если нашёл текст с вакансией:
{
    "technologies": ["массив технологий, языков программирования, фреймворков и инструментов (на английском, в нижнем регистре, например: 'c++', 'go', 'node.js')"],
    "role": "роль или профессия (в нижнем регистре, например: 'backend developer', 'devops')",
    "short_description": "краткое описание вакансии (1-2 предложения, на русском языке) или null если непонятно",
    "grade": "грейд (junior, middle, senior, lead) или null если не указан",
    "format": "формат работы (remote, office, hybrid) или null если не указан",
    "salary_min": число (минимальная зарплата в валюте вакансии) или null
}

Формат JSON, который ты должен вернуть если нашёл пост но вакансии в ней нет:
{
    "text_about": О чем пост (1-2 предложение, на русском языке, в нижнем регистре)
}
Формат JSON, который ты должен вернуть если нашёл пост с текстом, который содержит агрегатор множества вакансий:
{
    "roles": Список вакансий в посте (на английском, в нижнем регистре, например: 'backend developer', 'devops')
}
PROMPT;

        //todo отключить отображение сайта через десктоп

        //todo посмотреть индексы в Postrgre мигграциях
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => "Текст вакансии:\n" . $text]
                    ]
                ]
            ],
            'system_instruction' => [
                'parts' => [
                    ['text' => $systemPrompt]
                ]
            ],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
            ]
        ];

        try {
            //Todo сделать ретрай подключения
            $response = Http::post($this->apiUrl . '?key=' . $this->apiKey, $payload);

            if ($response->successful()) {
                $data = $response->json();

                $jsonText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                if (empty($jsonText)) {
                    return null;
                }

                $decoded = json_decode($jsonText, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }

                Log::error('MessageMatchingService: Failed to decode JSON from Gemini', [
                    'jsonText' => $jsonText,
                    'error' => json_last_error_msg()
                ]);

                return null;
            }

            Log::error('MessageMatchingService: Gemini API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('MessageMatchingService: Exception during API call', [
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }
}
