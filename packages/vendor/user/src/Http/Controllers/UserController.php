<?php

namespace Vendor\User\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Vendor\User\Http\Requests\UserRequest;
use Vendor\User\Http\Requests\InviteRequest;
use Vendor\User\Models\UserInvitation;
use Vendor\User\Services\UserService;
use Vendor\User\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class UserController extends Controller
{
    public function __construct(protected UserService $userService) {}

    /* ── INDEX ────────────────────────────────────────────────────────────── */

    public function index()
    {
        return view('user::index', [
            'roles'    => config('user.tenant_roles', []),
            'statuses' => config('user.user_statuses', []),
        ]);
    }

    /* ── CREATE ───────────────────────────────────────────────────────────── */

    public function create()
    {
        return view('user::invite', [
            'roles' => array_diff_key(
                config('user.tenant_roles', []),
                ['owner' => '']          // On ne peut pas inviter un owner
            ),
        ]);
    }

    /* ── STORE (invite) ───────────────────────────────────────────────────── */

    public function store(InviteRequest $request): JsonResponse
    {
        try {
            $invitation = $this->userService->invite($request->validated());

            return response()->json([
                'success' => true,
                'message' => "Invitation envoyée à {$invitation->email}.",
                'data'    => $invitation,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /* ── SHOW ─────────────────────────────────────────────────────────────── */

    public function show(User $user)
    {
        $this->authorizeTenantUser($user);
        $user->load('roles');
        return view('user::show', [
            'user'  => $user,
            'roles' => config('user.tenant_roles', []),
        ]);
    }

    /* ── EDIT ─────────────────────────────────────────────────────────────── */

    public function edit(User $user)
    {
        $this->authorizeTenantUser($user);
        $user->load('roles');

        return view('user::edit', [
            'user'     => $user,
            'roles'    => config('user.tenant_roles', []),
            'statuses' => config('user.user_statuses', []),
        ]);
    }

    /* ── UPDATE ───────────────────────────────────────────────────────────── */

    public function update(UserRequest $request, User $user): JsonResponse
    {
        $this->authorizeTenantUser($user);

        try {
            $user = $this->userService->updateUser($user, $request->validated());

            return response()->json([
                'success'  => true,
                'message'  => 'Utilisateur mis à jour avec succès.',
                'data'     => $user,
                'redirect' => route('users.show', $user),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /* ── DESTROY ──────────────────────────────────────────────────────────── */

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

    /* ── DATA (AJAX) ──────────────────────────────────────────────────────── */

    public function getData(Request $request): JsonResponse
    {
        $users = $this->userService->getFilteredUsers($request->all());

        return response()->json([
            'data'         => $users->items(),
            'current_page' => $users->currentPage(),
            'last_page'    => $users->lastPage(),
            'per_page'     => $users->perPage(),
            'total'        => $users->total(),
            'from'         => $users->firstItem(),
            'to'           => $users->lastItem(),
        ]);
    }

    /* ── STATS (AJAX) ─────────────────────────────────────────────────────── */

    public function getStats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->userService->getStats(),
        ]);
    }

    /* ── BULK ─────────────────────────────────────────────────────────────── */

    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
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
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        try {
            $count = $this->userService->bulkStatusUpdate($request->ids, $request->status);
            return response()->json(['success' => true, 'message' => "{$count} utilisateur(s) mis à jour."]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /* ── SUSPEND / ACTIVATE ───────────────────────────────────────────────── */

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

    /* ── AVATAR ───────────────────────────────────────────────────────────── */

    public function uploadAvatar(Request $request, User $user): JsonResponse
    {
        $this->authorizeTenantUser($user);
        $request->validate(['avatar' => 'required|image|max:'.config('user.avatar.max_size_kb', 2048)]);

        try {
            $user = $this->userService->updateAvatar($user, $request->file('avatar'));
            return response()->json([
                'success' => true,
                'message' => 'Avatar mis à jour.',
                'avatar_url' => asset('storage/'.$user->avatar),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /* ── INVITATIONS ──────────────────────────────────────────────────────── */

    public function invitations()
    {
        return view('user::invitations', [
            'roles' => config('user.tenant_roles', []),
        ]);
    }

    public function invitationsData(Request $request): JsonResponse
    {
        $invitations = $this->userService->getInvitations($request->all());
        return response()->json([
            'data'         => $invitations->items(),
            'current_page' => $invitations->currentPage(),
            'last_page'    => $invitations->lastPage(),
            'per_page'     => $invitations->perPage(),
            'total'        => $invitations->total(),
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

    /* ── EXPORTS ──────────────────────────────────────────────────────────── */

    public function exportCsv()
    {
        return Excel::download(new UsersExport, 'utilisateurs_'.date('Y-m-d').'.csv');
    }

    public function exportExcel()
    {
        return Excel::download(new UsersExport, 'utilisateurs_'.date('Y-m-d').'.xlsx');
    }

    /* ── ACCEPT INVITATION (public) ───────────────────────────────────────── */

    public function acceptForm(string $token)
    {
        $invitation = app(\Vendor\User\Repositories\UserRepository::class)->findInvitationByToken($token);

        if (!$invitation || !$invitation->is_active) {
            abort(404, 'Invitation invalide ou expirée.');
        }

        return view('user::accept', compact('invitation'));
    }

    public function acceptSubmit(Request $request, string $token): JsonResponse
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'password'              => 'required|min:8|confirmed',
        ]);

        $invitation = app(\Vendor\User\Repositories\UserRepository::class)->findInvitationByToken($token);

        if (!$invitation || !$invitation->is_active) {
            return response()->json(['success' => false, 'message' => 'Invitation invalide ou expirée.'], 422);
        }

        try {
            $user = $this->userService->acceptInvitation($invitation, $request->only('name', 'password'));
            return response()->json([
                'success'  => true,
                'message'  => 'Bienvenue ! Votre compte a été créé.',
                'redirect' => route('login'),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ── Helpers ──────────────────────────────────────────────────────────── */

    private function authorizeTenantUser(User $user): void
    {
        if ($user->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'Accès non autorisé.');
        }
    }

    private function authorizeTenantInvitation(UserInvitation $invitation): void
    {
        if ($invitation->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'Accès non autorisé.');
        }
    }
}