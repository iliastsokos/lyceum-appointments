<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolClassRequest extends FormRequest
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
            // Plain ASCII only (e.g. "A1", not the Greek "Α1"): Excel import
            // matches this name literally against a typed spreadsheet cell,
            // and Greek "Α" / Latin "A" are different, visually-identical
            // Unicode characters — a classic source of silent mismatches.
            'name' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/', 'unique:school_classes,name'],
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
