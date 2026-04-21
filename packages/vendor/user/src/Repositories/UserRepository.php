<?php

namespace Vendor\User\Repositories;

use App\Models\User;
use Vendor\User\Models\UserInvitation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class UserRepository
{
    // ── Queries ────────────────────────────────────────────────────────────

    public function getFiltered(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $tenantId = Auth::user()->tenant_id;

        $query = User::where('tenant_id', $tenantId)
            ->with(['roles']);

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%")
                  ->orWhere('job_title', 'like', "%{$term}%")
                  ->orWhere('department', 'like', "%{$term}%");
            });
        }

        if (!empty($filters['role'])) {
            $query->whereHas('roles', fn($q) => $q->where('name', $filters['role']));
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['role_in_tenant'])) {
            $query->where('role_in_tenant', $filters['role_in_tenant']);
        }

        $sortBy  = $filters['sort_by']  ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $allowed = ['name','email','created_at','last_login_at','status','role_in_tenant'];
        if (in_array($sortBy, $allowed)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->latest();
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?User
    {
        return User::where('tenant_id', Auth::user()->tenant_id)->find($id);
    }

    public function count(): int
    {
        return User::where('tenant_id', Auth::user()->tenant_id)->count();
    }

    public function countByStatus(string $status): int
    {
        return User::where('tenant_id', Auth::user()->tenant_id)->where('status', $status)->count();
    }

    public function countByRole(): array
    {
        return User::where('tenant_id', Auth::user()->tenant_id)
            ->selectRaw('role_in_tenant, count(*) as count')
            ->groupBy('role_in_tenant')
            ->pluck('count', 'role_in_tenant')
            ->toArray();
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh(['roles']);
    }

    public function delete(User $user): bool
    {
        // Sécurité: ne jamais supprimer le tenant owner
        if ($user->is_tenant_owner) {
            throw new \RuntimeException('Impossible de supprimer le propriétaire du compte.');
        }
        return (bool) $user->delete();
    }

    public function bulkDelete(array $ids): int
    {
        $tenantId = Auth::user()->tenant_id;
        return User::where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->where('is_tenant_owner', false)
            ->where('id', '!=', Auth::id())
            ->delete();
    }

    public function bulkStatusUpdate(array $ids, string $status): int
    {
        $tenantId = Auth::user()->tenant_id;
        return User::where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->where('is_tenant_owner', false)
            ->where('id', '!=', Auth::id())
            ->update(['status' => $status]);
    }

    // ── Invitations ─────────────────────────────────────────────────────────

    public function getInvitations(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $tenantId = Auth::user()->tenant_id;

        $query = UserInvitation::where('tenant_id', $tenantId)
            ->with('invitedBy');

        if (!empty($filters['search'])) {
            $query->where('email', 'like', '%'.$filters['search'].'%');
        }

        if (!empty($filters['status'])) {
            match($filters['status']) {
                'pending'  => $query->pending(),
                'accepted' => $query->whereNotNull('accepted_at'),
                'expired'  => $query->whereNull('accepted_at')->where('expires_at', '<', now()),
                'revoked'  => $query->whereNotNull('revoked_at'),
                default    => null,
            };
        }

        return $query->latest()->paginate($perPage);
    }

    public function findInvitationByToken(string $token): ?UserInvitation
    {
        return UserInvitation::where('token', $token)->first();
    }

    public function createInvitation(array $data): UserInvitation
    {
        return UserInvitation::create($data);
    }

    public function revokeInvitation(UserInvitation $invitation, string $reason = ''): UserInvitation
    {
        $invitation->update([
            'revoked_at'     => now(),
            'revoked_reason' => $reason,
        ]);
        return $invitation->fresh();
    }

    public function pendingInvitationForEmail(string $email, int $tenantId): ?UserInvitation
    {
        return UserInvitation::where('tenant_id', $tenantId)
            ->where('email', $email)
            ->active()
            ->first();
    }
}