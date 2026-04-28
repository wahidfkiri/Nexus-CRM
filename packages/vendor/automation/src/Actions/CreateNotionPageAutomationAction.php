<?php

namespace Vendor\Automation\Actions;

use NexusExtensions\NotionWorkspace\Models\NotionPageLink;
use NexusExtensions\NotionWorkspace\Services\NotionWorkspaceApiService;
use NexusExtensions\Projects\Models\Project;
use NexusExtensions\Projects\Models\ProjectTask;
use RuntimeException;
use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationSuggestion;
use Vendor\Client\Models\Client;
use Vendor\Invoice\Models\Invoice;
use Vendor\Invoice\Models\Quote;

class CreateNotionPageAutomationAction extends AbstractAutomationAction
{
    public function __construct(
        \Vendor\Automation\Services\ExtensionAvailabilityService $extensions,
        protected NotionWorkspaceApiService $notionService
    ) {
        parent::__construct($extensions);
    }

    public function execute(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion = null): array
    {
        return $this->withReconnectHandling('notion-workspace', function () use ($automationEvent, $suggestion) {
            $tenantId = $this->tenantId($automationEvent);
            $this->assertExtensionActive($tenantId, 'notion-workspace', "Notion Workspace doit être installé pour cette automation.");

            if (!$this->notionService->getToken($tenantId)) {
                throw new RuntimeException("Notion Workspace n'est pas connecté pour ce tenant.");
            }

            $draft = $this->buildDraft($automationEvent, $suggestion);
            $page = $this->notionService->createPage($tenantId, [
                'title' => $draft['title'],
                'content' => $draft['content'],
                'icon' => $draft['icon'],
                'parent_page_id' => $draft['parent_page_id'],
            ]);

            $actor = $this->resolveActorUser($automationEvent);
            $link = NotionPageLink::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'notion_page_id' => (string) $page['id'],
                ],
                [
                    'notion_parent_id' => $page['parent']['page_id'] ?? null,
                    'notion_page_title' => (string) ($page['title'] ?? $draft['title']),
                    'notion_page_url' => (string) ($page['url'] ?? ''),
                    'client_id' => $draft['client_id'],
                    'project_id' => $draft['project_id'],
                    'context_label' => $draft['context_label'],
                    'notes' => $draft['notes'],
                    'linked_by' => (int) $actor->id,
                    'last_synced_at' => now(),
                ]
            );

            return [
                'result' => 'notion_page_created',
                'message' => $draft['success_message'],
                'notion_page_id' => (string) $page['id'],
                'notion_page_title' => (string) ($page['title'] ?? $draft['title']),
                'link_id' => (int) $link->id,
                'client_id' => $draft['client_id'],
                'project_id' => $draft['project_id'],
                'target_url' => (string) ($page['url'] ?? '') ?: $this->routeUrl('notion-workspace.index'),
                'target_blank' => true,
            ];
        });
    }

    protected function buildDraft(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion = null): array
    {
        $payload = $this->payload($automationEvent);
        $meta = $this->meta($suggestion);
        $tenantId = $this->tenantId($automationEvent);

        $template = trim((string) ($payload['template'] ?? $meta['template'] ?? 'generic'));
        $customTitle = $this->sanitizeText((string) ($payload['title'] ?? ''));
        $customContent = trim((string) ($payload['content'] ?? ''));

        if ($customTitle !== '') {
            return [
                'title' => $customTitle,
                'content' => $customContent !== '' ? $customContent : 'Page créée automatiquement depuis une suggestion CRM.',
                'icon' => '',
                'parent_page_id' => $payload['parent_page_id'] ?? null,
                'client_id' => $payload['client_id'] ?? null,
                'project_id' => $payload['project_id'] ?? null,
                'context_label' => $this->sanitizeText((string) ($payload['context_label'] ?? 'Documentation')),
                'notes' => $this->sanitizeText((string) ($payload['notes'] ?? 'Page Notion créée automatiquement depuis le moteur d automation.')),
                'success_message' => trim((string) ($payload['success_message'] ?? 'Page Notion créée avec succès.')),
            ];
        }

        return match ($template) {
            'client_notes' => $this->clientDraft($tenantId, $payload, $suggestion),
            'project_brief' => $this->projectDraft($tenantId, $payload, $suggestion),
            'task_spec' => $this->taskDraft($tenantId, $payload, $suggestion),
            'quote_followup' => $this->quoteDraft($tenantId, $payload, $suggestion),
            'invoice_followup' => $this->invoiceDraft($tenantId, $payload, $suggestion),
            default => [
                'title' => 'Page Notion CRM',
                'content' => 'Page créée automatiquement depuis une suggestion CRM.',
                'icon' => '',
                'parent_page_id' => $payload['parent_page_id'] ?? null,
                'client_id' => $payload['client_id'] ?? null,
                'project_id' => $payload['project_id'] ?? null,
                'context_label' => 'Documentation',
                'notes' => 'Page Notion créée automatiquement depuis le moteur d automation.',
                'success_message' => 'Page Notion créée avec succès.',
            ],
        };
    }

    protected function clientDraft(int $tenantId, array $payload, ?AutomationSuggestion $suggestion): array
    {
        $clientId = $this->modelId($payload, $suggestion, 'client_id', Client::class);
        if (!$clientId) {
            throw new RuntimeException('Client introuvable pour la création de la page Notion.');
        }

        $client = $this->loadClient($tenantId, $clientId);
        $clientName = $this->clientDisplayName($client);

        return [
            'title' => 'Client - ' . $clientName . ' - Notes internes',
            'content' => implode(PHP_EOL, array_filter([
                'Type de page: Notes client',
                'Client: ' . $clientName,
                $client->email ? 'Email: ' . $client->email : null,
                $client->phone ? 'Téléphone: ' . $client->phone : null,
                $client->website ? 'Site web: ' . $client->website : null,
                '',
                'A documenter:',
                '- Contexte commercial',
                '- Besoins et priorités',
                '- Risques et objections',
                '- Prochaines actions',
            ])),
            'icon' => '',
            'parent_page_id' => $payload['parent_page_id'] ?? null,
            'client_id' => (int) $client->id,
            'project_id' => null,
            'context_label' => $this->sanitizeText((string) ($payload['context_label'] ?? 'Notes client')),
            'notes' => 'Page Notion créée automatiquement après création du client.',
            'success_message' => 'Page Notion client créée avec succès.',
        ];
    }

    protected function projectDraft(int $tenantId, array $payload, ?AutomationSuggestion $suggestion): array
    {
        $projectId = $this->modelId($payload, $suggestion, 'project_id', Project::class);
        if (!$projectId) {
            throw new RuntimeException('Projet introuvable pour la création de la page Notion.');
        }

        $project = $this->loadProject($tenantId, $projectId);
        $clientName = $project->client ? $this->clientDisplayName($project->client) : null;

        return [
            'title' => 'Projet - ' . $this->sanitizeText((string) $project->name) . ' - Brief',
            'content' => implode(PHP_EOL, array_filter([
                'Type de page: Brief projet',
                'Projet: ' . $this->sanitizeText((string) $project->name),
                $clientName ? 'Client: ' . $clientName : null,
                $project->status ? 'Statut: ' . $this->sanitizeText((string) $project->status) : null,
                $project->description ? 'Description: ' . $this->sanitizeText((string) $project->description) : null,
                '',
                'A documenter:',
                '- Objectifs du projet',
                '- Parties prenantes',
                '- Livrables attendus',
                '- Risques et dépendances',
                "- Plan d'exécution",
            ])),
            'icon' => '',
            'parent_page_id' => $payload['parent_page_id'] ?? null,
            'client_id' => $project->client_id ? (int) $project->client_id : null,
            'project_id' => (int) $project->id,
            'context_label' => $this->sanitizeText((string) ($payload['context_label'] ?? 'Brief projet')),
            'notes' => 'Page Notion créée automatiquement après création du projet.',
            'success_message' => 'Page Notion projet créée avec succès.',
        ];
    }

    protected function taskDraft(int $tenantId, array $payload, ?AutomationSuggestion $suggestion): array
    {
        $taskId = $this->modelId($payload, $suggestion, 'task_id', ProjectTask::class);
        if (!$taskId) {
            throw new RuntimeException('Tâche introuvable pour la création de la page Notion.');
        }

        $task = $this->loadProjectTask($tenantId, $taskId);
        $project = $task->project;

        return [
            'title' => 'Tâche - ' . $this->sanitizeText((string) $task->title) . ' - Spec',
            'content' => implode(PHP_EOL, array_filter([
                'Type de page: Spécification de tâche',
                'Tâche: ' . $this->sanitizeText((string) $task->title),
                $project ? 'Projet: ' . $this->sanitizeText((string) $project->name) : null,
                $task->due_date ? 'Échéance: ' . $task->due_date : null,
                $task->description ? 'Description: ' . $this->sanitizeText((string) $task->description) : null,
                '',
                'A documenter:',
                '- Contexte',
                "- Étapes d'exécution",
                '- Definition of done',
                '- Points de validation',
            ])),
            'icon' => '',
            'parent_page_id' => $payload['parent_page_id'] ?? null,
            'client_id' => $project?->client_id ? (int) $project->client_id : null,
            'project_id' => $project?->id ? (int) $project->id : null,
            'context_label' => $this->sanitizeText((string) ($payload['context_label'] ?? 'Spécification de tâche')),
            'notes' => 'Page Notion créée automatiquement pour documenter une tâche projet.',
            'success_message' => 'Page Notion tâche créée avec succès.',
        ];
    }

    protected function quoteDraft(int $tenantId, array $payload, ?AutomationSuggestion $suggestion): array
    {
        $quoteId = $this->modelId($payload, $suggestion, 'quote_id', Quote::class);
        if (!$quoteId) {
            throw new RuntimeException('Devis introuvable pour la création de la page Notion.');
        }

        $quote = $this->loadQuote($tenantId, $quoteId);
        $quoteRef = $this->sanitizeText((string) ($quote->quote_number ?: $quote->number ?: ('Devis #' . $quote->id)));
        $clientName = $quote->client ? $this->clientDisplayName($quote->client) : 'client';

        return [
            'title' => $quoteRef . ' - Suivi commercial',
            'content' => implode(PHP_EOL, array_filter([
                'Type de page: Suivi de devis',
                'Reference: ' . $quoteRef,
                'Client: ' . $clientName,
                $quote->valid_until ? "Valide jusqu'au: " . $quote->valid_until : null,
                '',
                'A documenter:',
                '- Historique des échanges',
                '- Objections du client',
                '- Conditions négociées',
                '- Prochaine relance',
            ])),
            'icon' => '',
            'parent_page_id' => $payload['parent_page_id'] ?? null,
            'client_id' => $quote->client_id ? (int) $quote->client_id : null,
            'project_id' => $payload['project_id'] ?? null,
            'context_label' => $this->sanitizeText((string) ($payload['context_label'] ?? 'Suivi de devis')),
            'notes' => 'Page Notion créée automatiquement pour suivre un devis.',
            'success_message' => 'Page Notion devis créée avec succès.',
        ];
    }

    protected function invoiceDraft(int $tenantId, array $payload, ?AutomationSuggestion $suggestion): array
    {
        $invoiceId = $this->modelId($payload, $suggestion, 'invoice_id', Invoice::class);
        if (!$invoiceId) {
            throw new RuntimeException('Facture introuvable pour la création de la page Notion.');
        }

        $invoice = $this->loadInvoice($tenantId, $invoiceId);
        $invoiceRef = $this->sanitizeText((string) ($invoice->invoice_number ?: $invoice->number ?: ('Facture #' . $invoice->id)));
        $clientName = $invoice->client ? $this->clientDisplayName($invoice->client) : 'client';

        return [
            'title' => $invoiceRef . ' - Suivi paiement',
            'content' => implode(PHP_EOL, array_filter([
                'Type de page: Suivi de facture',
                'Reference: ' . $invoiceRef,
                'Client: ' . $clientName,
                $invoice->status ? 'Statut: ' . $this->sanitizeText((string) $invoice->status) : null,
                $invoice->due_date ? "Date d'échéance: " . $invoice->due_date : null,
                '',
                'A documenter:',
                '- État du paiement',
                '- Relances effectuées',
                '- Blocages signalés',
                '- Prochaine action',
            ])),
            'icon' => '',
            'parent_page_id' => $payload['parent_page_id'] ?? null,
            'client_id' => $invoice->client_id ? (int) $invoice->client_id : null,
            'project_id' => $payload['project_id'] ?? null,
            'context_label' => $this->sanitizeText((string) ($payload['context_label'] ?? 'Suivi de facture')),
            'notes' => 'Page Notion créée automatiquement pour suivre une facture.',
            'success_message' => 'Page Notion facture créée avec succès.',
        ];
    }
}
