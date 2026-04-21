<?php

namespace NexusExtensions\GoogleDrive\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoogleDriveFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:500'],
            'parent_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}

