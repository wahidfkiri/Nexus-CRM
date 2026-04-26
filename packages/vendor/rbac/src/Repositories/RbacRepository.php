<?php

namespace Vendor\Rbac\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Vendor\Rbac\Services\TenantRoleService;

class RbacRepository
{
    public function __construct(protected TenantRoleService $tenantRoleService)
    {
    }

    public function getRolesForTenant(int $tenantId): Collection
    {
        $this->tenantRoleService->ensureTenantRoles($tenantId);

        return Role::query()
            ->where('tenant_id', $tenantId)
            ->withCount('permissions')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();
    }

    public function getFilteredRoles(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $tenantId = (int) Auth::user()->tenant_id;
        $this->tenantRoleService->ensureTenantRoles($tenantId);

        $query = Role::query()
            ->where('tenant_id', $tenantId)
            ->withCount('permissions')
            ->with('permissions');

        if (!empty($filters['search'])) {
            $query->where(function ($query) use ($filters): void {
                $query->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('label', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function findRole(int $id): ?Role
    {
        $tenantId = (int) Auth::user()->tenant_id;
        $this->tenantRoleService->ensureTenantRoles($tenantId);

        return Role::query()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->with('permissions')
            ->first();
    }

    public function createRole(array $data): Role
    {
        return Role::query()->create([
            'name' => $data['name'],
            'label' => $data['label'],
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? '#64748b',
            'guard_name' => 'web',
            'tenant_id' => (int) Auth::user()->tenant_id,
            'is_system' => false,
            'is_active' => true,
        ]);
    }

    public function updateRole(Role $role, array $data): Role
    {
        $role->update(array_filter([
            'label' => $data['label'] ?? null,
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? null,
            'is_active' => $data['is_active'] ?? null,
        ], fn ($value) => $value !== null));

        return $role->fresh(['permissions']);
    }

    public function deleteRole(Role $role): bool
    {
        if ((bool) $role->is_system || (int) $role->tenant_id <= 0) {
            throw new \RuntimeException('Impossible de supprimer un rôle système.');
        }

        if (
            \App\Models\TenantUserMembership::query()
                ->where('role_id', (int) $role->id)
                ->where('status', 'active')
                ->exists()
        ) {
            throw new \RuntimeException('Ce rôle est assigné à des utilisateurs. Réassignez-les avant suppression.');
        }

        return (bool) $role->delete();
    }

    public function syncRolePermissions(Role $role, array $permissionNames): Role
    {
        $validNames = Permission::query()
            ->whereNull('tenant_id')
            ->whereIn('name', $permissionNames)
            ->pluck('name')
            ->all();

        $role->syncPermissions($validNames);

        return $role->fresh(['permissions']);
    }

    public function getAllPermissions(): Collection
    {
        return Permission::query()
            ->whereNull('tenant_id')
            ->orderBy('group')
            ->orderBy('name')
            ->get();
    }

    public function getPermissionsGrouped(): array
    {
        $permissions = $this->getAllPermissions();
        $groups = config('rbac.permission_groups', []);
        $result = [];

        foreach ($groups as $groupKey => $groupDefinition) {
            $result[$groupKey] = [
                'label' => $groupDefinition['label'],
                'icon' => $groupDefinition['icon'],
                'permissions' => [],
            ];

            foreach (array_keys($groupDefinition['permissions']) as $permissionName) {
                $permission = $permissions->firstWhere('name', $permissionName);
                if ($permission) {
                    $result[$groupKey]['permissions'][] = $permission;
                }
            }
        }

        return $result;
    }

    public function ensurePermissionsExist(): void
    {
        $this->tenantRoleService->ensureTenantRoles((int) Auth::user()->tenant_id);
    }

    public function getStats(): array
    {
        $tenantId = (int) Auth::user()->tenant_id;
        $roles = $this->getRolesForTenant($tenantId);

        return [
            'total_roles' => $roles->count(),
            'custom_roles' => $roles->where('is_system', false)->count(),
            'total_permissions' => Permission::query()->whereNull('tenant_id')->count(),
            'users_without_role' => User::query()
                ->whereHas('tenantMemberships', fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'active')
                    ->whereNull('role_id'))
                ->count(),
            'roles_distribution' => $roles->mapWithKeys(function ($role) use ($tenantId) {
                $count = \App\Models\TenantUserMembership::query()
                    ->where('tenant_id', $tenantId)
                    ->where('role_id', (int) $role->id)
                    ->where('status', 'active')
                    ->count();

                return [($role->label ?? $role->name) => $count];
            })->toArray(),
        ];
    }
}
