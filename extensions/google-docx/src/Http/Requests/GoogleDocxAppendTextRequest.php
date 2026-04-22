<?php

namespace NexusExtensions\GoogleDocx\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoogleDocxAppendTextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:50000'],
        ];
    }
}
