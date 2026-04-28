<?php

namespace Vendor\Automation\Services;

use App\Models\User;
use App\Notifications\AutomationSuggestionPendingNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationSuggestion;
use Vendor\Automation\Support\AutomationReconnectResolver;

class AutomationReconnectNotificationService
{
    public function notifyForProvider(int $tenantId, int $userId, string $providerSlug, string $targetUrl): int
    {
        $user = User::query()
            ->whereKey($userId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$user) {
            return 0;
        }

        $suggestions = $this->pendingSuggestionsForProvider($tenantId, $userId, $providerSlug);
        $activeIds = $suggestions->pluck('id')->map(fn ($id) => (int) $id)->all();
        $notifications = $this->providerNotifications($user, $providerSlug);
        $bySuggestionId = $notifications->keyBy(fn (DatabaseNotification $notification) => (int) data_get($notification->data, 'suggestion_id'));

        foreach ($suggestions as $suggestion) {
            $suggestionId = (int) $suggestion->id;
            $payload = (new AutomationSuggestionPendingNotification(
                $suggestion,
                $providerSlug,
                $this->buildResumeUrl($targetUrl, $suggestionId, $providerSlug)
            ))->toArray($user);

            $notification = $bySuggestionId->get($suggestionId);
            if ($notification) {
                $notification->forceFill([
                    'data' => $payload,
                    'read_at' => null,
                ])->save();

                continue;
            }

            $user->notify(new AutomationSuggestionPendingNotification(
                $suggestion,
                $providerSlug,
                $this->buildResumeUrl($targetUrl, $suggestionId, $providerSlug)
            ));
        }

        $notifications
            ->filter(fn (DatabaseNotification $notification) => !in_array((int) data_get($notification->data, 'suggestion_id'), $activeIds, true))
            ->each(fn (DatabaseNotification $notification) => $notification->delete());

        return count($activeIds);
    }

    public function syncForSuggestion(?AutomationSuggestion $suggestion): void
    {
        if (!$suggestion || !$suggestion->user_id) {
            return;
        }

        $user = $suggestion->relationLoaded('user')
            ? $suggestion->user
            : User::query()->find($suggestion->user_id);

        if (!$user) {
            return;
        }

        $notifications = $this->suggestionNotifications($user, (int) $suggestion->id);
        $providerSlug = $this->resolveSuggestionProvider($suggestion);

        if (!$suggestion->isActionable() || !$providerSlug) {
            $notifications->each(fn (DatabaseNotification $notification) => $notification->delete());
            return;
        }

        $notifications
            ->filter(fn (DatabaseNotification $notification) => (string) data_get($notification->data, 'provider_slug') !== $providerSlug)
            ->each(fn (DatabaseNotification $notification) => $notification->delete());
    }

    protected function pendingSuggestionsForProvider(int $tenantId, int $userId, string $providerSlug): Collection
    {
        return AutomationSuggestion::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('status', AutomationSuggestion::STATUS_PENDING)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderBy('created_at')
            ->get()
            ->filter(fn (AutomationSuggestion $suggestion) => $this->resolveSuggestionProvider($suggestion) === $providerSlug)
            ->values();
    }

    protected function resolveSuggestionProvider(AutomationSuggestion $suggestion): ?string
    {
        $latestFailedEvent = $suggestion->automationEvents()
            ->where('status', AutomationEvent::STATUS_FAILED)
            ->latest('id')
            ->first();

        if (!$latestFailedEvent) {
            return null;
        }

        return AutomationReconnectResolver::resolve($latestFailedEvent->last_error)['slug'] ?? null;
    }

    protected function providerNotifications(User $user, string $providerSlug): Collection
    {
        return $user->notifications()
            ->latest('updated_at')
            ->get()
            ->filter(function (DatabaseNotification $notification) use ($providerSlug) {
                return (string) data_get($notification->data, 'notification_kind') === 'automation_suggestion_pending'
                    && (string) data_get($notification->data, 'provider_slug') === $providerSlug;
            })
            ->values();
    }

    protected function suggestionNotifications(User $user, int $suggestionId): Collection
    {
        return $user->notifications()
            ->latest('updated_at')
            ->get()
            ->filter(function (DatabaseNotification $notification) use ($suggestionId) {
                return (string) data_get($notification->data, 'notification_kind') === 'automation_suggestion_pending'
                    && (int) data_get($notification->data, 'suggestion_id') === $suggestionId;
            })
            ->values();
    }

    protected function buildResumeUrl(string $targetUrl, int $suggestionId, string $providerSlug): string
    {
        $separator = str_contains($targetUrl, '?') ? '&' : '?';

        return $targetUrl . $separator . http_build_query([
            'automation_resume' => 1,
            'automation_suggestion_ids' => (string) $suggestionId,
            'automation_provider' => $providerSlug,
        ]);
    }
}
