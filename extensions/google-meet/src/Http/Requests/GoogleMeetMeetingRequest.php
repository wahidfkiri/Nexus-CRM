<?php

namespace NexusExtensions\GoogleMeet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoogleMeetMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'calendar_id' => ['nullable', 'string', 'max:255'],
            'summary' => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string', 'max:8000'],
            'location' => ['nullable', 'string', 'max:500'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'timezone' => ['nullable', 'string', 'max:80'],
            'attendees' => ['nullable', 'string', 'max:3000'],
            'visibility' => ['nullable', 'in:default,public,private,confidential'],
            'send_updates' => ['nullable', 'in:all,externalOnly,none'],
            'create_meet_link' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'summary.required' => 'Le titre de la reunion est obligatoire.',
            'summary.min' => 'Le titre doit contenir au moins 2 caracteres.',
            'start_at.required' => 'La date de debut est obligatoire.',
            'end_at.required' => 'La date de fin est obligatoire.',
            'end_at.after' => 'La date de fin doit etre apres la date de debut.',
            'send_updates.in' => 'La valeur de notification est invalide.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $raw = trim((string) $this->input('attendees', ''));
            if ($raw === '') {
                return;
            }

            $emails = preg_split('/[,;\n]+/', $raw) ?: [];
            $invalid = [];

            foreach ($emails as $email) {
                $candidate = trim($email);
                if ($candidate === '') {
                    continue;
                }

                if (preg_match('/<([^>]+)>/', $candidate, $matches) === 1) {
                    $candidate = trim((string) $matches[1]);
                }

                $candidate = trim($candidate, " \t\n\r\0\x0B\"'");

                if (!filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                    $invalid[] = $candidate;
                }
            }

            if (!empty($invalid)) {
                $validator->errors()->add(
                    'attendees',
                    'Un ou plusieurs emails participants sont invalides.'
                );
            }
        });
    }
}
