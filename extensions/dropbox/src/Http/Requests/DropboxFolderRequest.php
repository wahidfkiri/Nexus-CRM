<?php

namespace NexusExtensions\Dropbox\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DropboxFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'string', 'max:500'],
        ];
    }
}
