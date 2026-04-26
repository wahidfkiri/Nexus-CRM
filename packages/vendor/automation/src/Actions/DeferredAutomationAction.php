<?php

namespace Vendor\Automation\Actions;

use Vendor\Automation\Contracts\AutomationAction;
use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationSuggestion;

class DeferredAutomationAction implements AutomationAction
{
    public function execute(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion = null): array
    {
        return [
            'result' => 'recorded',
            'message' => 'Suggestion enregistree. L action detaillee sera branchee lors de la phase 4.',
            'action_type' => (string) $automationEvent->action_type,
            'suggestion_id' => $suggestion?->id,
            'target_url' => $suggestion?->meta['target_url'] ?? null,
        ];
    }
}
