<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/',
                Rule::unique('school_classes', 'name')->ignore($this->route('school_class')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'Χρησιμοποιήστε μόνο λατινικούς χαρακτήρες και αριθμούς (π.χ. A1), όχι ελληνικούς.',
        ];
    }
}
