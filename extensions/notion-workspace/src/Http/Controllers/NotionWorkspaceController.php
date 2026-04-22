<?php

namespace NexusExtensions\NotionWorkspace\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use NexusExtensions\NotionWorkspace\Http\Requests\NotionPageStoreRequest;
use NexusExtensions\NotionWorkspace\Http\Requests\NotionPageUpdateRequest;
use NexusExtensions\NotionWorkspace\Models\NotionPage;
use NexusExtensions\NotionWorkspace\Models\NotionPageActivity;
use NexusExtensions\NotionWorkspace\Models\NotionPageShare;
use Vendor\Client\Models\Client;

class NotionWorkspaceController extends Controller
{
    public function index()
    {
        $this->authorizePermission('notion.view');

        $firstPage = $this->baseQuery()
            ->where('is_archived', false)
            ->orderByDesc('is_favorite')
            ->orderBy('title')
            ->first();

        return view('notion-workspace::notion.index', [
            'initialPageId' => $firstPage?->id,
            'visibilities' => config('notion-workspace.visibilities', []),
            'clients' => Client::query()->orderBy('company_name')->get(['id', 'company_name']),
            'users' => User::query()
                ->where('tenant_id', auth()->user()->tenant_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function treeData(Request $request): JsonResponse
    {
        $this->authorizePermission('notion.view');

        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'scope' => ['nullable', 'in:all,favorites,templates,archived'],
        ]);

        $scope = (string) $request->string('scope', 'all');
        $search = trim((string) $request->string('search'));

        $query = $this->baseQuery()
            ->with(['owner:id,name', 'client:id,company_name'])
            ->orderByDesc('is_favorite')
            ->orderBy('sort_order')
            ->orderBy('title');

        if ($scope === 'favorites') {
            $query->where('is_favorite', true)->where('is_archived', false);
        } elseif ($scope === 'templates') {
            $query->where('is_template', true)->where('is_archived', false);
        } elseif ($scope === 'archived') {
            $query->where('is_archived', true);
        } else {
            $query->where('is_archived', false);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content_text', 'like', "%{$search}%");
            });
        }

        $pages = $query->get();

        return response()->json([
            'success' => true,
            'data' => $pages->map(fn (NotionPage $page) => $this->formatPageTreeNode($page))->values(),
        ]);
    }

    public function show(NotionPage $page): JsonResponse
    {
        $this->authorizePageAccess($page, 'notion.view');

        $page->load([
            'owner:id,name,email',
            'editor:id,name,email',
            'client:id,company_name',
            'shares.user:id,name,email',
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatPage($page),
        ]);
    }

    public function store(NotionPageStoreRequest $request): JsonResponse
    {
        $this->authorizePermission('notion.create');

        $payload = $request->validated();
        $this->assertTenantParent($payload['parent_id'] ?? null);
        $this->assertTenantClient($payload['client_id'] ?? null);
        $payload['owner_id'] = (int) auth()->id();
        $payload['visibility'] = $payload['visibility'] ?? 'private';
        $payload['slug'] = $this->makeSlug((string) $payload['title']);
        $payload['sort_order'] = ((int) $this->baseQuery()
                ->where('parent_id', $payload['parent_id'] ?? null)
                ->max('sort_order')) + 1;
        $payload['last_edited_by'] = (int) auth()->id();
        $payload['last_edited_at'] = now();

        if (isset($payload['content_json']) && is_string($payload['content_json'])) {
            $decoded = json_decode($payload['content_json'], true);
            $payload['content_json'] = is_array($decoded) ? $decoded : null;
        }

        $page = NotionPage::query()->create($payload);

        $this->log($page, 'page_created', 'Page creee');

        return response()->json([
            'success' => true,
            'message' => 'Page creee avec succes.',
            'data' => $this->formatPage($page->fresh(['owner:id,name,email', 'editor:id,name,email', 'client:id,company_name'])),
        ], 201);
    }

    public function update(NotionPageUpdateRequest $request, NotionPage $page): JsonResponse
    {
        $this->authorizePageAccess($page, 'notion.update');

        $payload = $request->validated();
        $this->assertTenantParent($payload['parent_id'] ?? $page->parent_id);
        $this->assertTenantClient($payload['client_id'] ?? $page->client_id);

        if (isset($payload['content_json']) && is_string($payload['content_json'])) {
            $decoded = json_decode($payload['content_json'], true);
            $payload['content_json'] = is_array($decoded) ? $decoded : null;
        }

        if (array_key_exists('parent_id', $payload) && (int) ($payload['parent_id'] ?? 0) === (int) $page->id) {
            return response()->json([
                'success' => false,
                'message' => 'Une page ne peut pas etre son propre parent.',
            ], 422);
        }

        $payload['last_edited_by'] = (int) auth()->id();
        $payload['last_edited_at'] = now();
        $page->update($payload);

        $this->log($page, 'page_updated', 'Page mise a jour');

        return response()->json([
            'success' => true,
            'message' => 'Page mise a jour.',
            'data' => $this->formatPage($page->fresh(['owner:id,name,email', 'editor:id,name,email', 'client:id,company_name'])),
        ]);
    }

    public function destroy(NotionPage $page): JsonResponse
    {
        $this->authorizePageAccess($page, 'notion.delete');

        $page->update(['is_archived' => true]);
        $this->log($page, 'page_archived', 'Page archivee');

        return response()->json([
            'success' => true,
            'message' => 'Page archivee.',
        ]);
    }

    public function duplicate(NotionPage $page): JsonResponse
    {
        $this->authorizePageAccess($page, 'notion.create');

        $copy = $page->replicate([
            'slug',
            'last_edited_by',
            'last_edited_at',
            'is_favorite',
            'created_at',
            'updated_at',
        ]);

        $copy->title = $page->title . ' (Copie)';
        $copy->slug = $this->makeSlug($copy->title);
        $copy->is_favorite = false;
        $copy->last_edited_by = (int) auth()->id();
        $copy->last_edited_at = now();
        $copy->save();

        $this->log($copy, 'page_duplicated', 'Page dupliquee');

        return response()->json([
            'success' => true,
            'message' => 'Page dupliquee.',
            'data' => $this->formatPage($copy->fresh(['owner:id,name,email', 'editor:id,name,email', 'client:id,company_name'])),
        ], 201);
    }

    public function toggleFavorite(NotionPage $page): JsonResponse
    {
        $this->authorizePageAccess($page, 'notion.update');

        $page->is_favorite = !$page->is_favorite;
        $page->last_edited_by = (int) auth()->id();
        $page->last_edited_at = now();
        $page->save();

        $this->log($page, 'page_favorite_toggled', $page->is_favorite ? 'Page ajoutee aux favoris' : 'Page retiree des favoris');

        return response()->json([
            'success' => true,
            'message' => $page->is_favorite ? 'Ajoutee aux favoris.' : 'Retiree des favoris.',
            'data' => ['is_favorite' => (bool) $page->is_favorite],
        ]);
    }

    public function move(NotionPage $page, Request $request): JsonResponse
    {
        $this->authorizePageAccess($page, 'notion.update');

        $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:notion_pages,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $parentId = $request->filled('parent_id') ? (int) $request->integer('parent_id') : null;
        $this->assertTenantParent($parentId);
        if ($parentId === (int) $page->id) {
            return response()->json(['success' => false, 'message' => 'Parent invalide.'], 422);
        }

        $page->parent_id = $parentId;
        $page->sort_order = (int) $request->integer('sort_order', 0);
        $page->last_edited_by = (int) auth()->id();
        $page->last_edited_at = now();
        $page->save();

        $this->log($page, 'page_moved', 'Page deplacee');

        return response()->json([
            'success' => true,
            'message' => 'Page deplacee.',
        ]);
    }

    public function syncShares(NotionPage $page, Request $request): JsonResponse
    {
        $this->authorizePageAccess($page, 'notion.share');

        $request->validate([
            'shares' => ['required', 'array'],
            'shares.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'shares.*.can_edit' => ['nullable', 'boolean'],
            'shares.*.can_comment' => ['nullable', 'boolean'],
            'shares.*.can_share' => ['nullable', 'boolean'],
        ]);

        $shares = collect($request->input('shares', []))
            ->map(function (array $row): array {
                return [
                    'user_id' => (int) ($row['user_id'] ?? 0),
                    'can_edit' => (bool) ($row['can_edit'] ?? true),
                    'can_comment' => (bool) ($row['can_comment'] ?? true),
                    'can_share' => (bool) ($row['can_share'] ?? false),
                ];
            })
            ->filter(fn (array $row): bool => $row['user_id'] > 0)
            ->unique('user_id')
            ->values();

        $allowedIds = User::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->whereIn('id', $shares->pluck('user_id')->all())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $shares = $shares->filter(fn (array $row): bool => in_array((int) $row['user_id'], $allowedIds, true))->values();

        $existingIds = NotionPageShare::query()
            ->where('notion_page_id', $page->id)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id);

        $incomingIds = $shares->pluck('user_id');
        $toDelete = $existingIds->diff($incomingIds)->all();
        if (!empty($toDelete)) {
            NotionPageShare::query()
                ->where('notion_page_id', $page->id)
                ->whereIn('user_id', $toDelete)
                ->delete();
        }

        foreach ($shares as $share) {
            NotionPageShare::query()->updateOrCreate(
                ['notion_page_id' => $page->id, 'user_id' => $share['user_id']],
                [
                    'can_edit' => $share['can_edit'],
                    'can_comment' => $share['can_comment'],
                    'can_share' => $share['can_share'],
                    'shared_by' => (int) auth()->id(),
                ]
            );
        }

        $this->log($page, 'page_shared', 'Partages synchronises');

        return response()->json([
            'success' => true,
            'message' => 'Partages mis a jour.',
            'data' => NotionPageShare::query()
                ->where('notion_page_id', $page->id)
                ->with('user:id,name,email')
                ->get(),
        ]);
    }

    public function activities(NotionPage $page): JsonResponse
    {
        $this->authorizePageAccess($page, 'notion.view');

        return response()->json([
            'success' => true,
            'data' => NotionPageActivity::query()
                ->where('notion_page_id', $page->id)
                ->with('user:id,name,email')
                ->latest()
                ->limit(50)
                ->get(),
        ]);
    }

    private function authorizePermission(string $permission): void
    {
        if ($this->isTenantAdmin() || auth()->user()->can($permission)) {
            return;
        }

        if ($permission === 'notion.view' && auth()->user()->can('notion.create')) {
            return;
        }

        abort(403, 'Permission insuffisante: ' . $permission);
    }

    private function authorizePageAccess(NotionPage $page, string $permission): void
    {
        $this->authorizePermission($permission);

        if ($this->isTenantAdmin()) {
            return;
        }

        if ((int) $page->owner_id === (int) auth()->id()) {
            return;
        }

        if ($page->visibility === 'public') {
            return;
        }

        if ($page->visibility === 'team' && auth()->user()->can('notion.view')) {
            return;
        }

        $share = NotionPageShare::query()
            ->where('notion_page_id', $page->id)
            ->where('user_id', (int) auth()->id())
            ->first();

        if ($share) {
            if (in_array($permission, ['notion.view', 'notion.comment'], true)) {
                return;
            }
            if ($permission === 'notion.update' && $share->can_edit) {
                return;
            }
            if ($permission === 'notion.share' && $share->can_share) {
                return;
            }
        }

        abort(403, 'Acces non autorise a cette page.');
    }

    private function baseQuery()
    {
        $query = NotionPage::query();

        if ($this->isTenantAdmin()) {
            return $query;
        }

        $userId = (int) auth()->id();

        return $query->where(function ($q) use ($userId) {
            $q->where('owner_id', $userId)
                ->orWhere('visibility', 'public')
                ->orWhere('visibility', 'team')
                ->orWhereHas('shares', fn ($share) => $share->where('user_id', $userId));
        });
    }

    private function makeSlug(string $title): string
    {
        $base = Str::slug($title);
        $base = $base !== '' ? $base : 'page';
        $slug = $base;
        $i = 1;

        while (NotionPage::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    private function isTenantAdmin(): bool
    {
        return in_array((string) auth()->user()->role_in_tenant, ['owner', 'admin'], true)
            || (bool) auth()->user()->is_tenant_owner;
    }

    private function formatPageTreeNode(NotionPage $page): array
    {
        return [
            'id' => $page->id,
            'parent_id' => $page->parent_id,
            'title' => $page->title,
            'icon' => $page->icon,
            'cover_color' => $page->cover_color,
            'visibility' => $page->visibility,
            'client_name' => $page->client?->company_name,
            'owner_name' => $page->owner?->name,
            'is_favorite' => (bool) $page->is_favorite,
            'is_template' => (bool) $page->is_template,
            'is_archived' => (bool) $page->is_archived,
            'sort_order' => (int) $page->sort_order,
            'updated_at' => optional($page->updated_at)->format('Y-m-d H:i:s'),
        ];
    }

    private function formatPage(NotionPage $page): array
    {
        return [
            'id' => $page->id,
            'parent_id' => $page->parent_id,
            'client_id' => $page->client_id,
            'owner_id' => $page->owner_id,
            'title' => $page->title,
            'slug' => $page->slug,
            'icon' => $page->icon,
            'cover_color' => $page->cover_color,
            'visibility' => $page->visibility,
            'content_text' => $page->content_text,
            'content_json' => $page->content_json,
            'is_favorite' => (bool) $page->is_favorite,
            'is_template' => (bool) $page->is_template,
            'is_archived' => (bool) $page->is_archived,
            'sort_order' => (int) $page->sort_order,
            'owner_name' => $page->owner?->name,
            'client_name' => $page->client?->company_name,
            'last_edited_by' => $page->last_edited_by,
            'last_edited_by_name' => $page->editor?->name,
            'last_edited_at' => optional($page->last_edited_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($page->updated_at)->format('Y-m-d H:i:s'),
            'shares' => $page->shares->map(function (NotionPageShare $share): array {
                return [
                    'id' => $share->id,
                    'user_id' => $share->user_id,
                    'user_name' => $share->user?->name,
                    'user_email' => $share->user?->email,
                    'can_edit' => (bool) $share->can_edit,
                    'can_comment' => (bool) $share->can_comment,
                    'can_share' => (bool) $share->can_share,
                ];
            })->values(),
        ];
    }

    private function log(NotionPage $page, string $event, string $description, array $payload = []): void
    {
        NotionPageActivity::query()->create([
            'notion_page_id' => $page->id,
            'user_id' => (int) auth()->id(),
            'event' => $event,
            'description' => $description,
            'payload' => $payload,
        ]);
    }

    private function assertTenantParent(?int $parentId): void
    {
        if (!$parentId) {
            return;
        }

        $exists = NotionPage::query()->where('id', $parentId)->exists();
        if (!$exists) {
            abort(422, 'Parent invalide pour ce tenant.');
        }
    }

    private function assertTenantClient(?int $clientId): void
    {
        if (!$clientId) {
            return;
        }

        $exists = Client::query()->where('id', $clientId)->exists();
        if (!$exists) {
            abort(422, 'Client invalide pour ce tenant.');
        }
    }
}
