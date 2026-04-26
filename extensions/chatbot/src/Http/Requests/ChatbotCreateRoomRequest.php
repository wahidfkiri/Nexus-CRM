<?php

namespace NexusExtensions\Chatbot\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatbotCreateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'string', 'max:60', 'regex:/^(fa-[a-z0-9-]+|(?:fa|fas|far|fal|fad|fab|fat|fa-solid|fa-regular|fa-light|fa-thin|fa-brands)\s+fa-[a-z0-9-]+)$/i'],
            'color' => ['nullable', 'string', 'max:20', 'regex:/^#[0-9A-Fa-f]{3,6}$/'],
            'is_private' => ['nullable', 'boolean'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'room_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du salon est obligatoire.',
            'name.max' => 'Le nom du salon est trop long.',
            'icon.regex' => 'Le format de l icone est invalide.',
            'color.regex' => 'La couleur du salon est invalide.',
            'member_ids.*.exists' => 'Un membre selectionne est invalide.',
        ];
    }
}
