<?php

namespace Vendor\Automation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;
use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationSuggestion;
use Vendor\Automation\Services\AutomationEngine;
use Vendor\Automation\Services\AutomationReconnectNotificationService;
use Vendor\Automation\Services\AutomationSuggestionPresenter;
use Vendor\Automation\Support\AutomationReconnectResolver;
use Vendor\Automation\Support\AutomationTenantResolver;

class AutomationSuggestionController extends Controller
{
    public function __construct(
        protected AutomationEngine $engine,
        protected AutomationSuggestionPresenter $presenter,
        protected AutomationReconnectNotificationService $reconnectNotifications,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        if (is_string($request->input('ids'))) {
            $request->merge([
                'ids' => collect(explode(',', (string) $request->input('ids')))
                    ->map(fn ($id) => (int) trim($id))
                    ->filter()
                    ->values()
                    ->all(),
            ]);
        }

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
            $eventPayload = $this->presentEvent($freshEvent);
            $this->reconnectNotifications->syncForSuggestion($freshSuggestion);

            if ((string) $freshEvent->status === AutomationEvent::STATUS_FAILED) {
                $failureMessage = $this->safeStoredErrorMessage($freshEvent->last_error);

                return response()->json([
                    'success' => false,
                    'message' => $failureMessage,
                    'data' => [
                        'suggestions' => $this->presenter->presentCollection(collect([$freshSuggestion])),
                        'event' => array_merge($eventPayload, ['last_error' => $failureMessage]),
                    ],
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => (string) $freshEvent->status === AutomationEvent::STATUS_COMPLETED
                    ? 'Suggestion acceptee et traitee.'
                    : 'Suggestion acceptee. Automation en cours.',
                'data' => [
                    'suggestions' => $this->presenter->presentCollection(collect([$freshSuggestion])),
                    'event' => $eventPayload,
                ],
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse($e);
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
            $this->reconnectNotifications->syncForSuggestion($result->fresh());

            return response()->json([
                'success' => true,
                'message' => 'Suggestion ignoree.',
                'data' => [
                    'suggestions' => $this->presenter->presentCollection(collect([$result->fresh()])),
                ],
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function bulkAccept(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        [$processed, $errors] = $this->processSuggestions($validated['ids'], function (AutomationSuggestion $suggestion) {
            $event = $this->engine->accept($suggestion, auth()->id())->fresh();
            $freshSuggestion = $suggestion->fresh();

            if ((string) $event->status === AutomationEvent::STATUS_FAILED) {
                $failureMessage = $this->safeStoredErrorMessage($event->last_error);

                return [
                    'suggestion' => $freshSuggestion,
                    'error' => [
                        'id' => (int) $freshSuggestion->id,
                        'message' => $failureMessage,
                        'event' => array_merge(
                            $this->presentEvent($event),
                            ['last_error' => $failureMessage]
                        ),
                    ],
                ];
            }

            return [
                'suggestion' => $freshSuggestion,
                'error' => null,
            ];
        });

        $acceptedCount = collect($processed)
            ->filter(fn ($item) => $item instanceof AutomationSuggestion && (string) $item->status === AutomationSuggestion::STATUS_ACCEPTED)
            ->count();

        collect($processed)
            ->filter(fn ($item) => $item instanceof AutomationSuggestion)
            ->each(fn (AutomationSuggestion $suggestion) => $this->reconnectNotifications->syncForSuggestion($suggestion));

        if ($acceptedCount === 0) {
            return response()->json([
                'success' => false,
                'message' => $errors[0]['message'] ?? "Aucune suggestion n'a pu etre acceptee.",
                'data' => [
                    'suggestions' => $this->presenter->presentCollection(collect($processed)),
                    'errors' => $errors,
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $acceptedCount . ' suggestion(s) acceptee(s).',
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

        collect($processed)
            ->filter(fn ($item) => $item instanceof AutomationSuggestion)
            ->each(fn (AutomationSuggestion $suggestion) => $this->reconnectNotifications->syncForSuggestion($suggestion));

        if (empty($processed)) {
            return response()->json([
                'success' => false,
                'message' => $errors[0]['message'] ?? "Aucune suggestion n'a pu etre ignoree.",
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
                $result = $callback($suggestion);

                if (is_array($result) && array_key_exists('suggestion', $result)) {
                    if ($result['suggestion']) {
                        $processed[] = $result['suggestion'];
                    }

                    if (!empty($result['error'])) {
                        $errors[] = $result['error'];
                    }

                    continue;
                }

                $processed[] = $result;
            } catch (Throwable $e) {
                $errors[] = [
                    'id' => (int) $suggestion->id,
                    'message' => $this->safeExceptionMessage($e),
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
        return AutomationTenantResolver::resolve();
    }

    protected function presentEvent(AutomationEvent $event): array
    {
        $targetUrl = is_array($event->response) ? ($event->response['target_url'] ?? null) : null;
        $targetBlank = is_array($event->response) ? (bool) ($event->response['target_blank'] ?? false) : false;
        $provider = AutomationReconnectResolver::resolve($event->last_error);
        $requiresReconnect = $provider !== null;

        return [
            'id' => (int) $event->id,
            'status' => (string) $event->status,
            'action_type' => (string) $event->action_type,
            'last_error' => $this->safeStoredErrorMessage($event->last_error),
            'response' => is_array($event->response) ? $event->response : null,
            'target_url' => $targetUrl,
            'target_blank' => $targetBlank,
            'requires_reconnect' => $requiresReconnect,
            'reconnect_provider' => $provider['slug'] ?? null,
            'reconnect_url' => $provider['url'] ?? null,
            'reconnect_label' => $requiresReconnect
                ? 'Reconnecter ' . ($provider['label'] ?? 'le service')
                : null,
        ];
    }

    protected function errorResponse(Throwable $e): JsonResponse
    {
        if ($e instanceof HttpExceptionInterface) {
            throw $e;
        }

        if ($e instanceof ModelNotFoundException) {
            abort(404);
        }

        if ($e instanceof RuntimeException) {
            return response()->json([
                'success' => false,
                'message' => $this->safeExceptionMessage($e),
            ], 422);
        }

        report($e);

        return response()->json([
            'success' => false,
            'message' => "Une erreur inattendue est survenue pendant le traitement de l'automation.",
        ], 500);
    }

    protected function safeExceptionMessage(Throwable $e): string
    {
        if (!$e instanceof RuntimeException) {
            report($e);

            return "Une erreur inattendue est survenue pendant le traitement de l'automation.";
        }

        return $this->sanitizeMessage($e->getMessage());
    }

    protected function safeStoredErrorMessage(?string $message): string
    {
        return $this->sanitizeMessage($message, 'Cette automation a echoue.');
    }

    protected function sanitizeMessage(?string $message, string $fallback = "Une erreur inattendue est survenue pendant le traitement de l'automation."): string
    {
        $message = trim((string) $message);
        if ($message === '') {
            return $fallback;
        }

        $normalized = mb_strtolower($message);
        $unsafeFragments = [
            'sqlstate',
            'syntax error',
            'stack trace',
            '.php',
            'vendor\\',
            'vendor/',
            'd:\\',
            'c:\\',
            'call to undefined',
            'typeerror',
            'queryexception',
            'failed to open stream',
            'on line ',
        ];

        foreach ($unsafeFragments as $fragment) {
            if (str_contains($normalized, $fragment)) {
                return $fallback;
            }
        }

        return $message;
    }
}
