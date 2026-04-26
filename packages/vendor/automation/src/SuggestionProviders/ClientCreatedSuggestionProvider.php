<?php

namespace Vendor\Automation\SuggestionProviders;

use Illuminate\Support\Facades\Route;
use Vendor\Automation\Contracts\SuggestionProvider;
use Vendor\Automation\Data\SuggestionDefinition;
use Vendor\Automation\Services\ExtensionAvailabilityService;

class ClientCreatedSuggestionProvider implements SuggestionProvider
{
    public function __construct(
        protected ExtensionAvailabilityService $extensions
    ) {
    }

    public function suggest(string $sourceEvent, array $context = []): iterable
    {
        $tenantId = (int) ($context['tenant_id'] ?? 0);
        $client = (array) ($context['client'] ?? []);
        $clientId = (int) ($client['id'] ?? 0);
        $clientName = (string) ($client['company_name'] ?? $client['contact_name'] ?? 'ce client');

        if ($tenantId <= 0 || $clientId <= 0) {
            return [];
        }

        $suggestions = [];

        $calendarInstalled = $this->extensions->isActive($tenantId, 'google-calendar');
        $suggestions[] = SuggestionDefinition::make(
            $calendarInstalled ? 'create_followup_meeting' : 'install_extension',
            $calendarInstalled
                ? "Créer un rendez-vous de découverte pour {$clientName}"
                : 'Installer Google Calendar pour planifier un rendez-vous',
            0.89,
            $calendarInstalled
                ? ['client_id' => $clientId, 'meeting_type' => 'discovery']
                : ['extension_slug' => 'google-calendar', 'client_id' => $clientId, 'target_action' => 'create_followup_meeting'],
            [
                'integration' => 'google-calendar',
                'installed' => $calendarInstalled,
                'target_url' => $this->extensions->targetUrl('google-calendar'),
            ]
        );

        $invoiceInstalled = $this->extensions->isActive($tenantId, 'invoice');
        $quoteUrl = Route::has('invoices.quotes.create')
            ? route('invoices.quotes.create') . '?client_id=' . $clientId
            : $this->extensions->targetUrl('invoice');
        $suggestions[] = SuggestionDefinition::make(
            $invoiceInstalled ? 'create_quote' : 'install_extension',
            $invoiceInstalled
                ? "Créer un devis pour {$clientName}"
                : 'Installer la facturation pour créer un devis',
            0.84,
            $invoiceInstalled
                ? ['client_id' => $clientId]
                : ['extension_slug' => 'invoice', 'client_id' => $clientId, 'target_action' => 'create_quote'],
            [
                'integration' => 'invoice',
                'installed' => $invoiceInstalled,
                'target_url' => $quoteUrl,
            ]
        );

        return $suggestions;
    }
}
