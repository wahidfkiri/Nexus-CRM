<?php

namespace NexusExtensions\Chatbot\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatbotSendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxText = (int) config('chatbot.messages.max_text_length', 10000);
        $maxFileSize = (int) config('chatbot.messages.max_file_size_kb', 10240);
        $mimes = (array) config('chatbot.messages.allowed_mimes', []);
        $extensions = (array) config('chatbot.messages.allowed_extensions', []);
        $maxFiles = 6;

        $fileRules = [
            'file',
            'max:' . $maxFileSize,
        ];

        if (!empty($mimes)) {
            $fileRules[] = 'mimetypes:' . implode(',', $mimes);
        }

        if (!empty($extensions)) {
            $fileRules[] = 'mimes:' . implode(',', $extensions);
        }

        return [
            'room_id' => ['required', 'integer', 'exists:chatbot_rooms,id'],
            'text' => ['nullable', 'string', 'max:' . $maxText],
            'reply_to_message_id' => ['nullable', 'integer', 'exists:chatbot_messages,id'],
            'files' => ['nullable', 'array', 'max:' . $maxFiles],
            'files.*' => $fileRules,
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $text = trim((string) $this->input('text', ''));
            $files = $this->file('files', []);

            if ($text === '' && empty($files)) {
                $validator->errors()->add('text', 'Le message est vide. Ajoutez du texte ou un fichier.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'Le salon est obligatoire.',
            'room_id.exists' => 'Le salon selectionne est invalide.',
            'text.max' => 'Le message est trop long.',
            'files.*.file' => 'Le fichier envoye est invalide.',
            'files.max' => 'Vous pouvez envoyer jusqu a 6 fichiers par message.',
            'files.*.max' => 'Le fichier depasse la taille maximale autorisee.',
            'files.*.mimetypes' => 'Le type de fichier n est pas autorise.',
            'files.*.mimes' => 'L extension du fichier n est pas autorisee.',
        ];
    }
}
