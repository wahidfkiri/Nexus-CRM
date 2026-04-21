<?php

namespace Vendor\Extensions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ExtensionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('extension') instanceof \Vendor\Extensions\Models\Extension
            ? $this->route('extension')->id
            : $this->route('extension');

        $slugRule = $this->isMethod('PUT') || $this->isMethod('PATCH')
            ? "nullable|string|max:100|unique:extensions,slug,{$id}"
            : 'nullable|string|max:100|unique:extensions,slug';

        return [
            'name'               => 'required|string|max:150',
            'slug'               => $slugRule,
            'tagline'            => 'nullable|string|max:255',
            'description'        => 'nullable|string|max:500',
            'long_description'   => 'nullable|string',
            'version'            => 'nullable|string|max:20',
            'category'           => 'required|in:' . implode(',', array_keys(config('extensions.categories', []))),
            'icon'               => 'nullable|string|max:100',
            'icon_file'          => 'nullable|image|max:' . config('extensions.upload.max_size_kb', 2048),
            'icon_bg_color'      => 'nullable|string|max:20|regex:/^#[0-9A-Fa-f]{3,6}$/',
            'banner_file'        => 'nullable|image|max:' . (config('extensions.upload.max_size_kb', 2048) * 2),
            'developer_name'     => 'nullable|string|max:150',
            'developer_url'      => 'nullable|url|max:255',
            'documentation_url'  => 'nullable|url|max:255',
            'support_url'        => 'nullable|url|max:255',
            'pricing_type'       => 'required|in:' . implode(',', array_keys(config('extensions.pricing_types', []))),
            'price'              => 'nullable|numeric|min:0',
            'currency'           => 'nullable|string|size:3',
            'billing_cycle'      => 'nullable|in:' . implode(',', array_keys(config('extensions.billing_cycles', []))),
            'yearly_price'       => 'nullable|numeric|min:0',
            'has_trial'          => 'nullable|boolean',
            'trial_days'         => 'nullable|integer|min:1|max:365',
            'status'             => 'required|in:' . implode(',', array_keys(config('extensions.extension_statuses', []))),
            'is_featured'        => 'nullable|boolean',
            'is_new'             => 'nullable|boolean',
            'is_verified'        => 'nullable|boolean',
            'is_official'        => 'nullable|boolean',
            'sort_order'         => 'nullable|integer|min:0',
            'compatible_modules' => 'nullable|array',
            'webhook_url'        => 'nullable|url|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'Le nom de l\'extension est requis.',
            'category.required'  => 'La catégorie est requise.',
            'category.in'        => 'Catégorie invalide.',
            'pricing_type.required' => 'Le type de tarification est requis.',
            'status.required'    => 'Le statut est requis.',
            'icon_bg_color.regex'=> 'Couleur hexadécimale invalide.',
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