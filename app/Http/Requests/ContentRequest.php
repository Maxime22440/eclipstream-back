<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContentRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content_title' => 'required|string|max:255|regex:/^[a-zA-Z0-9\s\'\-!?.]+$/',
            'content_type' => 'nullable|string|in:movie,series,anime'
        ];
    }

    /**
     * Messages d'erreurs personnalisés (optionnel).
     */
    public function messages(): array
    {
        return [
            'content_title.required' => 'Le titre du contenu est requis.',
            'content_title.regex' => 'Le titre contient des caractères non autorisés.',
            'content_type.in' => 'Le type de contenu doit être film, série, ou animé.',
        ];
    }
}
