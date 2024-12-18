<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSeasonRequest extends FormRequest
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
            'season_number' => 'required|integer|min:1',
            'total_episodes' => 'nullable|integer|min:1',
            'admin_password' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'season_number.required' => 'Le numéro de la saison est requis.',
            'season_number.integer' => 'Le numéro de la saison doit être un nombre entier.',
            'total_episodes.integer' => 'Le nombre total d\'épisodes doit être un nombre entier.',
            'admin_password.required' => 'Le mot de passe administrateur est requis.',
        ];
    }
}
