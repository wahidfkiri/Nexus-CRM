<?php

namespace Vendor\Automation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use Vendor\Automation\Models\AutomationSuggestion;
use Vendor\Automation\Services\AutomationEngine;
use Vendor\Automation\Services\AutomationSuggestionPresenter;

class AutomationSuggestionController extends Controller
{
    public function __construct(
        protected AutomationEngine $engine,
        protected AutomationSuggestionPresenter $presenter
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer'],
            'source_event' => ['nullable', 'string', 'max:120'],
            'source_type' => ['nullable', 'string', 'max:255'],
            'source_id' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:pending,accepted,rejected,expired,all'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $suggestions = $this->presenter->fetch($validated, $this->tenantId());

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $suggestions->count(),
                'suggestions' => $this->presenter->presentCollection($suggestions),
            ],
        ]);
    }

    public function accept(AutomationSuggestion $suggestion): JsonResponse
    {
        $this->guardSuggestion($suggestion);

        try {
            $event = $this->engine->accept($suggestion, auth()->id());
            $freshSuggestion = $suggestion->fresh();
            $freshEvent = $event->fresh();

            return response()->json([
                'success' => true,
                'message' => 'Suggestion acceptee et traitee.',
                'data' => [
                    'suggestions' => $this->presenter->presentCollection(collect([$freshSuggestion])),
                    'event' => [
                        'id' => (int) $freshEvent->id,
                        'status' => (string) $freshEvent->status,
                        'action_type' => (string) $freshEvent->action_type,
                        'last_error' => $freshEvent->last_error,
                        'response' => is_array($freshEvent->response) ? $freshEvent->response : null,
                        'target_url' => is_array($freshEvent->response) ? ($freshEvent->response['target_url'] ?? null) : null,
                    ],
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function reject(Request $request, AutomationSuggestion $suggestion): JsonResponse
    {
        $this->guardSuggestion($suggestion);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $result = $this->engine->reject($suggestion, auth()->id(), $validated['reason'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Suggestion ignoree.',
                'data' => [
                    'suggestions' => $this->presenter->presentCollection(collect([$result->fresh()])),
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function bulkAccept(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        [$processed, $errors] = $this->processSuggestions($validated['ids'], function (AutomationSuggestion $suggestion) {
            $this->engine->accept($suggestion, auth()->id());
            return $suggestion->fresh();
        });

        if (empty($processed)) {
            return response()->json([
                'success' => false,
                'message' => $errors[0]['message'] ?? 'Aucune suggestion n a pu etre acceptee.',
                'data' => ['errors' => $errors],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => count($processed) . ' suggestion(s) acceptee(s).',
            'data' => [
                'suggestions' => $this->presenter->presentCollection(collect($processed)),
                'errors' => $errors,
            ],
        ]);
    }

    public function bulkReject(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        [$processed, $errors] = $this->processSuggestions($validated['ids'], function (AutomationSuggestion $suggestion) use ($validated) {
            return $this->engine->reject($suggestion, auth()->id(), $validated['reason'] ?? null)->fresh();
        });

        if (empty($processed)) {
            return response()->json([
                'success' => false,
                'message' => $errors[0]['message'] ?? 'Aucune suggestion n a pu etre ignoree.',
                'data' => ['errors' => $errors],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => count($processed) . ' suggestion(s) ignoree(s).',
            'data' => [
                'suggestions' => $this->presenter->presentCollection(collect($processed)),
                'errors' => $errors,
            ],
        ]);
    }

    protected function processSuggestions(array $ids, callable $callback): array
    {
        $suggestions = AutomationSuggestion::query()
            ->where('tenant_id', $this->tenantId())
            ->whereIn('id', collect($ids)->map(fn ($id) => (int) $id)->all())
            ->get();

        $processed = [];
        $errors = [];

        foreach ($suggestions as $suggestion) {
            try {
                $processed[] = $callback($suggestion);
            } catch (Throwable $e) {
                $errors[] = [
                    'id' => (int) $suggestion->id,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [$processed, $errors];
    }

    protected function guardSuggestion(AutomationSuggestion $suggestion): void
    {
        abort_if((int) $suggestion->tenant_id !== $this->tenantId(), 404);
    }

    protected function tenantId(): int
    {
        return (int) (auth()->user()->tenant_id ?? 0);
    }
}
