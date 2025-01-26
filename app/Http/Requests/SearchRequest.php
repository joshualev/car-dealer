<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * For simplicity, we'll allow all users. Adjust as needed.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Change as per your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Customize the validation error messages.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'q.string' => 'The search query must be a valid string.',
            'q.max' => 'The search query may not be greater than 255 characters.',
        ];
    }
}
