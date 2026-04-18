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
        // On update, ignore the current client's email in unique check
        $clientId = $this->route('client') instanceof \Vendor\Client\Models\Client
            ? $this->route('client')->id
            : $this->route('client');

        $emailRule = $this->isMethod('PUT') || $this->isMethod('PATCH')
            ? "required|email|max:255|unique:clients,email,{$clientId},id,deleted_at,NULL"
            : 'required|email|max:255|unique:clients,email,NULL,id,deleted_at,NULL';

        return [
            'company_name'      => 'required|string|max:255',
            'contact_name'      => 'nullable|string|max:255',
            'email'             => $emailRule,
            'phone'             => 'nullable|string|max:20',
            'mobile'            => 'nullable|string|max:20',
            'website'           => 'nullable|url|max:255',
            'address'           => 'nullable|string|max:500',
            'city'              => 'nullable|string|max:100',
            'postal_code'       => 'nullable|string|max:20',
            'country'           => 'nullable|string|max:100',
            'vat_number'        => 'nullable|string|max:50',
            'siret'             => 'nullable|string|max:50',
            'type'              => 'required|in:entreprise,particulier,startup,association,public',
            'status'            => 'required|in:actif,inactif,en_attente,suspendu',
            'source'            => 'nullable|in:direct,site_web,reference,reseau_social,autre',
            'tags'              => 'nullable|array',
            'tags.*'            => 'string|max:50',
            'revenue'           => 'required|numeric|min:0',
            'potential_value'   => 'nullable|numeric|min:0',
            'payment_term'      => 'nullable|in:immediate,15j,30j,45j,60j',
            'industry'          => 'nullable|string|max:100',
            'employee_count'    => 'nullable|integer|min:0',
            'assigned_to'       => 'nullable|exists:users,id',
            'notes'             => 'nullable|string|max:5000',
            'next_follow_up_at' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'Le nom de l\'entreprise est requis.',
            'email.required'        => 'L\'adresse email est requise.',
            'email.email'           => 'Veuillez saisir une adresse email valide.',
            'email.unique'          => 'Cet email est déjà utilisé par un autre client.',
            'type.required'         => 'Le type de client est requis.',
            'type.in'               => 'Le type sélectionné est invalide.',
            'status.required'       => 'Le statut est requis.',
            'status.in'             => 'Le statut sélectionné est invalide.',
            'revenue.min'           => 'Le chiffre d\'affaires ne peut pas être négatif.',
            'website.url'           => 'Veuillez saisir une URL valide (ex: https://exemple.com).',
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
