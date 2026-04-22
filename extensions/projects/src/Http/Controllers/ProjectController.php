<?php

namespace NexusExtensions\Projects\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use NexusExtensions\Projects\Http\Requests\ProjectStoreRequest;
use NexusExtensions\Projects\Http\Requests\ProjectTaskStoreRequest;
use NexusExtensions\Projects\Http\Requests\ProjectTaskUpdateRequest;
use NexusExtensions\Projects\Http\Requests\ProjectUpdateRequest;
use NexusExtensions\Projects\Models\Project;
use NexusExtensions\Projects\Models\ProjectActivity;
use NexusExtensions\Projects\Models\ProjectFile;
use NexusExtensions\Projects\Models\ProjectMember;
use NexusExtensions\Projects\Models\ProjectTask;
use NexusExtensions\Projects\Models\ProjectTaskChecklist;
use NexusExtensions\Projects\Models\ProjectTaskComment;
use NexusExtensions\Projects\Models\ProjectTaskFile;
use NexusExtensions\GoogleDrive\Models\GoogleDriveFile;
use NexusExtensions\GoogleDrive\Services\GoogleDriveService;
use RuntimeException;
use Vendor\Client\Models\Client;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;

class ProjectController extends Controller
{
    public function index()
    {
        $this->authorizePermission('projects.view');

        return view('projects::projects.index', [
            'statuses' => config('projects.project_statuses', []),
            'priorities' => config('projects.priorities', []),
            'clients' => Client::query()->orderBy('company_name')->get(['id', 'company_name']),
            'users' => User::query()
                ->where('tenant_id', auth()->user()->tenant_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function show(Project $project)
    {
        $this->authorizeProjectAccess($project, 'projects.view');

        $project->load([
            'client:id,company_name,contact_name,email,phone',
            'owner:id,name,email',
            'members.user:id,name,email,status,role_in_tenant',
        ]);

        return view('projects::projects.show', [
            'project' => $project,
            'taskStatuses' => config('projects.task_statuses', []),
            'priorities' => config('projects.priorities', []),
            'clients' => Client::query()->orderBy('company_name')->get(['id', 'company_name']),
            'users' => User::query()
                ->where('tenant_id', auth()->user()->tenant_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorizePermission('projects.view');

        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:40'],
            'priority' => ['nullable', 'string', 'max:40'],
            'client_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'sort_by' => ['nullable', 'in:name,status,priority,progress,due_date,created_at'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
        ]);

        $query = Project::query()
            ->with([
                'client:id,company_name',
                'owner:id,name',
            ])
            ->withCount(['tasks', 'members']);

        if (!$this->hasAnyPermission(['projects.admin']) && !$this->isTenantAdmin()) {
            $userId = (int) auth()->id();
            $query->where(function ($q) use ($userId) {
                $q->where('owner_id', $userId)
                    ->orWhereHas('members', fn ($m) => $m->where('user_id', $userId)->where('is_active', true));
            });
        }

        if ($request->filled('search')) {
            $term = (string) $request->string('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhereHas('client', fn ($c) => $c->where('company_name', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', (string) $request->string('priority'));
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', (int) $request->integer('client_id'));
        }

        $sortBy = (string) $request->string('sort_by', 'created_at');
        $sortDir = (string) $request->string('sort_dir', 'desc');
        $perPage = (int) $request->integer('per_page', 15);

        $projects = $query->orderBy($sortBy, $sortDir)->paginate($perPage);

        return response()->json([
            'data' => $projects->getCollection()->map(fn (Project $project) => $this->formatProject($project))->values(),
            'current_page' => $projects->currentPage(),
            'last_page' => $projects->lastPage(),
            'per_page' => $projects->perPage(),
            'total' => $projects->total(),
            'from' => $projects->firstItem(),
            'to' => $projects->lastItem(),
        ]);
    }

    public function stats(): JsonResponse
    {
        $this->authorizePermission('projects.view');

        $query = Project::query();

        if (!$this->hasAnyPermission(['projects.admin']) && !$this->isTenantAdmin()) {
            $userId = (int) auth()->id();
            $query->where(function ($q) use ($userId) {
                $q->where('owner_id', $userId)
                    ->orWhereHas('members', fn ($m) => $m->where('user_id', $userId)->where('is_active', true));
            });
        }

        $total = (clone $query)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'active' => (clone $query)->where('status', 'active')->count(),
                'planning' => (clone $query)->where('status', 'planning')->count(),
                'completed' => (clone $query)->where('status', 'completed')->count(),
                'delayed' => (clone $query)->whereNotIn('status', ['completed', 'archived'])->whereDate('due_date', '<', now()->toDateString())->count(),
            ],
        ]);
    }

    public function store(ProjectStoreRequest $request): JsonResponse
    {
        $this->authorizePermission('projects.create');

        try {
            $project = DB::transaction(function () use ($request) {
                $payload = $request->validated();
                $payload['owner_id'] = (int) auth()->id();
                $payload['status'] = $payload['status'] ?? 'planning';
                $payload['priority'] = $payload['priority'] ?? 'medium';
                $payload['progress'] = 0;
                $payload['slug'] = $this->makeProjectSlug((string) $payload['name']);

                $project = Project::query()->create($payload);

                ProjectMember::query()->updateOrCreate(
                    ['project_id' => $project->id, 'user_id' => (int) auth()->id()],
                    ['role' => 'owner', 'is_active' => true, 'joined_at' => now(), 'invited_by' => (int) auth()->id()]
                );

                $memberIds = collect($payload['member_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->unique();
                if ($memberIds->isNotEmpty()) {
                    $allowedUserIds = User::query()
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->whereIn('id', $memberIds->values())
                        ->pluck('id');

                    foreach ($allowedUserIds as $userId) {
                        ProjectMember::query()->updateOrCreate(
                            ['project_id' => $project->id, 'user_id' => (int) $userId],
                            ['role' => $userId === auth()->id() ? 'owner' : 'member', 'is_active' => true, 'joined_at' => now(), 'invited_by' => (int) auth()->id()]
                        );
                    }
                }

                $this->logActivity('project_created', 'Projet cree', $project, null, ['name' => $project->name]);

                return $project;
            });

            return response()->json([
                'success' => true,
                'message' => 'Projet cree avec succes.',
                'data' => $this->formatProject($project->fresh(['client:id,company_name', 'owner:id,name'])->loadCount(['tasks', 'members'])),
                'redirect' => route('projects.show', $project),
            ], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function update(ProjectUpdateRequest $request, Project $project): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.update');

        try {
            $payload = $request->validated();

            if (($payload['status'] ?? null) === 'completed' && !$project->completed_at) {
                $payload['completed_at'] = now();
                $payload['progress'] = 100;
            }

            $project->update($payload);

            $this->logActivity('project_updated', 'Projet mis a jour', $project, null, $payload);

            return response()->json([
                'success' => true,
                'message' => 'Projet mis a jour.',
                'data' => $this->formatProject($project->fresh(['client:id,company_name', 'owner:id,name'])->loadCount(['tasks', 'members'])),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.delete');

        try {
            DB::transaction(function () use ($project) {
                ProjectTask::query()->where('project_id', $project->id)->delete();
                ProjectMember::query()->where('project_id', $project->id)->delete();
                ProjectActivity::query()->where('project_id', $project->id)->delete();
                $project->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Projet supprime.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function syncMembers(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_members');

        $request->validate([
            'members' => ['required', 'array', 'min:1'],
            'members.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'members.*.role' => ['nullable', 'in:owner,manager,member,viewer'],
        ]);

        try {
            DB::transaction(function () use ($request, $project) {
                $incoming = collect($request->input('members', []));

                $tenantUserIds = User::query()
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->whereIn('id', $incoming->pluck('user_id')->all())
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $allowed = $incoming
                    ->filter(fn ($row) => in_array((int) ($row['user_id'] ?? 0), $tenantUserIds, true))
                    ->map(function ($row) use ($project) {
                        $uid = (int) $row['user_id'];
                        $role = (string) ($row['role'] ?? 'member');

                        if ($uid === (int) $project->owner_id) {
                            $role = 'owner';
                        }

                        return [
                            'user_id' => $uid,
                            'role' => $role,
                        ];
                    })
                    ->unique('user_id')
                    ->values();

                if (!$allowed->pluck('user_id')->contains((int) $project->owner_id)) {
                    $allowed->push(['user_id' => (int) $project->owner_id, 'role' => 'owner']);
                }

                $currentIds = ProjectMember::query()->where('project_id', $project->id)->pluck('user_id')->map(fn ($id) => (int) $id);
                $newIds = $allowed->pluck('user_id');

                $toDelete = $currentIds->diff($newIds)->all();
                if (!empty($toDelete)) {
                    ProjectMember::query()->where('project_id', $project->id)->whereIn('user_id', $toDelete)->delete();
                }

                foreach ($allowed as $row) {
                    ProjectMember::query()->updateOrCreate(
                        ['project_id' => $project->id, 'user_id' => (int) $row['user_id']],
                        [
                            'role' => $row['role'],
                            'is_active' => true,
                            'joined_at' => now(),
                            'invited_by' => (int) auth()->id(),
                        ]
                    );
                }
            });

            $this->logActivity('members_synced', 'Membres du projet synchronises', $project, null, ['updated_by' => auth()->id()]);

            return response()->json([
                'success' => true,
                'message' => 'Membres mis a jour.',
                'data' => ProjectMember::query()
                    ->where('project_id', $project->id)
                    ->with('user:id,name,email')
                    ->orderByRaw("FIELD(role,'owner','manager','member','viewer')")
                    ->orderBy('id')
                    ->get(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function tasksData(Project $project, Request $request): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.view');

        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:backlog,todo,in_progress,review,done,blocked'],
            'assigned_to' => ['nullable', 'integer'],
        ]);

        $query = ProjectTask::query()
            ->where('project_id', $project->id)
            ->with(['assignee:id,name,email', 'creator:id,name', 'client:id,company_name'])
            ->withCount(['comments', 'checklist as checklist_total', 'checklist as checklist_done' => fn ($q) => $q->where('is_done', true)]);

        if ($request->filled('search')) {
            $term = (string) $request->string('search');
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhereHas('assignee', fn ($u) => $u->where('name', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', (int) $request->integer('assigned_to'));
        }

        $tasks = $query->orderBy('position')->orderByDesc('id')->get();

        return response()->json([
            'success' => true,
            'data' => $tasks->map(fn (ProjectTask $task) => $this->formatTask($task))->values(),
            'grouped' => collect(config('projects.task_statuses', []))
                ->keys()
                ->mapWithKeys(fn ($status) => [$status => $tasks->where('status', $status)->map(fn ($task) => $this->formatTask($task))->values()]),
        ]);
    }

    public function storeTask(Project $project, ProjectTaskStoreRequest $request): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');

        try {
            $payload = $request->validated();
            $status = $payload['status'] ?? 'todo';

            if (!empty($payload['assigned_to'])) {
                $this->ensureTenantUser((int) $payload['assigned_to']);
            }

            $payload['project_id'] = $project->id;
            $payload['created_by'] = (int) auth()->id();
            $payload['status'] = $status;
            $payload['position'] = ((int) ProjectTask::query()->where('project_id', $project->id)->where('status', $status)->max('position')) + 1;

            if (!empty($payload['tags']) && is_string($payload['tags'])) {
                $payload['tags'] = collect(explode(',', $payload['tags']))->map(fn ($v) => trim((string) $v))->filter()->values()->all();
            }

            if ($status === 'done') {
                $payload['completed_at'] = now();
            }

            $task = ProjectTask::query()->create($payload);
            $project->recalculateProgress();

            $this->logActivity('task_created', 'Tache creee: ' . $task->title, $project, $task, ['status' => $task->status]);

            return response()->json([
                'success' => true,
                'message' => 'Tache creee avec succes.',
                'data' => $this->formatTask($task->fresh(['assignee:id,name,email', 'creator:id,name', 'client:id,company_name'])->loadCount(['comments', 'checklist as checklist_total', 'checklist as checklist_done' => fn ($q) => $q->where('is_done', true)])),
            ], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function updateTask(Project $project, ProjectTask $task, ProjectTaskUpdateRequest $request): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');
        $this->ensureTaskBelongsToProject($project, $task);

        try {
            $payload = $request->validated();

            if (!empty($payload['assigned_to'])) {
                $this->ensureTenantUser((int) $payload['assigned_to']);
            }

            if (!empty($payload['tags']) && is_string($payload['tags'])) {
                $payload['tags'] = collect(explode(',', $payload['tags']))->map(fn ($v) => trim((string) $v))->filter()->values()->all();
            }

            if (($payload['status'] ?? $task->status) === 'done' && !$task->completed_at) {
                $payload['completed_at'] = now();
            }

            if (($payload['status'] ?? $task->status) !== 'done') {
                $payload['completed_at'] = null;
            }

            $task->update($payload);
            $project->recalculateProgress();

            $this->logActivity('task_updated', 'Tache mise a jour: ' . $task->title, $project, $task, $payload);

            return response()->json([
                'success' => true,
                'message' => 'Tache mise a jour.',
                'data' => $this->formatTask($task->fresh(['assignee:id,name,email', 'creator:id,name', 'client:id,company_name'])->loadCount(['comments', 'checklist as checklist_total', 'checklist as checklist_done' => fn ($q) => $q->where('is_done', true)])),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function moveTask(Project $project, ProjectTask $task, Request $request): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');
        $this->ensureTaskBelongsToProject($project, $task);

        $request->validate([
            'status' => ['required', 'in:backlog,todo,in_progress,review,done,blocked'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $newStatus = (string) $request->string('status');
            $position = (int) $request->integer('position', 0);

            $task->status = $newStatus;
            $task->position = max(0, $position);
            $task->completed_at = $newStatus === 'done' ? ($task->completed_at ?: now()) : null;
            $task->save();

            $project->recalculateProgress();
            $this->logActivity('task_moved', 'Tache deplacee: ' . $task->title, $project, $task, ['status' => $newStatus, 'position' => $position]);

            return response()->json([
                'success' => true,
                'message' => 'Tache deplacee.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroyTask(Project $project, ProjectTask $task): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');
        $this->ensureTaskBelongsToProject($project, $task);

        try {
            DB::transaction(function () use ($project, $task) {
                ProjectTaskChecklist::query()->where('project_task_id', $task->id)->delete();
                ProjectTaskComment::query()->where('project_task_id', $task->id)->delete();
                $task->delete();
                $project->recalculateProgress();
            });

            $this->logActivity('task_deleted', 'Tache supprimee: ' . $task->title, $project, null, ['task_id' => $task->id]);

            return response()->json([
                'success' => true,
                'message' => 'Tache supprimee.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function commentsData(Project $project, ProjectTask $task): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.view');
        $this->ensureTaskBelongsToProject($project, $task);

        $comments = ProjectTaskComment::query()
            ->where('project_task_id', $task->id)
            ->with('user:id,name,email')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $comments,
        ]);
    }

    public function addComment(Project $project, ProjectTask $task, Request $request): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.comment');
        $this->ensureTaskBelongsToProject($project, $task);

        $request->validate([
            'comment' => ['required', 'string', 'max:4000'],
        ]);

        try {
            $comment = ProjectTaskComment::query()->create([
                'project_task_id' => $task->id,
                'user_id' => (int) auth()->id(),
                'comment' => (string) $request->string('comment'),
            ]);

            $this->logActivity('task_commented', 'Commentaire ajoute sur: ' . $task->title, $project, $task, ['comment_id' => $comment->id]);

            return response()->json([
                'success' => true,
                'message' => 'Commentaire ajoute.',
                'data' => $comment->load('user:id,name,email'),
            ], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function checklistStore(Project $project, ProjectTask $task, Request $request): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');
        $this->ensureTaskBelongsToProject($project, $task);

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $item = ProjectTaskChecklist::query()->create([
            'project_task_id' => $task->id,
            'title' => (string) $request->string('title'),
            'position' => ((int) ProjectTaskChecklist::query()->where('project_task_id', $task->id)->max('position')) + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Checklist ajoutee.',
            'data' => $item,
        ], 201);
    }

    public function checklistData(Project $project, ProjectTask $task): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.view');
        $this->ensureTaskBelongsToProject($project, $task);

        $items = ProjectTaskChecklist::query()
            ->where('project_task_id', $task->id)
            ->orderBy('position')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function checklistToggle(Project $project, ProjectTask $task, ProjectTaskChecklist $item): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');
        $this->ensureTaskBelongsToProject($project, $task);

        abort_if((int) $item->project_task_id !== (int) $task->id, 404);

        $item->is_done = !$item->is_done;
        $item->done_by = $item->is_done ? (int) auth()->id() : null;
        $item->done_at = $item->is_done ? now() : null;
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Checklist mise a jour.',
            'data' => $item,
        ]);
    }

    public function checklistDestroy(Project $project, ProjectTask $task, ProjectTaskChecklist $item): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');
        $this->ensureTaskBelongsToProject($project, $task);

        abort_if((int) $item->project_task_id !== (int) $task->id, 404);
        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Checklist supprimee.',
        ]);
    }

    public function filesData(Project $project): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.view');

        $files = ProjectFile::query()
            ->where('project_id', $project->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $files,
        ]);
    }

    public function uploadFile(Project $project, Request $request): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');

        $request->validate([
            'file' => ['required', 'file', 'max:' . ((int) config('google-drive.api.max_file_size_mb', 100) * 1024)],
        ]);

        try {
            $tenantId = (int) auth()->user()->tenant_id;
            $drive = $this->ensureGoogleDriveAvailable($tenantId);
            $folderId = $this->ensureProjectDriveFolder($project, $drive, $tenantId);

            $meta = $drive->uploadFile($tenantId, $request->file('file'), $folderId);

            $row = ProjectFile::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'project_id' => $project->id,
                    'drive_file_id' => (string) ($meta['id'] ?? ''),
                ],
                [
                    'uploaded_by' => (int) auth()->id(),
                    'name' => (string) ($meta['name'] ?? 'Fichier'),
                    'mime_type' => (string) ($meta['mime_type'] ?? ''),
                    'size_bytes' => (int) ($meta['size_bytes'] ?? 0),
                    'web_view_link' => $meta['web_view_link'] ?? null,
                    'download_link' => $meta['download_link'] ?? null,
                    'meta' => $meta,
                ]
            );

            $this->logActivity('file_uploaded', 'Fichier ajoute au projet', $project, null, [
                'drive_file_id' => $row->drive_file_id,
                'name' => $row->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fichier ajoute avec succes.',
                'data' => $row,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function deleteFile(Project $project, ProjectFile $file, Request $request): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');

        abort_if((int) $file->project_id !== (int) $project->id, 404);

        try {
            $tenantId = (int) auth()->user()->tenant_id;
            $drive = $this->ensureGoogleDriveAvailable($tenantId);
            $drive->delete($tenantId, (string) $file->drive_file_id, false);

            $this->logActivity('file_deleted', 'Fichier supprime du projet', $project, null, [
                'drive_file_id' => $file->drive_file_id,
                'name' => $file->name,
            ]);

            $file->delete();

            return response()->json([
                'success' => true,
                'message' => 'Fichier supprime.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function taskFilesData(Project $project, ProjectTask $task): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.view');
        $this->ensureTaskBelongsToProject($project, $task);

        $files = ProjectTaskFile::query()
            ->where('project_task_id', $task->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $files,
        ]);
    }

    public function taskUploadFile(Project $project, ProjectTask $task, Request $request): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');
        $this->ensureTaskBelongsToProject($project, $task);

        $request->validate([
            'file' => ['required', 'file', 'max:' . ((int) config('google-drive.api.max_file_size_mb', 100) * 1024)],
        ]);

        try {
            $tenantId = (int) auth()->user()->tenant_id;
            $drive = $this->ensureGoogleDriveAvailable($tenantId);
            $folderId = $this->ensureTaskDriveFolder($project, $task, $drive, $tenantId);

            $meta = $drive->uploadFile($tenantId, $request->file('file'), $folderId);

            $row = ProjectTaskFile::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'project_task_id' => $task->id,
                    'drive_file_id' => (string) ($meta['id'] ?? ''),
                ],
                [
                    'uploaded_by' => (int) auth()->id(),
                    'name' => (string) ($meta['name'] ?? 'Fichier'),
                    'mime_type' => (string) ($meta['mime_type'] ?? ''),
                    'size_bytes' => (int) ($meta['size_bytes'] ?? 0),
                    'web_view_link' => $meta['web_view_link'] ?? null,
                    'download_link' => $meta['download_link'] ?? null,
                    'meta' => $meta,
                ]
            );

            $this->logActivity('task_file_uploaded', 'Fichier ajoute a la tache', $project, $task, [
                'drive_file_id' => $row->drive_file_id,
                'name' => $row->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fichier ajoute a la tache.',
                'data' => $row,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function taskDeleteFile(Project $project, ProjectTask $task, ProjectTaskFile $file): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');
        $this->ensureTaskBelongsToProject($project, $task);

        abort_if((int) $file->project_task_id !== (int) $task->id, 404);

        try {
            $tenantId = (int) auth()->user()->tenant_id;
            $drive = $this->ensureGoogleDriveAvailable($tenantId);
            $drive->delete($tenantId, (string) $file->drive_file_id, false);

            $this->logActivity('task_file_deleted', 'Fichier supprime de la tache', $project, $task, [
                'drive_file_id' => $file->drive_file_id,
                'name' => $file->name,
            ]);

            $file->delete();

            return response()->json([
                'success' => true,
                'message' => 'Fichier supprime.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function formatProject(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'slug' => $project->slug,
            'description' => $project->description,
            'status' => $project->status,
            'priority' => $project->priority,
            'start_date' => optional($project->start_date)->format('Y-m-d'),
            'due_date' => optional($project->due_date)->format('Y-m-d'),
            'completed_at' => optional($project->completed_at)->format('Y-m-d H:i:s'),
            'budget' => $project->budget,
            'progress' => (int) $project->progress,
            'color' => $project->color,
            'client_id' => $project->client_id,
            'client_name' => $project->client?->company_name,
            'owner_id' => $project->owner_id,
            'owner_name' => $project->owner?->name,
            'tasks_count' => (int) ($project->tasks_count ?? 0),
            'members_count' => (int) ($project->members_count ?? 0),
            'created_at' => optional($project->created_at)->format('Y-m-d H:i:s'),
        ];
    }

    private function formatTask(ProjectTask $task): array
    {
        return [
            'id' => $task->id,
            'project_id' => $task->project_id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'priority' => $task->priority,
            'position' => (int) $task->position,
            'assigned_to' => $task->assigned_to,
            'assignee_name' => $task->assignee?->name,
            'creator_name' => $task->creator?->name,
            'client_name' => $task->client?->company_name,
            'start_date' => optional($task->start_date)->format('Y-m-d'),
            'due_date' => optional($task->due_date)->format('Y-m-d'),
            'completed_at' => optional($task->completed_at)->format('Y-m-d H:i:s'),
            'estimate_hours' => $task->estimate_hours,
            'spent_hours' => $task->spent_hours,
            'tags' => $task->tags ?: [],
            'comments_count' => (int) ($task->comments_count ?? 0),
            'checklist_total' => (int) ($task->checklist_total ?? 0),
            'checklist_done' => (int) ($task->checklist_done ?? 0),
            'created_at' => optional($task->created_at)->format('Y-m-d H:i:s'),
        ];
    }

    private function makeProjectSlug(string $name): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'projet';
        $slug = $base;
        $suffix = 1;

        while (Project::query()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    private function authorizeProjectAccess(Project $project, string $permission): void
    {
        $this->authorizePermission($permission);

        if ($this->hasAnyPermission(['projects.admin']) || $this->isTenantAdmin()) {
            return;
        }

        $userId = (int) auth()->id();
        $isOwner = (int) $project->owner_id === $userId;
        $isMember = ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->exists();

        abort_if(!$isOwner && !$isMember, 403, 'Acces non autorise a ce projet.');
    }

    private function authorizePermission(string $permission): void
    {
        if ($this->isTenantAdmin()) {
            return;
        }

        if (auth()->user()->can($permission)) {
            return;
        }

        if ($permission === 'projects.view' && $this->hasAnyPermission(['projects.create', 'projects.update', 'projects.manage_tasks', 'projects.comment'])) {
            return;
        }

        abort(403, 'Permission insuffisante: ' . $permission);
    }

    private function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (auth()->user()->can($permission)) {
                return true;
            }
        }

        return false;
    }

    private function isTenantAdmin(): bool
    {
        return in_array((string) auth()->user()->role_in_tenant, ['owner', 'admin'], true)
            || (bool) auth()->user()->is_tenant_owner;
    }

    private function ensureTaskBelongsToProject(Project $project, ProjectTask $task): void
    {
        abort_if((int) $task->project_id !== (int) $project->id, 404, 'Tache introuvable pour ce projet.');
    }

    private function ensureTenantUser(int $userId): void
    {
        $exists = User::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('id', $userId)
            ->exists();

        abort_if(!$exists, 422, 'Utilisateur invalide pour ce tenant.');
    }

    private function logActivity(string $event, string $description, Project $project, ?ProjectTask $task = null, array $payload = []): void
    {
        ProjectActivity::query()->create([
            'project_id' => $project->id,
            'project_task_id' => $task?->id,
            'user_id' => auth()->id(),
            'event' => $event,
            'description' => $description,
            'payload' => $payload,
        ]);
    }

    private function ensureGoogleDriveAvailable(int $tenantId): GoogleDriveService
    {
        $extension = Extension::query()->where('slug', 'google-drive')->first();
        if (!$extension) {
            throw new RuntimeException("L'application Google Drive n'est pas disponible. Installez-la depuis Marketplace.");
        }

        $isActive = TenantExtension::query()
            ->where('tenant_id', $tenantId)
            ->where('extension_id', $extension->id)
            ->whereIn('status', ['active', 'trial'])
            ->exists();

        if (!$isActive) {
            throw new RuntimeException("Google Drive n'est pas installe pour ce tenant. Installez l'application Google Drive depuis Marketplace.");
        }

        $service = app(GoogleDriveService::class);
        $token = $service->getToken($tenantId);
        if (!$token) {
            throw new RuntimeException("Google Drive n'est pas connecte. Ouvrez l'application Google Drive et cliquez sur Connecter.");
        }

        return $service;
    }

    private function ensureProjectDriveFolder(Project $project, GoogleDriveService $drive, int $tenantId): string
    {
        $meta = is_array($project->metadata) ? $project->metadata : [];
        $existing = (string) ($meta['drive_folder_id'] ?? '');
        if ($existing !== '') {
            try {
                $drive->getFile($tenantId, $existing);
                return $existing;
            } catch (\Throwable $e) {
                // continue: folder missing or inaccessible, recreate.
            }
        }

        $token = $drive->getToken($tenantId);
        $rootId = $token?->drive_root_folder_id ?: null;

        $projectsFolder = null;
        if ($rootId) {
            $projectsFolder = GoogleDriveFile::forTenant($tenantId)
                ->where('is_folder', true)
                ->where('name', 'Projets')
                ->where('parent_drive_id', $rootId)
                ->first();
        }

        $projectsFolderId = $projectsFolder?->drive_id;
        if (!$projectsFolderId) {
            $created = $drive->createFolder($tenantId, 'Projets', $rootId);
            $projectsFolderId = (string) ($created['id'] ?? '');
        }

        if ($projectsFolderId === '') {
            throw new RuntimeException('Impossible de creer le dossier Drive pour les projets.');
        }

        $projectFolderName = 'Projet-' . (int) $project->id;
        $projectFolder = GoogleDriveFile::forTenant($tenantId)
            ->where('is_folder', true)
            ->where('name', $projectFolderName)
            ->where('parent_drive_id', $projectsFolderId)
            ->first();

        $projectFolderId = $projectFolder?->drive_id;
        if (!$projectFolderId) {
            $created = $drive->createFolder($tenantId, $projectFolderName, $projectsFolderId);
            $projectFolderId = (string) ($created['id'] ?? '');
        }

        if ($projectFolderId === '') {
            throw new RuntimeException('Impossible de creer le dossier Drive du projet.');
        }

        $meta['drive_folder_id'] = $projectFolderId;
        $project->update(['metadata' => $meta]);

        return $projectFolderId;
    }

    private function ensureTaskDriveFolder(Project $project, ProjectTask $task, GoogleDriveService $drive, int $tenantId): string
    {
        $meta = is_array($task->metadata) ? $task->metadata : [];
        $existing = (string) ($meta['drive_folder_id'] ?? '');
        if ($existing !== '') {
            try {
                $drive->getFile($tenantId, $existing);
                return $existing;
            } catch (\Throwable $e) {
                // continue
            }
        }

        $projectFolderId = $this->ensureProjectDriveFolder($project, $drive, $tenantId);

        $tasksFolder = GoogleDriveFile::forTenant($tenantId)
            ->where('is_folder', true)
            ->where('name', 'Taches')
            ->where('parent_drive_id', $projectFolderId)
            ->first();

        $tasksFolderId = $tasksFolder?->drive_id;
        if (!$tasksFolderId) {
            $created = $drive->createFolder($tenantId, 'Taches', $projectFolderId);
            $tasksFolderId = (string) ($created['id'] ?? '');
        }

        if ($tasksFolderId === '') {
            throw new RuntimeException('Impossible de creer le dossier Drive des taches.');
        }

        $taskFolderName = 'Tache-' . (int) $task->id;
        $taskFolder = GoogleDriveFile::forTenant($tenantId)
            ->where('is_folder', true)
            ->where('name', $taskFolderName)
            ->where('parent_drive_id', $tasksFolderId)
            ->first();

        $taskFolderId = $taskFolder?->drive_id;
        if (!$taskFolderId) {
            $created = $drive->createFolder($tenantId, $taskFolderName, $tasksFolderId);
            $taskFolderId = (string) ($created['id'] ?? '');
        }

        if ($taskFolderId === '') {
            throw new RuntimeException('Impossible de creer le dossier Drive de la tache.');
        }

        $meta['drive_folder_id'] = $taskFolderId;
        $task->update(['metadata' => $meta]);

        return $taskFolderId;
    }
}
