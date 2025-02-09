<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadEpisodeRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation pour l'upload d'un épisode.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'video'        => 'required|file|mimetypes:video/mp4,video/x-matroska,video/avi|max:204800',
            'episode_uuid' => 'required|string',
        ];
    }
}
