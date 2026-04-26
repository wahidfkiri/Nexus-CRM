<?php

namespace Vendor\Automation\SuggestionProviders;

use Vendor\Automation\Contracts\SuggestionProvider;
use Vendor\Automation\Data\SuggestionDefinition;
use Vendor\Automation\Services\ExtensionAvailabilityService;

class ExtensionActivatedSuggestionProvider implements SuggestionProvider
{
    public function __construct(
        protected ExtensionAvailabilityService $extensions
    ) {
    }

    public function suggest(string $sourceEvent, array $context = []): iterable
    {
        $tenantId = (int) ($context['tenant_id'] ?? 0);
        $activation = (array) ($context['extension_activation'] ?? []);
        $activationId = (int) ($activation['id'] ?? 0);
        $slug = trim((string) ($activation['extension_slug'] ?? ''));
        $name = trim((string) ($activation['extension_name'] ?? ucfirst(str_replace('-', ' ', $slug))));

        if ($tenantId <= 0 || $activationId <= 0 || $slug === '') {
            return [];
        }

        $suggestions = [
            SuggestionDefinition::make(
                'open_extension_workspace',
                $this->primaryWorkspaceLabel($slug, $name),
                0.98,
                [
                    'activation_id' => $activationId,
                    'extension_slug' => $slug,
                    'target_url' => $this->extensions->targetUrl($slug),
                    'message' => 'Raccourci vers ' . $name . ' enregistre.',
                ],
                [
                    'integration' => $slug,
                    'installed' => true,
                    'target_url' => $this->extensions->targetUrl($slug),
                    'primary_label' => 'Marquer traite',
                ]
            ),
        ];

        if ($slug === 'projects') {
            $calendarInstalled = $this->extensions->isActive($tenantId, 'google-calendar');
            $driveInstalled = $this->extensions->isActive($tenantId, 'google-drive');
            $chatInstalled = $this->extensions->preferredInstalled($tenantId, ['chatbot', 'slack']) !== null;

            if (!$calendarInstalled) {
                $suggestions[] = SuggestionDefinition::make(
                    'install_extension',
                    'Installer Google Calendar pour planifier projets et taches',
                    0.89,
                    ['extension_slug' => 'google-calendar', 'activation_id' => $activationId, 'target_action' => 'schedule_project_kickoff'],
                    [
                        'integration' => 'google-calendar',
                        'installed' => false,
                        'target_url' => $this->extensions->targetUrl('google-calendar'),
                    ]
                );
            }

            if (!$driveInstalled) {
                $suggestions[] = SuggestionDefinition::make(
                    'install_extension',
                    'Installer Google Drive pour centraliser les fichiers projet',
                    0.82,
                    ['extension_slug' => 'google-drive', 'activation_id' => $activationId, 'target_action' => 'create_project_drive_folder'],
                    [
                        'integration' => 'google-drive',
                        'installed' => false,
                        'target_url' => $this->extensions->targetUrl('google-drive'),
                    ]
                );
            }

            if (!$chatInstalled) {
                $suggestions[] = SuggestionDefinition::make(
                    'install_extension',
                    'Installer Chatbot ou Slack pour ouvrir des canaux projet',
                    0.72,
                    ['extension_slug' => 'chatbot', 'activation_id' => $activationId, 'target_action' => 'create_project_channel'],
                    [
                        'integration' => 'chatbot',
                        'installed' => false,
                        'target_url' => $this->extensions->targetUrl('chatbot'),
                    ]
                );
            }
        }

        if ($slug === 'invoice') {
            $gmailInstalled = $this->extensions->isActive($tenantId, 'google-gmail');
            if (!$gmailInstalled) {
                $suggestions[] = SuggestionDefinition::make(
                    'install_extension',
                    'Installer Google Gmail pour envoyer vos devis et factures',
                    0.9,
                    ['extension_slug' => 'google-gmail', 'activation_id' => $activationId, 'target_action' => 'send_invoice_email'],
                    [
                        'integration' => 'google-gmail',
                        'installed' => false,
                        'target_url' => $this->extensions->targetUrl('google-gmail'),
                    ]
                );
            }
        }

        if ($slug === 'google-calendar' && $this->extensions->isActive($tenantId, 'projects')) {
            $suggestions[] = SuggestionDefinition::make(
                'open_extension_workspace',
                'Ouvrir Projets pour utiliser Google Calendar sur vos projets et taches',
                0.86,
                [
                    'activation_id' => $activationId,
                    'extension_slug' => 'projects',
                    'target_url' => $this->extensions->targetUrl('projects'),
                    'message' => 'Raccourci Projets enregistre apres activation de Google Calendar.',
                ],
                [
                    'integration' => 'projects',
                    'installed' => true,
                    'target_url' => $this->extensions->targetUrl('projects'),
                    'primary_label' => 'Marquer traite',
                ]
            );
        }

        if ($slug === 'google-drive' && $this->extensions->isActive($tenantId, 'projects')) {
            $suggestions[] = SuggestionDefinition::make(
                'open_extension_workspace',
                'Ouvrir Projets pour stocker vos fichiers dans Google Drive',
                0.84,
                [
                    'activation_id' => $activationId,
                    'extension_slug' => 'projects',
                    'target_url' => $this->extensions->targetUrl('projects'),
                    'message' => 'Raccourci Projets enregistre apres activation de Google Drive.',
                ],
                [
                    'integration' => 'projects',
                    'installed' => true,
                    'target_url' => $this->extensions->targetUrl('projects'),
                    'primary_label' => 'Marquer traite',
                ]
            );
        }

        if ($slug === 'google-gmail' && $this->extensions->isActive($tenantId, 'invoice')) {
            $suggestions[] = SuggestionDefinition::make(
                'open_extension_workspace',
                'Ouvrir Facturation pour utiliser Gmail sur les devis et factures',
                0.87,
                [
                    'activation_id' => $activationId,
                    'extension_slug' => 'invoice',
                    'target_url' => $this->extensions->targetUrl('invoice'),
                    'message' => 'Raccourci Facturation enregistre apres activation de Gmail.',
                ],
                [
                    'integration' => 'invoice',
                    'installed' => true,
                    'target_url' => $this->extensions->targetUrl('invoice'),
                    'primary_label' => 'Marquer traite',
                ]
            );
        }

        return $suggestions;
    }

    protected function primaryWorkspaceLabel(string $slug, string $name): string
    {
        return match ($slug) {
            'google-calendar', 'google-drive', 'google-gmail', 'google-meet', 'google-sheets', 'google-docx', 'slack' => 'Ouvrir ' . $name . ' pour finaliser la connexion',
            'projects' => 'Ouvrir Projets pour creer votre premier espace de travail',
            'invoice' => 'Ouvrir Facturation pour configurer vos documents',
            default => 'Ouvrir ' . $name . ' pour terminer sa configuration',
        };
    }
}
