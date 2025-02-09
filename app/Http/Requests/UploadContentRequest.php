<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadContentRequest extends FormRequest
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
     * Définit les règles de validation pour cette requête.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'video'        => 'required|file|mimetypes:video/mp4,video/x-matroska,video/avi|max:204800',
            'content_uuid' => 'required|string',
        ];
    }
}
