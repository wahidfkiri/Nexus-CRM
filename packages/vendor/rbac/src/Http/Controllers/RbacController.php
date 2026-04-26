<?php

namespace Vendor\Rbac\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Throwable;
use Vendor\Rbac\Http\Requests\RoleRequest;
use Vendor\Rbac\Services\RbacService;
use Vendor\Rbac\Services\TenantRoleService;

class RbacController extends Controller
{
    public function __construct(
        protected RbacService $rbacService,
        protected TenantRoleService $tenantRoleService,
    ) {
    }

    public function rolesIndex()
    {
        return view('rbac::roles.index');
    }

    public function rolesCreate()
    {
        return view('rbac::roles.create', [
            'permissionsGrouped' => $this->rbacService->getPermissionsGrouped(),
        ]);
    }

    public function rolesStore(RoleRequest $request): JsonResponse
    {
        try {
            $role = $this->rbacService->createRole($request->validated());

            return response()->json([
                'success' => true,
                'message' => "Rôle « {$role->label} » créé avec succès.",
                'data' => $this->formatRole($role),
                'redirect' => route('rbac.roles.show', $role),
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function rolesShow(Role $role)
    {
        $this->authorizeTenantRole($role);
        $role->load('permissions');

        $users = User::query()
            ->whereHas('tenantMemberships', fn ($query) => $query
                ->where('tenant_id', auth()->user()->tenant_id)
                ->where('role_id', (int) $role->id)
                ->where('status', 'active'))
            ->get(['id', 'name', 'email', 'avatar', 'status', 'role_in_tenant']);

        return view('rbac::roles.show', [
            'role' => $role,
            'users' => $users,
            'permissionsGrouped' => $this->rbacService->getPermissionsGrouped(),
        ]);
    }

    public function rolesEdit(Role $role)
    {
        $this->authorizeTenantRole($role);
        $role->load('permissions');

        return view('rbac::roles.edit', [
            'role' => $role,
            'permissionsGrouped' => $this->rbacService->getPermissionsGrouped(),
        ]);
    }

    public function rolesUpdate(RoleRequest $request, Role $role): JsonResponse
    {
        $this->authorizeTenantRole($role);

        try {
            $role = $this->rbacService->updateRole($role, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Rôle mis à jour.',
                'data' => $this->formatRole($role),
                'redirect' => route('rbac.roles.show', $role),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function rolesDestroy(Role $role): JsonResponse
    {
        $this->authorizeTenantRole($role);

        try {
            $this->rbacService->deleteRole($role);

            return response()->json(['success' => true, 'message' => 'Rôle supprimé.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function rolesSync(Request $request, Role $role): JsonResponse
    {
        $this->authorizeTenantRole($role);

        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        try {
            $role = $this->rbacService->syncPermissions($role, $request->permissions ?? []);

            return response()->json([
                'success' => true,
                'message' => 'Permissions synchronisées.',
                'count' => $role->permissions->count(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function rolesData(Request $request): JsonResponse
    {
        $roles = $this->rbacService->getFilteredRoles($request->all());

        return response()->json([
            'data' => $roles->map(fn ($role) => $this->formatRole($role))->values(),
            'current_page' => $roles->currentPage(),
            'last_page' => $roles->lastPage(),
            'per_page' => $roles->perPage(),
            'total' => $roles->total(),
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->rbacService->getStats(),
        ]);
    }

    public function permissionsIndex()
    {
        return view('rbac::permissions.index', [
            'permissionsGrouped' => $this->rbacService->getPermissionsGrouped(),
        ]);
    }

    public function assignRole(Request $request, User $user): JsonResponse
    {
        $tenantId = (int) auth()->user()->tenant_id;
        abort_if(!$user->hasTenantAccess($tenantId), 403);

        $request->validate([
            'role' => 'required',
        ]);

        try {
            $role = $this->tenantRoleService->resolveTenantRole($tenantId, $request->input('role'));
            if ($role->name === 'owner' && !auth()->user()->hasTenantRole('owner', $tenantId)) {
                throw new \RuntimeException('Seul le propriétaire du tenant peut attribuer le rôle propriétaire.');
            }

            $this->tenantRoleService->syncUserRole($user, $tenantId, $role, [
                'status' => 'active',
                'is_tenant_owner' => $role->name === 'owner',
            ]);

            return response()->json([
                'success' => true,
                'message' => "Rôle « " . ($role->label ?? $role->name) . " » assigné à {$user->name}.",
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function formatRole(Role $role): array
    {
        $usersCount = \App\Models\TenantUserMembership::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('role_id', (int) $role->id)
            ->where('status', 'active')
            ->count();

        return [
            'id' => $role->id,
            'name' => $role->name,
            'label' => $role->label ?? $role->name,
            'description' => $role->description,
            'color' => $role->color ?? '#64748b',
            'is_system' => (bool) $role->is_system,
            'is_active' => (bool) ($role->is_active ?? true),
            'tenant_id' => $role->tenant_id,
            'permissions_count' => $role->permissions_count ?? $role->permissions->count(),
            'users_count' => $usersCount,
            'permissions' => $role->relationLoaded('permissions')
                ? $role->permissions->pluck('name')
                : [],
        ];
    }

    private function authorizeTenantRole(Role $role): void
    {
        if ((int) $role->tenant_id !== (int) auth()->user()->tenant_id) {
            abort(403, 'Accès non autorisé à ce rôle.');
        }
    }
}
