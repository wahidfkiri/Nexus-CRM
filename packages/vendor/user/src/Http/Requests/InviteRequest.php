<?php

namespace Vendor\User\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Vendor\Rbac\Services\TenantRoleService;

class InviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $email = mb_strtolower(trim((string) $this->input('email')));
        $roleName = $this->input('role_in_tenant');
        $roleId = $this->input('role_id');

        $tenantId = (int) optional($this->user())->tenant_id;
        if ($tenantId > 0) {
            app(TenantRoleService::class)->ensureTenantRoles($tenantId);
        }

        if (!$roleId && is_string($roleName) && $roleName !== '') {
            $resolvedRoleId = Role::query()
                ->where('guard_name', 'web')
                ->where('tenant_id', $tenantId)
                ->where('name', $roleName)
                ->value('id');

            if ($resolvedRoleId) {
                $roleId = (int) $resolvedRoleId;
            }
        }

        $this->merge([
            'email' => $email,
            'role_id' => $roleId,
        ]);
    }

    public function rules(): array
    {
        $allowedRoles = array_keys(array_diff_key(config('user.tenant_roles', []), ['owner' => '']));

        return [
            'email' => ['required', 'email:rfc,dns', 'max:255'],
            'role_id' => [
                'nullable',
                'integer',
                Rule::exists('roles', 'id')->where(fn ($query) => $query
                    ->where('guard_name', 'web')
                    ->where('tenant_id', (int) optional($this->user())->tenant_id)
                    ->where('is_active', true)),
            ],
            'role_in_tenant' => ['required_without:role_id', 'string', Rule::in($allowedRoles)],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $roleId = $this->input('role_id');
            $roleName = (string) $this->input('role_in_tenant', '');

            if ($roleId) {
                $role = Role::query()
                    ->where('id', (int) $roleId)
                    ->where('guard_name', 'web')
                    ->where('tenant_id', (int) optional($this->user())->tenant_id)
                    ->where('is_active', true)
                    ->first();

                if (!$role) {
                    $validator->errors()->add('role_id', 'Le rôle sélectionné est introuvable.');
                    return;
                }

                if (!array_key_exists($role->name, config('user.tenant_roles', [])) || $role->name === 'owner') {
                    $validator->errors()->add('role_id', 'Ce rôle ne peut pas être attribué par invitation.');
                    return;
                }

                $this->merge([
                    'role_in_tenant' => $role->name,
                    'role_id' => (int) $role->id,
                ]);
                return;
            }

            if ($roleName !== '') {
                $role = Role::query()
                    ->where('name', $roleName)
                    ->where('guard_name', 'web')
                    ->where('tenant_id', (int) optional($this->user())->tenant_id)
                    ->where('is_active', true)
                    ->first();

                if (!$role) {
                    $validator->errors()->add('role_in_tenant', 'Le rôle sélectionné n’existe pas.');
                    return;
                }

                if ($role->name === 'owner') {
                    $validator->errors()->add('role_in_tenant', 'Le rôle propriétaire ne peut pas être attribué.');
                    return;
                }

                $this->merge(['role_id' => (int) $role->id]);
            }
        });
    }

    public function messages(): array
    {
        return [
            'email.required' => 'L’adresse email est requise.',
            'email.email' => 'Veuillez saisir un email valide.',
            'role_in_tenant.required_without' => 'Le rôle est requis.',
            'role_in_tenant.in' => 'Le rôle sélectionné est invalide.',
            'role_id.exists' => 'Le rôle sélectionné est introuvable.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Erreurs de validation.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
