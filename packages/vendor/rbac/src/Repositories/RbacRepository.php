<?php

namespace Vendor\Rbac\Repositories;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RbacRepository
{
    // ── Rôles ──────────────────────────────────────────────────────────────

    public function getRolesForTenant(int $tenantId): Collection
    {
        return Role::where(function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)
              ->orWhereNull('tenant_id'); // rôles système globaux
        })
        ->withCount('permissions', 'users')
        ->orderBy('tenant_id') // système d'abord
        ->orderBy('name')
        ->get();
    }

    public function getFilteredRoles(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $tenantId = Auth::user()->tenant_id;

        $query = Role::where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            })
            ->withCount(['permissions', 'users'])
            ->with('permissions');

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%'.$filters['search'].'%')
                  ->orWhere('label', 'like', '%'.$filters['search'].'%');
            });
        }

        return $query->orderBy('is_system', 'desc')->orderBy('name')->paginate($perPage);
    }

    public function findRole(int $id): ?Role
    {
        $tenantId = Auth::user()->tenant_id;
        return Role::where('id', $id)
            ->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            })
            ->with('permissions')
            ->first();
    }

    public function createRole(array $data): Role
    {
        return Role::create([
            'name'        => $data['name'],
            'label'       => $data['label'],
            'description' => $data['description'] ?? null,
            'color'       => $data['color'] ?? '#64748b',
            'guard_name'  => 'web',
            'tenant_id'   => Auth::user()->tenant_id,
            'is_system'   => false,
            'is_active'   => true,
        ]);
    }

    public function updateRole(Role $role, array $data): Role
    {
        $role->update(array_filter([
            'label'       => $data['label']       ?? null,
            'description' => $data['description'] ?? null,
            'color'       => $data['color']       ?? null,
            'is_active'   => $data['is_active']   ?? null,
        ], fn($v) => !is_null($v)));

        return $role->fresh(['permissions']);
    }

    public function deleteRole(Role $role): bool
    {
        if ($role->is_system || is_null($role->tenant_id)) {
            throw new \RuntimeException('Impossible de supprimer un rôle système.');
        }
        // Vérifier que personne n'a ce rôle
        if ($role->users()->count() > 0) {
            throw new \RuntimeException('Ce rôle est assigné à des utilisateurs. Veuillez d\'abord les réassigner.');
        }
        return (bool) $role->delete();
    }

    public function syncRolePermissions(Role $role, array $permissionNames): Role
    {
        $role->syncPermissions($permissionNames);
        return $role->fresh(['permissions']);
    }

    // ── Permissions ─────────────────────────────────────────────────────────

    public function getAllPermissions(): Collection
    {
        return Permission::whereNull('tenant_id')
            ->orWhere('tenant_id', Auth::user()->tenant_id)
            ->orderBy('group')
            ->orderBy('name')
            ->get();
    }

    public function getPermissionsGrouped(): array
    {
        $permissions = $this->getAllPermissions();
        $groups = config('rbac.permission_groups', []);
        $result = [];

        foreach ($groups as $groupKey => $groupDef) {
            $result[$groupKey] = [
                'label'       => $groupDef['label'],
                'icon'        => $groupDef['icon'],
                'permissions' => [],
            ];
            foreach (array_keys($groupDef['permissions']) as $permName) {
                $perm = $permissions->firstWhere('name', $permName);
                if ($perm) {
                    $result[$groupKey]['permissions'][] = $perm;
                }
            }
        }

        return $result;
    }

    public function ensurePermissionsExist(): void
    {
        $groups = config('rbac.permission_groups', []);
        foreach ($groups as $groupKey => $groupDef) {
            foreach ($groupDef['permissions'] as $name => $label) {
                Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    ['label' => $label, 'group' => $groupKey]
                );
            }
        }
    }

    // ── Stats ───────────────────────────────────────────────────────────────

    public function getStats(): array
    {
        $tenantId = Auth::user()->tenant_id;

        $roles = Role::where(function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
        })->withCount('users')->get();

        return [
            'total_roles'       => $roles->count(),
            'custom_roles'      => $roles->whereNotNull('tenant_id')->count(),
            'total_permissions' => Permission::whereNull('tenant_id')->count(),
            'users_without_role'=> \App\Models\User::where('tenant_id', $tenantId)
                ->whereDoesntHave('roles')
                ->count(),
            'roles_distribution'=> $roles->mapWithKeys(fn($r) => [
                ($r->label ?? $r->name) => $r->users_count
            ])->toArray(),
        ];
    }
}