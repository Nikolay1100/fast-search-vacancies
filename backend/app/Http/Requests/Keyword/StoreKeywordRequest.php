<?php

declare(strict_types=1);

namespace App\Http\Requests\Keyword;

use App\Models\Keyword;
use Illuminate\Foundation\Http\FormRequest;

class StoreKeywordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'word' => [
                'required',
                'string',
                'min:2',
                'max:20',
                function ($attribute, $value, $fail) {
                    $exists = Keyword::where('word', $value)
                        ->whereHas('users', function ($query) {
                            $query->where('users.id', $this->user()->id);
                        })
                        ->exists();

                    if ($exists) {
                        $fail('You have already added this keyword.');
                    }
                },
            ],
        ];
    }
}
