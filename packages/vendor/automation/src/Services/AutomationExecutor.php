<?php

namespace Vendor\Automation\Services;

use RuntimeException;
use Throwable;
use Vendor\Automation\Events\AutomationEventFailed;
use Vendor\Automation\Events\AutomationEventProcessed;
use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationLog;
use Vendor\Automation\Models\AutomationSuggestion;
use Vendor\Automation\Registries\ActionRegistry;

class AutomationExecutor
{
    public function __construct(
        protected ActionRegistry $actionRegistry
    ) {
    }

    public function execute(AutomationEvent $automationEvent): AutomationEvent
    {
        if (in_array($automationEvent->status, [
            AutomationEvent::STATUS_COMPLETED,
            AutomationEvent::STATUS_SKIPPED,
        ], true)) {
            return $automationEvent;
        }

        $this->assertTenantScope((int) $automationEvent->tenant_id);

        $automationEvent->forceFill([
            'status' => AutomationEvent::STATUS_PROCESSING,
            'attempts' => (int) $automationEvent->attempts + 1,
        ])->save();

        $this->writeLog(
            $automationEvent,
            level: 'info',
            status: AutomationEvent::STATUS_PROCESSING,
            message: 'Exécution automation démarrée.',
        );

        $action = $this->actionRegistry->resolve((string) $automationEvent->action_type);
        if (!$action) {
            return $this->markFailed(
                $automationEvent,
                "Aucune action enregistrée pour le type [{$automationEvent->action_type}]."
            );
        }

        try {
            $response = $action->execute($automationEvent, $automationEvent->suggestion);

            $automationEvent->forceFill([
                'status' => AutomationEvent::STATUS_COMPLETED,
                'response' => $response,
                'processed_at' => now(),
                'failed_at' => null,
                'last_error' => null,
            ])->save();

            $this->writeLog(
                $automationEvent,
                level: 'info',
                status: AutomationEvent::STATUS_COMPLETED,
                message: 'Exécution automation terminée.',
                response: $response
            );

            event(new AutomationEventProcessed($automationEvent->fresh()));

            return $automationEvent->fresh();
        } catch (Throwable $e) {
            return $this->markFailed($automationEvent, $e->getMessage(), [
                'exception' => get_class($e),
            ]);
        }
    }

    protected function markFailed(AutomationEvent $automationEvent, string $message, array $context = []): AutomationEvent
    {
        $automationEvent->forceFill([
            'status' => AutomationEvent::STATUS_FAILED,
            'last_error' => $message,
            'failed_at' => now(),
        ])->save();

        $this->writeLog(
            $automationEvent,
            level: 'error',
            status: AutomationEvent::STATUS_FAILED,
            message: $message,
            context: $context
        );

        event(new AutomationEventFailed($automationEvent->fresh(), $message));

        return $automationEvent->fresh();
    }

    protected function writeLog(
        AutomationEvent $automationEvent,
        string $level,
        string $status,
        string $message,
        array $response = [],
        array $context = []
    ): void {
        $modelClass = config('automation.models.log', AutomationLog::class);

        $modelClass::query()->create([
            'tenant_id' => (int) $automationEvent->tenant_id,
            'user_id' => $automationEvent->user_id ? (int) $automationEvent->user_id : null,
            'automation_event_id' => (int) $automationEvent->id,
            'automation_suggestion_id' => $automationEvent->triggered_by_suggestion_id
                ? (int) $automationEvent->triggered_by_suggestion_id
                : null,
            'event_name' => (string) $automationEvent->event_name,
            'action_type' => (string) $automationEvent->action_type,
            'level' => $level,
            'status' => $status,
            'message' => $message,
            'response' => $response ?: null,
            'context' => $context ?: null,
        ]);
    }

    protected function assertTenantScope(int $tenantId): void
    {
        if (auth()->check() && (int) auth()->user()->tenant_id !== $tenantId) {
            throw new RuntimeException('Accès interdit à cette automation pour un autre tenant.');
        }
    }
}
