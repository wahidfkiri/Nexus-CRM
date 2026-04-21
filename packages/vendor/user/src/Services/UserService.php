<?php

namespace Vendor\User\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Vendor\User\Models\UserInvitation;
use Vendor\User\Repositories\UserRepository;
use Vendor\User\Notifications\UserInvitationNotification;
use Vendor\User\Events\UserInvited;
use Vendor\User\Events\UserActivated;
use Vendor\User\Events\UserSuspended;
use Vendor\User\Events\UserRoleChanged;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(protected UserRepository $repository) {}

    // ── Lecture ────────────────────────────────────────────────────────────

    public function getFilteredUsers(array $filters): LengthAwarePaginator
    {
        $perPage = min((int)($filters['per_page'] ?? config('user.pagination.per_page', 15)), 100);
        return $this->repository->getFiltered($filters, $perPage);
    }

    public function getStats(): array
    {
        return [
            'total'    => $this->repository->count(),
            'active'   => $this->repository->countByStatus('active'),
            'invited'  => $this->repository->countByStatus('invited'),
            'inactive' => $this->repository->countByStatus('inactive'),
            'suspended'=> $this->repository->countByStatus('suspended'),
            'by_role'  => $this->repository->countByRole(),
        ];
    }

    // ── Édition utilisateur ────────────────────────────────────────────────

    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $oldRole = $user->role_in_tenant;
            $newRole = $data['role_in_tenant'] ?? $oldRole;

            // Mise à jour des champs scalaires
            $user = $this->repository->update($user, array_filter([
                'name'         => $data['name'] ?? null,
                'email'        => $data['email'] ?? null,
                'phone'        => $data['phone'] ?? null,
                'job_title'    => $data['job_title'] ?? null,
                'department'   => $data['department'] ?? null,
                'status'       => $data['status'] ?? null,
                'role_in_tenant' => $newRole,
            ], fn($v) => !is_null($v)));

            // Synchronisation rôle Spatie
            if ($newRole !== $oldRole) {
                $user->syncRoles([$newRole]);
                event(new UserRoleChanged($user, $oldRole, $newRole));
                Log::channel('daily')->info("[User] Rôle modifié #{$user->id} : {$oldRole} → {$newRole}");
            }

            return $user->fresh(['roles']);
        });
    }

    public function deleteUser(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            $result = $this->repository->delete($user);
            Log::channel('daily')->info("[User] Supprimé #{$user->id} ({$user->email})");
            return $result;
        });
    }

    public function bulkDelete(array $ids): int
    {
        return DB::transaction(fn() => $this->repository->bulkDelete($ids));
    }

    public function bulkStatusUpdate(array $ids, string $status): int
    {
        return DB::transaction(fn() => $this->repository->bulkStatusUpdate($ids, $status));
    }

    public function suspendUser(User $user): User
    {
        return DB::transaction(function () use ($user) {
            $user = $this->repository->update($user, ['status' => 'suspended']);
            event(new UserSuspended($user));
            return $user;
        });
    }

    public function activateUser(User $user): User
    {
        return DB::transaction(function () use ($user) {
            $user = $this->repository->update($user, ['status' => 'active']);
            event(new UserActivated($user));
            return $user;
        });
    }

    public function updateAvatar(User $user, \Illuminate\Http\UploadedFile $file): User
    {
        $disk = config('user.avatar.upload_disk', 'public');
        $path = config('user.avatar.upload_path', 'avatars');

        // Supprimer l'ancien avatar
        if ($user->avatar) {
            \Illuminate\Support\Facades\Storage::disk($disk)->delete($user->avatar);
        }

        $filename = $file->store($path, $disk);
        return $this->repository->update($user, ['avatar' => $filename]);
    }

    // ── Invitations ─────────────────────────────────────────────────────────

    public function invite(array $data): UserInvitation
    {
        return DB::transaction(function () use ($data) {
            $tenantId  = Auth::user()->tenant_id;
            $email     = strtolower(trim($data['email']));
            $role      = $data['role_in_tenant'] ?? 'user';

            // Vérifier si l'utilisateur existe déjà dans ce tenant
            $existing = User::where('email', $email)
                ->where('tenant_id', $tenantId)
                ->first();

            if ($existing) {
                throw new \RuntimeException("Cet email est déjà associé à un membre de votre équipe.");
            }

            // Révoquer toute invitation pending pour le même email
            $pending = $this->repository->pendingInvitationForEmail($email, $tenantId);
            if ($pending) {
                $this->repository->revokeInvitation($pending, 'Remplacée par une nouvelle invitation');
            }

            // Créer l'invitation
            $token = Str::random(config('user.invitation.token_length', 64));
            $invitation = $this->repository->createInvitation([
                'tenant_id'      => $tenantId,
                'invited_by'     => Auth::id(),
                'email'          => $email,
                'role_in_tenant' => $role,
                'token'          => $token,
                'expires_at'     => now()->addDays(config('user.invitation.expire_days', 7)),
            ]);

            // Envoyer la notification
            $invitation->notify(new UserInvitationNotification($invitation));

            event(new UserInvited($invitation));

            Log::channel('daily')->info("[User] Invitation envoyée à {$email} (rôle: {$role})", [
                'tenant_id' => $tenantId,
                'invited_by'=> Auth::id(),
            ]);

            return $invitation;
        });
    }

    public function resendInvitation(UserInvitation $invitation): UserInvitation
    {
        if (!$invitation->is_active) {
            throw new \RuntimeException('Cette invitation ne peut pas être renvoyée.');
        }

        $cooldown = config('user.invitation.resend_cooldown', 24);
        if ($invitation->last_resent_at && $invitation->last_resent_at->diffInHours(now()) < $cooldown) {
            throw new \RuntimeException("Veuillez attendre {$cooldown}h avant de renvoyer l'invitation.");
        }

        $invitation->update([
            'resend_count'  => $invitation->resend_count + 1,
            'last_resent_at'=> now(),
            'expires_at'    => now()->addDays(config('user.invitation.expire_days', 7)),
        ]);

        $invitation->notify(new UserInvitationNotification($invitation));

        return $invitation->fresh();
    }

    public function revokeInvitation(UserInvitation $invitation): UserInvitation
    {
        return $this->repository->revokeInvitation($invitation, 'Révoquée manuellement');
    }

    /**
     * Accepter une invitation et créer/lier l'utilisateur
     */
    public function acceptInvitation(UserInvitation $invitation, array $userData): User
    {
        return DB::transaction(function () use ($invitation, $userData) {
            if (!$invitation->is_active) {
                throw new \RuntimeException('Cette invitation n\'est plus valide.');
            }

            // Créer l'utilisateur
            $user = User::create([
                'name'                    => $userData['name'],
                'email'                   => $invitation->email,
                'password'                => Hash::make($userData['password']),
                'tenant_id'               => $invitation->tenant_id,
                'role_in_tenant'          => $invitation->role_in_tenant,
                'is_tenant_owner'         => false,
                'status'                  => 'active',
                'invited_by'              => $invitation->invited_by,
                'invitation_token'        => null,
                'invitation_accepted_at'  => now(),
            ]);

            // Assigner le rôle Spatie
            $user->assignRole($invitation->role_in_tenant);

            // Marquer l'invitation comme acceptée
            $invitation->update(['accepted_at' => now()]);

            event(new UserActivated($user));

            Log::channel('daily')->info("[User] Invitation acceptée par {$user->email}");

            return $user;
        });
    }

    // ── Utilitaires ────────────────────────────────────────────────────────

    public function getInvitations(array $filters): LengthAwarePaginator
    {
        $perPage = min((int)($filters['per_page'] ?? 15), 100);
        return $this->repository->getInvitations($filters, $perPage);
    }
}