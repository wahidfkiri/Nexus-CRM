<?php

namespace Vendor\User\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Vendor\Rbac\Services\TenantRoleService;
use Vendor\User\Events\UserActivated;
use Vendor\User\Events\UserInvited;
use Vendor\User\Events\UserRoleChanged;
use Vendor\User\Events\UserSuspended;
use Vendor\User\Models\UserInvitation;
use Vendor\User\Notifications\UserInvitationNotification;
use Vendor\User\Repositories\UserRepository;

class UserService
{
    public function __construct(
        protected UserRepository $repository,
        protected TenantRoleService $tenantRoleService,
    ) {
    }

    public function getFilteredUsers(array $filters): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? config('user.pagination.per_page', 15)), 100);

        return $this->repository->getFiltered($filters, $perPage);
    }

    public function getStats(): array
    {
        return [
            'total' => $this->repository->count(),
            'active' => $this->repository->countByStatus('active'),
            'invited' => $this->repository->countByStatus('invited'),
            'inactive' => $this->repository->countByStatus('inactive'),
            'suspended' => $this->repository->countByStatus('suspended'),
            'by_role' => $this->repository->countByRole(),
        ];
    }

    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $tenantId = (int) Auth::user()->tenant_id;
            $this->tenantRoleService->ensureTenantRoles($tenantId);

            $oldRole = (string) $user->role_in_tenant;
            $newRoleData = array_key_exists('role_id', $data) || array_key_exists('role_in_tenant', $data)
                ? $this->resolveRolePayload($data, $tenantId)
                : null;
            $newRole = (string) ($newRoleData['role_name'] ?? $oldRole);

            $user = $this->repository->update($user, array_filter([
                'name' => $data['name'] ?? null,
                'email' => isset($data['email']) ? mb_strtolower(trim((string) $data['email'])) : null,
                'phone' => $data['phone'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'department' => $data['department'] ?? null,
                'status' => $data['status'] ?? null,
                'role_in_tenant' => $newRole,
                'role_id' => $newRoleData['role_id'] ?? null,
            ], static fn ($value) => $value !== null));

            if ($newRoleData) {
                $this->tenantRoleService->syncUserRole($user, $tenantId, (int) $newRoleData['role_id'], [
                    'status' => (string) ($data['status'] ?? 'active'),
                    'is_tenant_owner' => $newRole === 'owner',
                ]);
            }

            if ($newRole !== $oldRole) {
                event(new UserRoleChanged($user, $oldRole, $newRole));
                Log::channel('daily')->info("[User] Role update #{$user->id}: {$oldRole} -> {$newRole}");
            }

            return $user->fresh(['roles']);
        });
    }

    public function deleteUser(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            $result = $this->repository->delete($user);
            Log::channel('daily')->info("[User] Deleted #{$user->id} ({$user->email})");

            return $result;
        });
    }

    public function bulkDelete(array $ids): int
    {
        return DB::transaction(fn () => $this->repository->bulkDelete($ids));
    }

    public function bulkStatusUpdate(array $ids, string $status): int
    {
        return DB::transaction(fn () => $this->repository->bulkStatusUpdate($ids, $status));
    }

    public function suspendUser(User $user): User
    {
        return DB::transaction(function () use ($user) {
            $user = $this->repository->update($user, ['status' => 'inactive']);
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

        if ($user->avatar) {
            \Illuminate\Support\Facades\Storage::disk($disk)->delete($user->avatar);
        }

        $filename = $file->store($path, $disk);

        return $this->repository->update($user, ['avatar' => $filename]);
    }

    public function invite(array $data): UserInvitation
    {
        return DB::transaction(function () use ($data) {
            /** @var User $actor */
            $actor = Auth::user();
            $tenantId = (int) $actor->tenant_id;
            $this->tenantRoleService->ensureTenantRoles($tenantId);

            $email = mb_strtolower(trim((string) $data['email']));
            $role = $this->resolveRolePayload($data, $tenantId);

            UserInvitation::query()
                ->where('tenant_id', $tenantId)
                ->where('pending_email_key', $email)
                ->lockForUpdate()
                ->get();

            $existingUser = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

            if ($existingUser && $existingUser->hasTenantAccess($tenantId)) {
                throw new \RuntimeException('Cet email est déjà associé à un membre de votre équipe.');
            }

            if ($this->repository->pendingInvitationForEmail($email, $tenantId)) {
                throw new \RuntimeException('Une invitation active existe déjà pour cet email.');
            }

            $invitation = $this->repository->createInvitation([
                'tenant_id' => $tenantId,
                'user_id' => $existingUser?->id,
                'invited_by' => $actor->id,
                'email' => $email,
                'role_id' => $role['role_id'],
                'role_in_tenant' => $role['role_name'],
                'token' => (string) Str::uuid(),
                'expires_at' => now()->addDays((int) config('user.invitation.expire_days', 7)),
                'status' => UserInvitation::STATUS_PENDING,
                'pending_email_key' => $email,
            ]);

            $invitation->notify(new UserInvitationNotification($invitation));
            event(new UserInvited($invitation));

            Log::channel('daily')->info('[User] Invitation created', [
                'tenant_id' => $tenantId,
                'email' => $email,
                'role' => $role['role_name'],
                'invited_by' => $actor->id,
            ]);

            return $invitation->fresh(['tenant', 'invitedBy', 'role', 'invitedUser']);
        });
    }

    public function resendInvitation(UserInvitation $invitation): UserInvitation
    {
        $invitation->markExpiredIfNeeded();

        if (!$invitation->isUsable()) {
            throw new \RuntimeException('Cette invitation ne peut plus être renvoyée.');
        }

        $cooldown = (int) config('user.invitation.resend_cooldown', 24);
        if ($invitation->last_resent_at && $invitation->last_resent_at->diffInHours(now()) < $cooldown) {
            throw new \RuntimeException("Veuillez attendre {$cooldown}h avant de renvoyer l’invitation.");
        }

        $invitation->update([
            'token' => (string) Str::uuid(),
            'resend_count' => (int) $invitation->resend_count + 1,
            'last_resent_at' => now(),
            'expires_at' => now()->addDays((int) config('user.invitation.expire_days', 7)),
            'status' => UserInvitation::STATUS_PENDING,
            'pending_email_key' => mb_strtolower((string) $invitation->email),
        ]);

        $invitation->notify(new UserInvitationNotification($invitation));

        return $invitation->fresh(['tenant', 'invitedBy', 'role', 'invitedUser']);
    }

    public function revokeInvitation(UserInvitation $invitation): UserInvitation
    {
        return $this->repository->revokeInvitation($invitation, 'Révoquée manuellement');
    }

    public function acceptInvitation(UserInvitation $invitation, array $userData = []): User
    {
        return DB::transaction(function () use ($invitation, $userData) {
            $invitation->refresh();
            $invitation->markExpiredIfNeeded();

            if (!$invitation->isUsable()) {
                throw new \RuntimeException('Cette invitation n’est plus valide.');
            }

            $tenantId = (int) $invitation->tenant_id;
            $this->tenantRoleService->ensureTenantRoles($tenantId);

            $email = mb_strtolower(trim((string) $invitation->email));
            $existingUser = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
            $actingUser = $userData['user'] ?? Auth::user();

            if ($actingUser instanceof User) {
                if (mb_strtolower((string) $actingUser->email) !== $email) {
                    throw new \RuntimeException('Cette invitation est liée à un autre email.');
                }

                if ($existingUser && (int) $existingUser->id !== (int) $actingUser->id) {
                    throw new \RuntimeException('Un autre compte utilise déjà cette invitation.');
                }

                if ($actingUser->hasTenantAccess($tenantId)) {
                    throw new \RuntimeException('Cet utilisateur est déjà membre de cette équipe.');
                }

                $this->attachMembershipFromInvitation($actingUser, $invitation);
                $this->markInvitationAccepted($invitation, $actingUser);

                event(new UserActivated($actingUser));

                return $actingUser->fresh(['roles']);
            }

            if ($existingUser) {
                throw new \RuntimeException('Un compte existe déjà avec cet email. Connectez-vous pour accepter cette invitation.');
            }

            $name = trim((string) ($userData['name'] ?? ''));
            $password = (string) ($userData['password'] ?? '');

            if ($name === '' || $password === '') {
                throw new \RuntimeException('Nom et mot de passe requis pour créer un nouveau compte.');
            }

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'tenant_id' => $tenantId,
                'role_in_tenant' => (string) $invitation->role_in_tenant,
                'is_tenant_owner' => false,
                'status' => 'active',
                'is_active' => true,
            ]);

            $this->attachMembershipFromInvitation($user, $invitation);
            $this->markInvitationAccepted($invitation, $user);

            event(new UserActivated($user));

            return $user->fresh(['roles']);
        });
    }

    public function getInvitations(array $filters): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $this->repository->getInvitations($filters, $perPage);
    }

    private function resolveRolePayload(array $data, int $tenantId): array
    {
        $roleId = isset($data['role_id']) ? (int) $data['role_id'] : null;
        $roleName = trim((string) ($data['role_in_tenant'] ?? ''));
        $resolvedRole = $this->tenantRoleService->resolveTenantRole($tenantId, $roleId ?: $roleName);

        if ($resolvedRole->name === 'owner' || !array_key_exists($resolvedRole->name, config('user.tenant_roles', []))) {
            throw new \RuntimeException('Ce rôle ne peut pas être attribué.');
        }

        return [
            'role_id' => (int) $resolvedRole->id,
            'role_name' => (string) $resolvedRole->name,
        ];
    }

    private function attachMembershipFromInvitation(User $user, UserInvitation $invitation): void
    {
        $tenantId = (int) $invitation->tenant_id;

        if ($user->hasTenantAccess($tenantId)) {
            throw new \RuntimeException('Cet utilisateur est déjà membre de cette équipe.');
        }

        $this->tenantRoleService->syncUserRole(
            $user,
            $tenantId,
            $invitation->role_id ? (int) $invitation->role_id : (string) $invitation->role_in_tenant,
            [
                'invited_by' => $invitation->invited_by,
                'joined_at' => now(),
                'status' => 'active',
                'is_tenant_owner' => false,
            ]
        );

        if (!(int) $user->getOriginal('tenant_id')) {
            $user->forceFill([
                'tenant_id' => $tenantId,
                'role_in_tenant' => (string) $invitation->role_in_tenant,
                'is_tenant_owner' => false,
                'status' => 'active',
                'is_active' => true,
            ])->save();
        }
    }

    private function markInvitationAccepted(UserInvitation $invitation, User $user): void
    {
        $invitation->update([
            'user_id' => (int) $user->id,
            'accepted_at' => now(),
            'status' => UserInvitation::STATUS_ACCEPTED,
            'pending_email_key' => null,
        ]);
    }
}
