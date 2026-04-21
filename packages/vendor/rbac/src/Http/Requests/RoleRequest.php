<?php

namespace Vendor\Rbac\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RoleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'label'         => 'required|string|max:100',
            'description'   => 'nullable|string|max:255',
            'color'         => 'nullable|string|max:20|regex:/^#[0-9A-Fa-f]{3,6}$/',
            'is_active'     => 'nullable|boolean',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }

    public function messages(): array
    {
        return [
            'label.required'      => 'Le nom du rôle est requis.',
            'label.max'           => 'Le nom ne peut pas dépasser 100 caractères.',
            'color.regex'         => 'La couleur doit être un code hexadécimal valide (ex: #2563eb).',
            'permissions.*.exists'=> 'Une permission sélectionnée est invalide.',
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