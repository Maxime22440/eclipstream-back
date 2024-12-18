<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEpisodeRequest extends FormRequest
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
            'episode_number' => 'required|integer|min:1',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'release_date' => 'nullable|date',
            'imdb_rating' => 'nullable|numeric|min:0|max:10',
            'duration' => 'required|integer',
            'admin_password' => 'required|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'episode_number.required' => 'Le numéro de l\'épisode est requis.',
            'episode_number.integer' => 'Le numéro de l\'épisode doit être un entier.',
            'title.required' => 'Le titre de l\'épisode est requis.',
            'title.max' => 'Le titre ne peut pas dépasser 255 caractères.',
            'imdb_rating.numeric' => 'La note IMDb doit être un nombre.',
            'imdb_rating.min' => 'La note IMDb doit être d\'au moins 0.',
            'imdb_rating.max' => 'La note IMDb ne peut pas dépasser 10.',
            'duration.required' => 'La durée est requise.',
            'duration.integer' => 'La durée doit être un entier.',
            'release_date.date' => 'La date de sortie doit être une date valide.',
            'admin_password.required' => 'Le mot de passe administrateur est requis.',
        ];
    }
}
