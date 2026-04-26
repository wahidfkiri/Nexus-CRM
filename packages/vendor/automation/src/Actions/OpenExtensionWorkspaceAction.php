<?php

namespace Vendor\Automation\Actions;

use RuntimeException;
use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationSuggestion;

class OpenExtensionWorkspaceAction extends AbstractAutomationAction
{
    public function execute(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion = null): array
    {
        $payload = $this->payload($automationEvent);
        $meta = $this->meta($suggestion);

        $extensionSlug = trim((string) ($payload['extension_slug'] ?? $meta['integration'] ?? ''));
        $targetUrl = trim((string) ($payload['target_url'] ?? $meta['target_url'] ?? ''));

        if ($targetUrl === '' && $extensionSlug !== '') {
            $targetUrl = $this->extensions->targetUrl($extensionSlug);
        }

        if ($targetUrl === '') {
            throw new RuntimeException('Aucune destination n est disponible pour cette suggestion.');
        }

        return [
            'result' => 'workspace_ready',
            'message' => trim((string) ($payload['message'] ?? 'Raccourci d application enregistre.')),
            'extension_slug' => $extensionSlug !== '' ? $extensionSlug : null,
            'target_url' => $targetUrl,
        ];
    }
}
