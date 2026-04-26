<?php

namespace Vendor\User\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;
use Vendor\Rbac\Services\TenantRoleService;
use Vendor\User\Exports\UsersExport;
use Vendor\User\Http\Requests\InviteRequest;
use Vendor\User\Http\Requests\UserRequest;
use Vendor\User\Models\UserInvitation;
use Vendor\User\Repositories\UserRepository;
use Vendor\User\Services\UserService;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected TenantRoleService $tenantRoleService,
    ) {
    }

    public function index()
    {
        return view('user::index', [
            'roles' => $this->tenantRoleOptions(),
            'statuses' => config('user.user_statuses', []),
        ]);
    }

    public function create()
    {
        return view('user::invite', [
            'roles' => array_diff_key($this->tenantRoleOptions(), ['owner' => '']),
        ]);
    }

    public function store(InviteRequest $request): JsonResponse
    {
        try {
            $invitation = $this->userService->invite($request->validated());

            return response()->json([
                'success' => true,
                'message' => "Invitation envoyée à {$invitation->email}.",
                'data' => $invitation,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(User $user)
    {
        $this->authorizeTenantUser($user);
        $user->load('roles');

        return view('user::show', [
            'user' => $user,
            'roles' => $this->tenantRoleOptions(),
        ]);
    }

    public function edit(User $user)
    {
        $this->authorizeTenantUser($user);
        $user->load('roles');

        return view('user::edit', [
            'user' => $user,
            'roles' => $this->tenantRoleOptions(),
            'statuses' => config('user.user_statuses', []),
        ]);
    }

    public function update(UserRequest $request, User $user): JsonResponse
    {
        $this->authorizeTenantUser($user);

        try {
            $user = $this->userService->updateUser($user, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur mis à jour avec succès.',
                'data' => $user,
                'redirect' => route('users.show', $user),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorizeTenantUser($user);

        if ($user->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Vous ne pouvez pas vous supprimer vous-même.'], 422);
        }

        try {
            $this->userService->deleteUser($user);

            return response()->json(['success' => true, 'message' => 'Utilisateur supprimé.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function getData(Request $request): JsonResponse
    {
        $users = $this->userService->getFilteredUsers($request->all());

        return response()->json([
            'data' => $users->items(),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
            'from' => $users->firstItem(),
            'to' => $users->lastItem(),
        ]);
    }

    public function getStats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->userService->getStats(),
        ]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        try {
            $count = $this->userService->bulkDelete($request->ids);

            return response()->json(['success' => true, 'message' => "{$count} utilisateur(s) supprimé(s)."]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function bulkStatus(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        try {
            $count = $this->userService->bulkStatusUpdate($request->ids, $request->status);

            return response()->json(['success' => true, 'message' => "{$count} utilisateur(s) mis à jour."]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function suspend(User $user): JsonResponse
    {
        $this->authorizeTenantUser($user);

        try {
            $this->userService->suspendUser($user);

            return response()->json(['success' => true, 'message' => 'Utilisateur suspendu.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function activate(User $user): JsonResponse
    {
        $this->authorizeTenantUser($user);

        try {
            $this->userService->activateUser($user);

            return response()->json(['success' => true, 'message' => 'Utilisateur activé.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function uploadAvatar(Request $request, User $user): JsonResponse
    {
        $this->authorizeTenantUser($user);
        $request->validate(['avatar' => 'required|image|max:' . config('user.avatar.max_size_kb', 2048)]);

        try {
            $user = $this->userService->updateAvatar($user, $request->file('avatar'));

            return response()->json([
                'success' => true,
                'message' => 'Avatar mis à jour.',
                'avatar_url' => asset('storage/' . $user->avatar),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function invitations()
    {
        return view('user::invitations', [
            'roles' => $this->tenantRoleOptions(),
        ]);
    }

    public function invitationsData(Request $request): JsonResponse
    {
        $invitations = $this->userService->getInvitations($request->all());

        return response()->json([
            'data' => $invitations->items(),
            'current_page' => $invitations->currentPage(),
            'last_page' => $invitations->lastPage(),
            'per_page' => $invitations->perPage(),
            'total' => $invitations->total(),
            'from' => $invitations->firstItem(),
            'to' => $invitations->lastItem(),
        ]);
    }

    public function resendInvitation(UserInvitation $invitation): JsonResponse
    {
        $this->authorizeTenantInvitation($invitation);

        try {
            $this->userService->resendInvitation($invitation);

            return response()->json(['success' => true, 'message' => 'Invitation renvoyée.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function revokeInvitation(UserInvitation $invitation): JsonResponse
    {
        $this->authorizeTenantInvitation($invitation);

        try {
            $this->userService->revokeInvitation($invitation);

            return response()->json(['success' => true, 'message' => 'Invitation révoquée.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function exportCsv()
    {
        return Excel::download(new UsersExport, 'utilisateurs_' . date('Y-m-d') . '.csv');
    }

    public function exportExcel()
    {
        return Excel::download(new UsersExport, 'utilisateurs_' . date('Y-m-d') . '.xlsx');
    }

    public function acceptForm(string $token)
    {
        $invitation = app(UserRepository::class)->findInvitationByToken($token);

        if (!$invitation) {
            return response()->view('user::accept-invalid', [
                'reason' => 'Cette invitation est introuvable. Vérifiez le lien reçu par email.',
            ], 410);
        }

        if (!$invitation->isUsable()) {
            return response()->view('user::accept-invalid', [
                'reason' => 'Cette invitation est expirée, déjà acceptée, ou révoquée.',
            ], 410);
        }

        $existingUser = User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower((string) $invitation->email)])->first();

        if (auth()->check()) {
            $currentUser = auth()->user();
            if (mb_strtolower((string) $currentUser->email) !== mb_strtolower((string) $invitation->email)) {
                return response()->view('user::accept-invalid', [
                    'reason' => 'Cette invitation est liée à un autre compte. Connectez-vous avec la bonne adresse email.',
                ], 403);
            }
        } elseif ($existingUser) {
            session([
                'pending_invitation_token' => $invitation->token,
                'pending_invitation_email' => mb_strtolower((string) $invitation->email),
            ]);

            return redirect()
                ->route('login')
                ->with('info', 'Connectez-vous avec votre compte existant pour rejoindre cette équipe.');
        } else {
            session([
                'pending_invitation_token' => $invitation->token,
                'pending_invitation_email' => mb_strtolower((string) $invitation->email),
            ]);

            return redirect()
                ->route('register')
                ->with('info', 'Créez votre compte pour finaliser cette invitation.')
                ->with('invitation_email', (string) $invitation->email);
        }

        return view('user::accept', [
            'invitation' => $invitation,
            'requiresPassword' => false,
            'existingUser' => $existingUser,
        ]);
    }

    public function acceptSubmit(Request $request, string $token): JsonResponse
    {
        $invitation = app(UserRepository::class)->findInvitationByToken($token);

        if (!$invitation || !$invitation->isUsable()) {
            return response()->json(['success' => false, 'message' => 'Invitation invalide ou expirée.'], 422);
        }

        if (!auth()->check()) {
            session([
                'pending_invitation_token' => $invitation->token,
                'pending_invitation_email' => mb_strtolower((string) $invitation->email),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connectez-vous ou créez votre compte pour accepter cette invitation.',
                'redirect' => User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower((string) $invitation->email)])->exists()
                    ? route('login')
                    : route('register'),
            ], 409);
        }

        if (mb_strtolower((string) auth()->user()->email) !== mb_strtolower((string) $invitation->email)) {
            return response()->json([
                'success' => false,
                'message' => 'Cette invitation est liée à une autre adresse email.',
            ], 403);
        }

        try {
            $this->userService->acceptInvitation($invitation, [
                'user' => auth()->user(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invitation acceptée. Votre accès à cette équipe est maintenant actif.',
                'redirect' => url('/dashboard'),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function authorizeTenantUser(User $user): void
    {
        $tenantId = (int) auth()->user()->tenant_id;
        $membership = $user->tenantMemberships()
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->first();

        if ($membership) {
            $user->setAttribute('role_in_tenant', (string) $membership->role_in_tenant);
            $user->setAttribute('is_tenant_owner', (bool) $membership->is_tenant_owner);
            $user->setAttribute('status', (string) $membership->status);
        }

        if (!$membership && (int) $user->getOriginal('tenant_id') !== $tenantId) {
            abort(403, 'Accès non autorisé.');
        }
    }

    private function authorizeTenantInvitation(UserInvitation $invitation): void
    {
        if ((int) $invitation->tenant_id !== (int) auth()->user()->tenant_id) {
            abort(403, 'Accès non autorisé.');
        }
    }

    private function tenantRoleOptions(): array
    {
        $tenantId = (int) auth()->user()->tenant_id;
        $roles = $this->tenantRoleService->ensureTenantRoles($tenantId);

        return $roles
            ->sortByDesc('is_system')
            ->mapWithKeys(fn ($role) => [$role->name => ($role->label ?? ucfirst($role->name))])
            ->toArray();
    }
}
