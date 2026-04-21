<?php

namespace Vendor\Rbac\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Vendor\Rbac\Http\Requests\RoleRequest;
use Vendor\Rbac\Services\RbacService;
use Throwable;

class RbacController extends Controller
{
    public function __construct(protected RbacService $rbacService) {}

    /* ── INDEX RÔLES ──────────────────────────────────────────────────────── */

    public function rolesIndex()
    {
        return view('rbac::roles.index');
    }

    /* ── CREATE RÔLE ──────────────────────────────────────────────────────── */

    public function rolesCreate()
    {
        return view('rbac::roles.create', [
            'permissionsGrouped' => $this->rbacService->getPermissionsGrouped(),
        ]);
    }

    /* ── STORE RÔLE ───────────────────────────────────────────────────────── */

    public function rolesStore(RoleRequest $request): JsonResponse
    {
        try {
            $role = $this->rbacService->createRole($request->validated());

            return response()->json([
                'success'  => true,
                'message'  => "Rôle « {$role->label} » créé avec succès.",
                'data'     => $this->formatRole($role),
                'redirect' => route('rbac.roles.show', $role),
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ── SHOW RÔLE ────────────────────────────────────────────────────────── */

    public function rolesShow(Role $role)
    {
        $this->authorizeTenantRole($role);
        $role->load('permissions');
        $users = User::where('tenant_id', auth()->user()->tenant_id)
            ->role($role->name)
            ->get(['id','name','email','avatar','status','role_in_tenant']);

        return view('rbac::roles.show', [
            'role'               => $role,
            'users'              => $users,
            'permissionsGrouped' => $this->rbacService->getPermissionsGrouped(),
        ]);
    }

    /* ── EDIT RÔLE ────────────────────────────────────────────────────────── */

    public function rolesEdit(Role $role)
    {
        $this->authorizeTenantRole($role);
        $role->load('permissions');

        return view('rbac::roles.edit', [
            'role'               => $role,
            'permissionsGrouped' => $this->rbacService->getPermissionsGrouped(),
        ]);
    }

    /* ── UPDATE RÔLE ──────────────────────────────────────────────────────── */

    public function rolesUpdate(RoleRequest $request, Role $role): JsonResponse
    {
        $this->authorizeTenantRole($role);

        try {
            $role = $this->rbacService->updateRole($role, $request->validated());

            return response()->json([
                'success'  => true,
                'message'  => "Rôle mis à jour.",
                'data'     => $this->formatRole($role),
                'redirect' => route('rbac.roles.show', $role),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ── DESTROY RÔLE ─────────────────────────────────────────────────────── */

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

    /* ── SYNC PERMISSIONS (AJAX depuis la page show) ──────────────────────── */

    public function rolesSync(Request $request, Role $role): JsonResponse
    {
        $this->authorizeTenantRole($role);

        $request->validate([
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        try {
            $role = $this->rbacService->syncPermissions($role, $request->permissions ?? []);
            return response()->json([
                'success' => true,
                'message' => 'Permissions synchronisées.',
                'count'   => $role->permissions->count(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ── DATA AJAX (liste des rôles) ──────────────────────────────────────── */

    public function rolesData(Request $request): JsonResponse
    {
        $roles = $this->rbacService->getFilteredRoles($request->all());

        return response()->json([
            'data'         => $roles->map(fn($r) => $this->formatRole($r))->values(),
            'current_page' => $roles->currentPage(),
            'last_page'    => $roles->lastPage(),
            'per_page'     => $roles->perPage(),
            'total'        => $roles->total(),
        ]);
    }

    /* ── STATS ────────────────────────────────────────────────────────────── */

    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->rbacService->getStats(),
        ]);
    }

    /* ── PERMISSIONS LIST (pour select dans formulaire user) ──────────────── */

    public function permissionsIndex()
    {
        return view('rbac::permissions.index', [
            'permissionsGrouped' => $this->rbacService->getPermissionsGrouped(),
        ]);
    }

    /* ── ASSIGN ROLE TO USER ──────────────────────────────────────────────── */

    public function assignRole(Request $request, User $user): JsonResponse
    {
        abort_if($user->tenant_id !== auth()->user()->tenant_id, 403);

        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        try {
            $role = Role::where('name', $request->role)
                ->where(function ($q) { $q->where('tenant_id', auth()->user()->tenant_id)->orWhereNull('tenant_id'); })
                ->firstOrFail();

            // Mettre à jour le rôle Spatie
            $user->syncRoles([$role->name]);

            // Mettre à jour role_in_tenant sur le user
            $user->update(['role_in_tenant' => $role->name]);

            return response()->json([
                'success' => true,
                'message' => "Rôle « " . ($role->label ?? $role->name) . " » assigné à {$user->name}.",
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ── Helpers ──────────────────────────────────────────────────────────── */

    private function formatRole(Role $role): array
    {
        return [
            'id'               => $role->id,
            'name'             => $role->name,
            'label'            => $role->label ?? $role->name,
            'description'      => $role->description,
            'color'            => $role->color ?? '#64748b',
            'is_system'        => (bool) $role->is_system,
            'is_active'        => (bool) ($role->is_active ?? true),
            'tenant_id'        => $role->tenant_id,
            'permissions_count'=> $role->permissions_count ?? $role->permissions->count(),
            'users_count'      => $role->users_count ?? 0,
            'permissions'      => $role->relationLoaded('permissions')
                ? $role->permissions->pluck('name')
                : [],
        ];
    }

    private function authorizeTenantRole(Role $role): void
    {
        $tenantId = auth()->user()->tenant_id;
        if (!is_null($role->tenant_id) && $role->tenant_id !== $tenantId) {
            abort(403, 'Accès non autorisé à ce rôle.');
        }
    }
}