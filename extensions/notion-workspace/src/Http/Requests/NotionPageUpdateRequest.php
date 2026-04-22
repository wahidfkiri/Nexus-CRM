<?php

namespace NexusExtensions\NotionWorkspace\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotionPageUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:220'],
            'parent_id' => ['nullable', 'integer', 'exists:notion_pages,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'icon' => ['nullable', 'string', 'max:20'],
            'cover_color' => ['nullable', 'string', 'max:20'],
            'visibility' => ['nullable', 'in:private,team,public'],
            'content_text' => ['nullable', 'string'],
            'content_json' => ['nullable'],
            'is_template' => ['nullable', 'boolean'],
            'is_favorite' => ['nullable', 'boolean'],
            'is_archived' => ['nullable', 'boolean'],
        ];
    }
}

