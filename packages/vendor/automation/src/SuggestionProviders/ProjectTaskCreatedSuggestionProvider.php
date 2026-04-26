<?php

namespace Vendor\Automation\SuggestionProviders;

use Vendor\Automation\Contracts\SuggestionProvider;
use Vendor\Automation\Data\SuggestionDefinition;
use Vendor\Automation\Services\ExtensionAvailabilityService;

class ProjectTaskCreatedSuggestionProvider implements SuggestionProvider
{
    public function __construct(
        protected ExtensionAvailabilityService $extensions
    ) {
    }

    public function suggest(string $sourceEvent, array $context = []): iterable
    {
        $tenantId = (int) ($context['tenant_id'] ?? 0);
        $task = (array) ($context['task'] ?? []);
        $project = (array) ($context['project'] ?? []);
        $meta = (array) ($context['meta'] ?? []);
        $taskId = (int) ($task['id'] ?? 0);
        $projectId = (int) ($project['id'] ?? 0);

        if ($tenantId <= 0 || $taskId <= 0 || $projectId <= 0) {
            return [];
        }

        $taskTitle = (string) ($task['title'] ?? 'cette tache');
        $calendarSynced = (bool) ($task['calendar_synced'] ?? false) || (bool) ($meta['calendar_synced'] ?? false);
        $hasDriveFolder = (bool) ($project['has_drive_folder'] ?? false);
        $hasTeamChannel = (bool) ($project['has_team_channel'] ?? false);

        $suggestions = [];

        if (!$calendarSynced) {
            $calendarInstalled = $this->extensions->isActive($tenantId, 'google-calendar');
            $suggestions[] = SuggestionDefinition::make(
                $calendarInstalled ? 'schedule_project_task_calendar' : 'install_extension',
                $calendarInstalled
                    ? 'Planifier la tache ' . $taskTitle . ' dans Google Calendar'
                    : 'Installer Google Calendar pour planifier les taches du projet',
                0.92,
                $calendarInstalled
                    ? ['project_id' => $projectId, 'task_id' => $taskId]
                    : ['extension_slug' => 'google-calendar', 'project_id' => $projectId, 'task_id' => $taskId, 'target_action' => 'schedule_project_task_calendar'],
                [
                    'integration' => 'google-calendar',
                    'installed' => $calendarInstalled,
                    'target_url' => $this->extensions->targetUrl('google-calendar'),
                ]
            );
        }

        if (!$hasDriveFolder) {
            $driveInstalled = $this->extensions->isActive($tenantId, 'google-drive');
            $suggestions[] = SuggestionDefinition::make(
                $driveInstalled ? 'create_project_drive_folder' : 'install_extension',
                $driveInstalled
                    ? 'Creer le dossier Google Drive du projet pour cette tache'
                    : 'Installer Google Drive pour centraliser les fichiers du projet',
                0.82,
                $driveInstalled
                    ? ['project_id' => $projectId, 'task_id' => $taskId]
                    : ['extension_slug' => 'google-drive', 'project_id' => $projectId, 'task_id' => $taskId, 'target_action' => 'create_project_drive_folder'],
                [
                    'integration' => 'google-drive',
                    'installed' => $driveInstalled,
                    'target_url' => $this->extensions->targetUrl('google-drive'),
                ]
            );
        }

        if (!$hasTeamChannel) {
            $installedChannel = $this->extensions->preferredInstalled($tenantId, ['chatbot', 'slack']);
            $channelInstalled = $installedChannel !== null;
            $channelSlug = $installedChannel ?: 'chatbot';

            $suggestions[] = SuggestionDefinition::make(
                $channelInstalled ? 'create_project_channel' : 'install_extension',
                $channelInstalled
                    ? 'Creer un canal d equipe pour coordonner cette tache'
                    : 'Installer Chatbot ou Slack pour discuter autour des taches du projet',
                0.73,
                $channelInstalled
                    ? ['project_id' => $projectId, 'task_id' => $taskId, 'extension_slug' => $channelSlug]
                    : ['extension_slug' => 'chatbot', 'project_id' => $projectId, 'task_id' => $taskId, 'target_action' => 'create_project_channel'],
                [
                    'integration' => $channelSlug,
                    'installed' => $channelInstalled,
                    'target_url' => $this->extensions->targetUrl($channelSlug),
                ]
            );
        }

        return $suggestions;
    }
}
