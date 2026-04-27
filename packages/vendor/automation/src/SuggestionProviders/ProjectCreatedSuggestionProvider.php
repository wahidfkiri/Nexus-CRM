<?php

namespace Vendor\Automation\SuggestionProviders;

use Vendor\Automation\Contracts\SuggestionProvider;
use Vendor\Automation\Data\SuggestionDefinition;
use Vendor\Automation\Services\ExtensionAvailabilityService;

class ProjectCreatedSuggestionProvider implements SuggestionProvider
{
    public function __construct(
        protected ExtensionAvailabilityService $extensions
    ) {
    }

    public function suggest(string $sourceEvent, array $context = []): iterable
    {
        $tenantId = (int) ($context['tenant_id'] ?? 0);
        $project = (array) ($context['project'] ?? []);
        $projectId = (int) ($project['id'] ?? 0);

        if ($tenantId <= 0 || $projectId <= 0) {
            return [];
        }

        $projectName = (string) ($project['name'] ?? 'ce projet');
        $meta = (array) ($context['meta'] ?? []);
        $calendarAlreadySynced = (bool) ($meta['calendar_synced'] ?? false);

        $suggestions = [];

        if (!$calendarAlreadySynced) {
            $calendarInstalled = $this->extensions->isActive($tenantId, 'google-calendar');
            $suggestions[] = SuggestionDefinition::make(
                $calendarInstalled ? 'schedule_project_kickoff' : 'install_extension',
                $calendarInstalled
                    ? "Planifier le kickoff du projet {$projectName}"
                    : 'Installer Google Calendar pour planifier un kickoff',
                0.9,
                $calendarInstalled
                    ? ['project_id' => $projectId]
                    : ['extension_slug' => 'google-calendar', 'project_id' => $projectId, 'target_action' => 'schedule_project_kickoff'],
                [
                    'integration' => 'google-calendar',
                    'installed' => $calendarInstalled,
                    'target_url' => $this->extensions->targetUrl('google-calendar'),
                ]
            );
        }

        $preferredStorage = $this->extensions->preferredInstalled($tenantId, ['google-drive', 'dropbox']);
        $storageInstalled = $preferredStorage !== null;
        $storageSlug = $preferredStorage ?: 'dropbox';
        $storageAction = $storageSlug === 'dropbox' ? 'create_project_dropbox_folder' : 'create_project_drive_folder';

        $suggestions[] = SuggestionDefinition::make(
            $storageInstalled ? $storageAction : 'install_extension',
            $storageInstalled
                ? ($storageSlug === 'dropbox'
                    ? "Creer un dossier Dropbox pour {$projectName}"
                    : "Creer un dossier Google Drive pour {$projectName}")
                : 'Installer Dropbox ou Google Drive pour centraliser les fichiers du projet',
            0.87,
            $storageInstalled
                ? ['project_id' => $projectId]
                : ['extension_slug' => 'dropbox', 'project_id' => $projectId, 'target_action' => 'create_project_dropbox_folder'],
            [
                'integration' => $storageSlug,
                'installed' => $storageInstalled,
                'target_url' => $this->extensions->targetUrl($storageSlug),
            ]
        );

        $preferredChannelExtension = $this->extensions->preferredInstalled($tenantId, ['chatbot', 'slack']);
        $channelInstalled = $preferredChannelExtension !== null;
        $channelSlug = $preferredChannelExtension ?: 'chatbot';
        $suggestions[] = SuggestionDefinition::make(
            $channelInstalled ? 'create_project_channel' : 'install_extension',
            $channelInstalled
                ? "Creer un canal d equipe pour {$projectName}"
                : 'Installer Chatbot ou Slack pour ouvrir un canal projet',
            0.76,
            $channelInstalled
                ? ['project_id' => $projectId, 'extension_slug' => $channelSlug]
                : ['extension_slug' => 'chatbot', 'project_id' => $projectId, 'target_action' => 'create_project_channel'],
            [
                'integration' => $channelSlug,
                'installed' => $channelInstalled,
                'target_url' => $this->extensions->targetUrl($channelSlug),
            ]
        );

        return $suggestions;
    }
}
