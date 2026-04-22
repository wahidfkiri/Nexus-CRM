<?php

namespace NexusExtensions\GoogleDocx\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoogleDocxReplaceTextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['required', 'string', 'max:500'],
            'replace' => ['nullable', 'string', 'max:50000'],
            'match_case' => ['nullable', 'boolean'],
        ];
    }
}
