<?php

namespace Vendor\Client\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'company_name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:clients,email',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'vat_number' => 'nullable|string|max:50',
            'siret' => 'nullable|string|max:50',
            'type' => 'required|in:entreprise,particulier,startup,association,public',
            'status' => 'required|in:actif,inactif,en_attente,suspendu',
            'source' => 'required|in:direct,site_web,reference,reseau_social,autre',
            'tags' => 'nullable|array',
            'revenue' => 'nullable|numeric|min:0',
            'potential_value' => 'nullable|numeric|min:0',
            'payment_term' => 'required|in:immediate,15j,30j,45j,60j',
            'industry' => 'nullable|string|max:100',
            'employee_count' => 'nullable|integer|min:0',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:5000',
            'next_follow_up_at' => 'nullable|date',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['email'] = 'required|email|max:255|unique:clients,email,' . $this->client;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'Le nom de l\'entreprise est requis',
            'company_name.max' => 'Le nom de l\'entreprise ne peut pas dépasser 255 caractères',
            'email.required' => 'L\'email est requis',
            'email.email' => 'Veuillez entrer une adresse email valide',
            'email.unique' => 'Cet email est déjà utilisé par un autre client',
            'type.required' => 'Le type de client est requis',
            'type.in' => 'Le type de client sélectionné est invalide',
            'status.required' => 'Le statut est requis',
            'status.in' => 'Le statut sélectionné est invalide',
            'source.required' => 'La source est requise',
            'source.in' => 'La source sélectionnée est invalide',
            'payment_term.required' => 'Le délai de paiement est requis',
            'revenue.min' => 'Le chiffre d\'affaires ne peut pas être négatif',
            'potential_value.min' => 'La valeur potentielle ne peut pas être négative',
            'employee_count.min' => 'Le nombre d\'employés ne peut pas être négatif',
            'website.url' => 'Veuillez entrer une URL valide',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson()) {
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $validator->errors()
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }
}