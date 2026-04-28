<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Vendor\Automation\Models\AutomationSuggestion;
use Vendor\Automation\Support\AutomationReconnectResolver;

class AutomationSuggestionPendingNotification extends Notification
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected AutomationSuggestion $suggestion,
        protected string $providerSlug,
        protected string $actionUrl,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $providerLabel = AutomationReconnectResolver::providerLabel($this->providerSlug);

        return [
            'notification_kind' => 'automation_suggestion_pending',
            'suggestion_id' => (int) $this->suggestion->id,
            'provider_slug' => $this->providerSlug,
            'provider_label' => $providerLabel,
            'title' => 'Suggestion en attente à reprendre',
            'message' => sprintf(
                '%s est reconnecté. Vous pouvez maintenant reprendre: %s.',
                $providerLabel,
                (string) $this->suggestion->label
            ),
            'action_url' => $this->actionUrl,
            'updated_at' => optional($this->suggestion->updated_at)?->toIso8601String(),
        ];
    }
}
