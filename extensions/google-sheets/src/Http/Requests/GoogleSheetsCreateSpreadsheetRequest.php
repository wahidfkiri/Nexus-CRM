<?php

namespace NexusExtensions\GoogleSheets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoogleSheetsCreateSpreadsheetRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:500'],
            'sheet_titles' => ['nullable', 'array', 'max:10'],
            'sheet_titles.*' => ['string', 'max:100'],
        ];
    }
}