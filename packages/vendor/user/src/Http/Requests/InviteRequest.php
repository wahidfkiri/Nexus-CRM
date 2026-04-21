<?php

namespace Vendor\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class InviteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email'          => 'required|email|max:255',
            'role_in_tenant' => 'required|in:admin,manager,user,viewer',
            'message'        => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'          => 'L\'adresse email est requise.',
            'email.email'             => 'Veuillez saisir un email valide.',
            'role_in_tenant.required' => 'Le rôle est requis.',
            'role_in_tenant.in'       => 'Rôle invalide. Valeurs acceptées : admin, manager, user, viewer.',
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