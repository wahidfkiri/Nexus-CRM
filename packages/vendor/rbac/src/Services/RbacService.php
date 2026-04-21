<?php

namespace Vendor\Rbac\Services;

use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Vendor\Rbac\Repositories\RbacRepository;

class RbacService
{
    public function __construct(protected RbacRepository $repository) {}

    // ── Rôles ──────────────────────────────────────────────────────────────

    public function getFilteredRoles(array $filters)
    {
        $perPage = min((int)($filters['per_page'] ?? 15), 100);
        return $this->repository->getFilteredRoles($filters, $perPage);
    }

    public function getRolesForCurrentTenant()
    {
        return $this->repository->getRolesForTenant(Auth::user()->tenant_id);
    }

    public function createRole(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            // Slug unique basé sur le label
            $data['name'] = $this->generateRoleSlug($data['label']);

            $role = $this->repository->createRole($data);

            // Assigner les permissions sélectionnées
            if (!empty($data['permissions'])) {
                $this->repository->syncRolePermissions($role, $data['permissions']);
            }

            $this->clearCache();

            Log::channel('daily')->info("[RBAC] Rôle créé : {$role->name}", [
                'tenant_id'   => Auth::user()->tenant_id,
                'permissions' => $data['permissions'] ?? [],
            ]);

            return $role->fresh(['permissions']);
        });
    }

    public function updateRole(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $this->assertNotSystem($role);

            $role = $this->repository->updateRole($role, $data);

            // Sync des permissions
            if (array_key_exists('permissions', $data)) {
                $this->repository->syncRolePermissions($role, $data['permissions'] ?? []);
            }

            $this->clearCache();

            Log::channel('daily')->info("[RBAC] Rôle modifié : {$role->name}");

            return $role->fresh(['permissions']);
        });
    }

    public function deleteRole(Role $role): bool
    {
        $this->assertNotSystem($role);

        return DB::transaction(function () use ($role) {
            $result = $this->repository->deleteRole($role);
            $this->clearCache();
            Log::channel('daily')->info("[RBAC] Rôle supprimé : {$role->name}");
            return $result;
        });
    }

    public function syncPermissions(Role $role, array $permissionNames): Role
    {
        $this->assertNotSystem($role);

        return DB::transaction(function () use ($role, $permissionNames) {
            $result = $this->repository->syncRolePermissions($role, $permissionNames);
            $this->clearCache();
            Log::channel('daily')->info("[RBAC] Permissions sync pour rôle {$role->name}", [
                'permissions' => $permissionNames,
            ]);
            return $result;
        });
    }

    // ── Permissions ─────────────────────────────────────────────────────────

    public function getPermissionsGrouped(): array
    {
        return $this->repository->getPermissionsGrouped();
    }

    public function getAllPermissions()
    {
        return $this->repository->getAllPermissions();
    }

    public function ensurePermissionsExist(): void
    {
        $this->repository->ensurePermissionsExist();
        $this->clearCache();
    }

    // ── Stats ───────────────────────────────────────────────────────────────

    public function getStats(): array
    {
        return $this->repository->getStats();
    }

    // ── Utilitaires ─────────────────────────────────────────────────────────

    private function generateRoleSlug(string $label): string
    {
        $tenantId = Auth::user()->tenant_id;
        $base  = Str::slug($label, '_');
        $slug  = $base;
        $count = 1;

        while (Role::where('name', $slug)->where('tenant_id', $tenantId)->exists()) {
            $slug = $base . '_' . $count++;
        }

        return $slug;
    }

    private function assertNotSystem(Role $role): void
    {
        if ($role->is_system || in_array($role->name, config('rbac.system_roles', []))) {
            throw new \RuntimeException('Les rôles système ne peuvent pas être modifiés.');
        }
    }

    private function clearCache(): void
    {
        // Vider le cache Spatie Permission pour ce tenant
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}