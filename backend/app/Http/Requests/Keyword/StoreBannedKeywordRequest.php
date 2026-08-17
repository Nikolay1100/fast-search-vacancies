<?php

declare(strict_types=1);

namespace App\Http\Requests\Keyword;

use App\Models\BannedWord;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBannedKeywordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'word' => [
                'required',
                'string',
                'min:2',
                'max:20',
                function ($attribute, $value, $fail) {
                    $globalExists = BannedWord::where('word', $value)
                        ->where('is_global', true)
                        ->exists();

                    if ($globalExists) {
                        $fail('This word is already blocked globally.');
                        return;
                    }

                    $userExists = BannedWord::where('word', $value)
                        ->whereHas('users', function ($query) {
                            $query->where('users.id', $this->user()->id);
                        })
                        ->exists();

                    if ($userExists) {
                        $fail('You have already added this stop word.');
                    }
                },
            ],
        ];
    }
}
