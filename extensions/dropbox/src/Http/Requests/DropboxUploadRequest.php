<?php

namespace NexusExtensions\Dropbox\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DropboxUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'max:102400'],
            'parent_id' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Veuillez selectionner au moins un fichier.',
            'files.array' => 'Le format des fichiers a importer est invalide.',
            'files.min' => 'Veuillez selectionner au moins un fichier.',
            'files.*.required' => 'Un fichier selectionne est invalide.',
            'files.*.file' => 'Un des elements selectionnes n est pas un fichier valide.',
            'files.*.max' => 'Un fichier depasse la limite autorisee de 100 MB.',
        ];
    }
}
