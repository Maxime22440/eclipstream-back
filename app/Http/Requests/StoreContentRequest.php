<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StoreContentRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'release_date' => 'required|date',
            'imdb_rating' => 'required|numeric|between:0,10',
            'duration' => 'required|integer',
            'country' => 'required|string|max:100',
            'actors' => 'nullable|string',
            'genres' => 'required|array',
            'poster' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'rewarded' => 'required|boolean',
            'saga' => 'nullable|string|max:255',
            'type' => 'required|string|in:movie,series,anime-movie,anime-series',
            'admin_password' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Le titre du film est requis.',
            'title.string' => 'Le titre doit être une chaîne de caractères.',
            'title.max' => 'Le titre ne peut pas dépasser 255 caractères.',

            'description.required' => 'La description du film est requise.',
            'description.string' => 'La description doit être une chaîne de caractères.',

            'release_date.required' => 'La date de sortie est requise.',
            'release_date.date' => 'La date de sortie doit être une date valide.',

            'imdb_rating.required' => 'La note IMDb est requise.',
            'imdb_rating.numeric' => 'La note IMDb doit être un nombre.',
            'imdb_rating.between' => 'La note IMDb doit être comprise entre 0 et 10.',

            'duration.required' => 'La durée est requise.',
            'duration.integer' => 'La durée doit être un entier.',

            'country.required' => 'Le pays d\'origine est requis.',
            'country.string' => 'Le pays doit être une chaîne de caractères.',
            'country.max' => 'Le nom du pays ne peut pas dépasser 100 caractères.',

            'actors.string' => 'Les acteurs doivent être une chaîne de caractères.',

            'genres.required' => 'Vous devez sélectionner au moins un genre.',
            'genres.array' => 'Les genres doivent être sous forme de liste.',

            'poster.required' => 'L\'affiche (poster) est requise.',
            'poster.image' => 'Le fichier de l\'affiche doit être une image.',
            'poster.mimes' => 'Le format de l\'affiche doit être jpeg, png, jpg, ou gif.',
            'poster.max' => 'L\'affiche ne peut pas dépasser 2 Mo.',

            'thumbnail.required' => 'La miniature est requise.',
            'thumbnail.image' => 'Le fichier de la miniature doit être une image.',
            'thumbnail.mimes' => 'Le format de la miniature doit être jpeg, png, jpg, ou gif.',
            'thumbnail.max' => 'La miniature ne peut pas dépasser 1 Mo.',

            'rewarded.required' => 'Le champ "Récompense" est requis.',
            'rewarded.boolean' => 'La récompense doit être soit "Oui" soit "Non".',

            'saga.string' => 'La saga doit être une chaîne de caractères.',
            'saga.max' => 'Le nom de la saga ne peut pas dépasser 255 caractères.',

            'type.required' => 'Le type de contenu est requis.',
            'type.string' => 'Le type de contenu doit être une chaîne de caractères.',
            'type.in' => 'Le type de contenu doit être l\'un des suivants : movie, series, anime-movie, anime-series.',

            'admin_password.required' => 'Le mot de passe administrateur est requis.',
        ];
    }

}
