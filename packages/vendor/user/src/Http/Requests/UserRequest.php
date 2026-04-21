<?php

namespace Vendor\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $userId = $this->route('user') instanceof \App\Models\User
            ? $this->route('user')->id
            : $this->route('user');

        $emailRule = $this->isMethod('PUT') || $this->isMethod('PATCH')
            ? "required|email|max:255|unique:users,email,{$userId}"
            : 'required|email|max:255|unique:users,email';

        return [
            'name'           => 'required|string|max:255',
            'email'          => $emailRule,
            'phone'          => 'nullable|string|max:30',
            'job_title'      => 'nullable|string|max:100',
            'department'     => 'nullable|string|max:100',
            'role_in_tenant' => 'required|in:owner,admin,manager,user,viewer',
            'status'         => 'required|in:active,inactive,invited,suspended',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'Le nom est requis.',
            'email.required'          => 'L\'email est requis.',
            'email.email'             => 'Format email invalide.',
            'email.unique'            => 'Cet email est déjà utilisé.',
            'role_in_tenant.required' => 'Le rôle est requis.',
            'role_in_tenant.in'       => 'Rôle invalide.',
            'status.required'         => 'Le statut est requis.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Erreurs de validation.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}