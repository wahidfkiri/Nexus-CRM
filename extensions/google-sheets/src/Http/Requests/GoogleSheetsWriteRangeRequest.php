<?php

namespace NexusExtensions\GoogleSheets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoogleSheetsWriteRangeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'range'    => ['required', 'string', 'max:255'],
            'values'   => ['required', 'array'],
            'values.*' => ['array'],
        ];
    }
}