<?php

namespace Vendor\GoogleCalendar\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoogleCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'calendar_id' => ['nullable', 'string', 'max:255'],
            'summary' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:8000'],
            'location' => ['nullable', 'string', 'max:500'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'all_day' => ['nullable', 'boolean'],
            'timezone' => ['nullable', 'string', 'max:80'],
            'attendees' => ['nullable', 'string', 'max:3000'],
            'visibility' => ['nullable', 'in:default,public,private,confidential'],
            'transparency' => ['nullable', 'in:opaque,transparent'],
            'color_id' => ['nullable', 'string', 'max:20'],
            'reminder_minutes' => ['nullable', 'integer', 'min:1', 'max:40320'],
            'recurrence' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'summary.required' => 'Event title is required.',
            'start_at.required' => 'Start date is required.',
            'end_at.required' => 'End date is required.',
            'end_at.after' => 'End date must be after start date.',
        ];
    }
}
