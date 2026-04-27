<?php

namespace App\Services;

use App\Models\Draft;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class DraftService
{
    public function saveForCurrentActor(array $attributes): Draft
    {
        $this->ensureDraftsTableIsReady();
        [$userId, $tenantId] = $this->resolveActor();
        $type = $this->normalizeType($attributes['type'] ?? null);
        $route = $this->normalizeRoute($attributes['route'] ?? null);
        $data = $this->normalizeData($attributes['data'] ?? []);

        $draft = $this->baseQuery($userId, $tenantId, $type, $route)
            ->latest('updated_at')
            ->first();

        if (!$draft) {
            $draft = new Draft([
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'type' => $type,
                'route' => $route,
            ]);
        }

        $draft->fill([
            'data' => $data,
            'expires_at' => $this->resolveExpiresAt(),
            'reminded_at' => null,
        ]);
        $draft->save();

        Draft::query()
            ->forActor($userId, $tenantId)
            ->where('type', $type)
            ->when(
                $route === null,
                fn (Builder $query) => $query->whereNull('route'),
                fn (Builder $query) => $query->where('route', $route)
            )
            ->whereKeyNot($draft->getKey())
            ->delete();

        return $draft->fresh();
    }

    public function loadLatestForCurrentActor(string $type, ?string $route = null): ?Draft
    {
        $this->ensureDraftsTableIsReady();
        [$userId, $tenantId] = $this->resolveActor();

        return $this->baseQuery($userId, $tenantId, $type, $route)
            ->notExpired()
            ->latest('updated_at')
            ->first();
    }

    public function deleteForCurrentActor(int $draftId): bool
    {
        $this->ensureDraftsTableIsReady();
        if ($draftId <= 0) {
            return false;
        }

        [$userId, $tenantId] = $this->resolveActor();

        return (bool) Draft::query()
            ->forActor($userId, $tenantId)
            ->whereKey($draftId)
            ->delete();
    }

    public function forgetFromRequest(Request $request): void
    {
        $draftId = (int) $request->input('draft_id');
        if ($draftId > 0) {
            $this->deleteForCurrentActor($draftId);
        }
    }

    public function formatDraft(Draft $draft): array
    {
        return [
            'id' => (int) $draft->id,
            'type' => (string) $draft->type,
            'route' => $draft->route,
            'data' => is_array($draft->data) ? $draft->data : [],
            'updated_at' => optional($draft->updated_at)?->toIso8601String(),
            'expires_at' => optional($draft->expires_at)?->toIso8601String(),
            'resume_url' => $this->resolveResumeUrl($draft),
        ];
    }

    public function resolveResumeUrl(Draft $draft): ?string
    {
        $route = trim((string) $draft->route);
        if ($route === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $route) === 1) {
            return $route;
        }

        return url($route);
    }

    protected function baseQuery(int $userId, int $tenantId, string $type, ?string $route): Builder
    {
        return Draft::query()
            ->forActor($userId, $tenantId)
            ->where('type', $this->normalizeType($type))
            ->when(
                $route === null,
                fn (Builder $query) => $query->whereNull('route'),
                fn (Builder $query) => $query->where('route', $this->normalizeRoute($route))
            );
    }

    protected function resolveActor(): array
    {
        /** @var Authenticatable&object|null $user */
        $user = auth()->user();
        if (!$user) {
            throw new RuntimeException('Utilisateur non authentifie.');
        }

        $tenantId = (int) (session('current_tenant_id') ?? $user->tenant_id ?? 0);
        if ($tenantId <= 0) {
            throw new RuntimeException('Tenant invalide pour la sauvegarde du brouillon.');
        }

        return [(int) $user->getAuthIdentifier(), $tenantId];
    }

    protected function normalizeType(?string $type): string
    {
        $normalized = trim((string) $type);
        if ($normalized === '') {
            throw new RuntimeException('Le type de brouillon est obligatoire.');
        }

        return $normalized;
    }

    protected function normalizeRoute(?string $route): ?string
    {
        $normalized = trim((string) $route);

        return $normalized === '' ? null : $normalized;
    }

    protected function normalizeData(mixed $data): array
    {
        if (!is_array($data)) {
            return [];
        }

        unset($data['_token'], $data['_method'], $data['draft_id']);

        return $data;
    }

    protected function resolveExpiresAt(): ?Carbon
    {
        $days = (int) config('drafts.expire_after_days', 30);

        return $days > 0 ? now()->addDays($days) : null;
    }

    protected function ensureDraftsTableIsReady(): void
    {
        if (!Schema::hasTable('drafts')) {
            throw new RuntimeException('Le systeme de brouillons n est pas initialise. Lancez les migrations pour activer la sauvegarde automatique.');
        }
    }
}
