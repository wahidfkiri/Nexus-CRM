<?php

namespace Vendor\Automation\Actions;

use NexusExtensions\GoogleGmail\Services\GoogleGmailService;
use RuntimeException;
use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationSuggestion;
use Vendor\Client\Models\Client;
use Vendor\Invoice\Models\Invoice;
use Vendor\Invoice\Models\Quote;
use Vendor\User\Models\UserInvitation;

class SendEmailAutomationAction extends AbstractAutomationAction
{
    public function __construct(
        \Vendor\Automation\Services\ExtensionAvailabilityService $extensions,
        protected GoogleGmailService $gmailService
    ) {
        parent::__construct($extensions);
    }

    public function execute(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion = null): array
    {
        return match ((string) $automationEvent->action_type) {
            'send_welcome_email' => $this->sendWelcomeEmail($automationEvent, $suggestion),
            'send_invoice_email' => $this->sendInvoiceEmail($automationEvent, $suggestion),
            'send_quote_email' => $this->sendQuoteEmail($automationEvent, $suggestion),
            'send_team_invitation_followup_email' => $this->sendInvitationFollowupEmail($automationEvent, $suggestion),
            default => throw new RuntimeException('Type d email automation non pris en charge.'),
        };
    }

    protected function sendWelcomeEmail(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'google-gmail', 'Google Gmail doit etre installe pour envoyer un email de bienvenue.');

        $payload = $this->payload($automationEvent);
        $clientId = $this->modelId($payload, $suggestion, 'client_id', Client::class);
        if (!$clientId) {
            throw new RuntimeException('Client introuvable pour l email de bienvenue.');
        }

        $client = $this->loadClient($tenantId, $clientId);
        $recipientEmail = trim((string) $client->email);
        if ($recipientEmail === '') {
            throw new RuntimeException('Ce client ne possede pas d adresse email.');
        }

        $displayName = $this->clientDisplayName($client);
        $contactName = trim((string) ($client->contact_name ?: $displayName));
        $appName = $this->appName();
        $clientUrl = $this->sourceUrlForModel($client);

        $subject = 'Bienvenue chez ' . $appName;
        $bodyText = implode("\n\n", array_filter([
            'Bonjour ' . $contactName . ',',
            'Merci pour votre confiance. Nous sommes ravis de vous compter parmi nos clients.',
            'Notre equipe reste disponible pour vous accompagner sur vos prochains besoins.',
            $clientUrl ? 'Votre fiche client dans le CRM: ' . $clientUrl : null,
            'A bientot,',
            $appName,
        ]));

        $bodyHtml = '<p>Bonjour ' . e($contactName) . ',</p>'
            . '<p>Merci pour votre confiance. Nous sommes ravis de vous compter parmi nos clients.</p>'
            . '<p>Notre equipe reste disponible pour vous accompagner sur vos prochains besoins.</p>'
            . ($clientUrl
                ? '<p><a href="' . e($clientUrl) . '" target="_blank" rel="noopener">Ouvrir votre fiche client</a></p>'
                : '')
            . '<p>A bientot,<br>' . e($appName) . '</p>';

        $result = $this->gmailService->sendEmail($tenantId, [
            'to' => $recipientEmail,
            'subject' => $subject,
            'body_text' => $bodyText,
            'body_html' => $bodyHtml,
        ]);

        $client->forceFill(['last_contact_at' => now()])->save();

        return [
            'result' => 'email_sent',
            'message' => 'Email de bienvenue envoye avec succes.',
            'client_id' => (int) $client->id,
            'client_name' => $displayName,
            'recipient_email' => $recipientEmail,
            'gmail_message_id' => (string) ($result['message_id'] ?? ''),
            'thread_id' => (string) ($result['thread_id'] ?? ''),
            'target_url' => $result['web_url'] ?? $this->routeUrl('google-gmail.index'),
        ];
    }

    protected function sendInvoiceEmail(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'google-gmail', 'Google Gmail doit etre installe pour envoyer une facture.');

        $payload = $this->payload($automationEvent);
        $invoiceId = $this->modelId($payload, $suggestion, 'invoice_id', Invoice::class);
        if (!$invoiceId) {
            throw new RuntimeException('Facture introuvable pour cet envoi.');
        }

        $invoice = $this->loadInvoice($tenantId, $invoiceId);
        $client = $invoice->client;
        if (!$client || (int) ($invoice->client_id ?? 0) <= 0) {
            throw new RuntimeException('Cette facture n est liee a aucun client CRM.');
        }

        $recipientEmail = trim((string) ($client->email ?? ''));
        $this->assertRecipientEmail($recipientEmail, 'Le client de cette facture ne possede pas d adresse email valide.');

        $clientName = $client ? $this->clientDisplayName($client) : 'client';
        $invoiceUrl = $this->routeUrl('invoices.show', $invoice);
        $amount = $this->formatMoney((float) $invoice->total, (string) $invoice->currency);
        $dueDate = $invoice->due_date?->format('d/m/Y');

        $subject = 'Facture ' . $invoice->number . ' - ' . $this->appName();
        $bodyText = implode("\n\n", array_filter([
            'Bonjour ' . $clientName . ',',
            'Votre facture ' . $invoice->number . ' est disponible pour un montant de ' . $amount . '.',
            $dueDate ? 'Date d echeance: ' . $dueDate . '.' : null,
            $invoiceUrl ? 'Consulter la facture: ' . $invoiceUrl : null,
            'Merci pour votre confiance.',
        ]));

        $bodyHtml = '<p>Bonjour ' . e($clientName) . ',</p>'
            . '<p>Votre facture <strong>' . e((string) $invoice->number) . '</strong> est disponible pour un montant de <strong>' . e($amount) . '</strong>.</p>'
            . ($dueDate ? '<p>Date d echeance: <strong>' . e($dueDate) . '</strong>.</p>' : '')
            . ($invoiceUrl
                ? '<p><a href="' . e($invoiceUrl) . '" target="_blank" rel="noopener">Consulter la facture</a></p>'
                : '')
            . '<p>Merci pour votre confiance.</p>';

        $result = $this->gmailService->sendEmail($tenantId, [
            'to' => $recipientEmail,
            'subject' => $subject,
            'body_text' => $bodyText,
            'body_html' => $bodyHtml,
        ]);

        if ((string) $invoice->status === 'draft') {
            $invoice->markAsSent();
        } elseif ($invoice->sent_at === null) {
            $invoice->forceFill(['sent_at' => now()])->save();
        }

        return [
            'result' => 'email_sent',
            'message' => 'Facture envoyee par email.',
            'invoice_id' => (int) $invoice->id,
            'invoice_number' => (string) $invoice->number,
            'recipient_email' => $recipientEmail,
            'gmail_message_id' => (string) ($result['message_id'] ?? ''),
            'thread_id' => (string) ($result['thread_id'] ?? ''),
            'target_url' => $result['web_url'] ?? ($invoiceUrl ?: $this->routeUrl('google-gmail.index')),
        ];
    }

    protected function sendQuoteEmail(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'google-gmail', 'Google Gmail doit etre installe pour envoyer un devis.');

        $payload = $this->payload($automationEvent);
        $quoteId = $this->modelId($payload, $suggestion, 'quote_id', Quote::class);
        if (!$quoteId) {
            throw new RuntimeException('Devis introuvable pour cet envoi.');
        }

        $quote = $this->loadQuote($tenantId, $quoteId);
        $client = $quote->client;
        if (!$client || (int) ($quote->client_id ?? 0) <= 0) {
            throw new RuntimeException('Ce devis n est lie a aucun client CRM.');
        }

        $recipientEmail = trim((string) ($client->email ?? ''));
        $this->assertRecipientEmail($recipientEmail, 'Le client de ce devis ne possede pas d adresse email valide.');

        $clientName = $client ? $this->clientDisplayName($client) : 'client';
        $quoteUrl = $this->routeUrl('invoices.quotes.show', $quote);
        $amount = $this->formatMoney((float) $quote->total, (string) $quote->currency);
        $validUntil = $quote->valid_until?->format('d/m/Y');

        $subject = 'Devis ' . $quote->number . ' - ' . $this->appName();
        $bodyText = implode("\n\n", array_filter([
            'Bonjour ' . $clientName . ',',
            'Votre devis ' . $quote->number . ' est pret pour un montant de ' . $amount . '.',
            $validUntil ? 'Valable jusqu au ' . $validUntil . '.' : null,
            $quoteUrl ? 'Consulter le devis: ' . $quoteUrl : null,
            'Nous restons a votre disposition.',
        ]));

        $bodyHtml = '<p>Bonjour ' . e($clientName) . ',</p>'
            . '<p>Votre devis <strong>' . e((string) $quote->number) . '</strong> est pret pour un montant de <strong>' . e($amount) . '</strong>.</p>'
            . ($validUntil ? '<p>Valable jusqu au <strong>' . e($validUntil) . '</strong>.</p>' : '')
            . ($quoteUrl
                ? '<p><a href="' . e($quoteUrl) . '" target="_blank" rel="noopener">Consulter le devis</a></p>'
                : '')
            . '<p>Nous restons a votre disposition.</p>';

        $result = $this->gmailService->sendEmail($tenantId, [
            'to' => $recipientEmail,
            'subject' => $subject,
            'body_text' => $bodyText,
            'body_html' => $bodyHtml,
        ]);

        if ((string) $quote->status === 'draft') {
            $quote->forceFill([
                'status' => 'sent',
                'sent_at' => now(),
            ])->save();
        } elseif ($quote->sent_at === null) {
            $quote->forceFill(['sent_at' => now()])->save();
        }

        return [
            'result' => 'email_sent',
            'message' => 'Devis envoye par email.',
            'quote_id' => (int) $quote->id,
            'quote_number' => (string) $quote->number,
            'recipient_email' => $recipientEmail,
            'gmail_message_id' => (string) ($result['message_id'] ?? ''),
            'thread_id' => (string) ($result['thread_id'] ?? ''),
            'target_url' => $result['web_url'] ?? ($quoteUrl ?: $this->routeUrl('google-gmail.index')),
        ];
    }

    protected function sendInvitationFollowupEmail(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'google-gmail', 'Google Gmail doit etre installe pour relancer une invitation.');

        $payload = $this->payload($automationEvent);
        $invitationId = $this->modelId($payload, $suggestion, 'invitation_id', UserInvitation::class);
        if (!$invitationId) {
            throw new RuntimeException('Invitation introuvable pour cet envoi.');
        }

        $invitation = $this->loadInvitation($tenantId, $invitationId);
        $invitation->markExpiredIfNeeded();
        if (!$invitation->isUsable()) {
            throw new RuntimeException('Cette invitation n est plus active.');
        }

        $recipientEmail = trim((string) $invitation->email);
        if ($recipientEmail === '') {
            throw new RuntimeException('Cette invitation ne contient pas d adresse email.');
        }

        $appName = $this->appName();
        $tenantName = trim((string) ($invitation->tenant?->name ?? 'votre equipe CRM'));
        $roleLabel = trim((string) ($invitation->role_in_tenant ?: 'membre'));
        $senderName = trim((string) ($invitation->invitedBy?->name ?? 'notre equipe'));
        $acceptUrl = $this->routeUrl('users.accept', (string) $invitation->token);
        $expiresAt = $invitation->expires_at?->format('d/m/Y H:i');

        $subject = 'Invitation a rejoindre ' . $tenantName;
        $bodyText = implode("\n\n", array_filter([
            'Bonjour,',
            $senderName . ' vous invite a rejoindre ' . $tenantName . ' sur ' . $appName . '.',
            'Role propose: ' . $roleLabel . '.',
            $acceptUrl ? 'Accepter l invitation: ' . $acceptUrl : null,
            $expiresAt ? 'Invitation valable jusqu au ' . $expiresAt . '.' : null,
            'A bientot,',
            $appName,
        ]));

        $bodyHtml = '<p>Bonjour,</p>'
            . '<p><strong>' . e($senderName) . '</strong> vous invite a rejoindre <strong>' . e($tenantName) . '</strong> sur ' . e($appName) . '.</p>'
            . '<p>Role propose: <strong>' . e($roleLabel) . '</strong>.</p>'
            . ($acceptUrl
                ? '<p><a href="' . e($acceptUrl) . '" target="_blank" rel="noopener">Accepter l invitation</a></p>'
                : '')
            . ($expiresAt ? '<p>Invitation valable jusqu au <strong>' . e($expiresAt) . '</strong>.</p>' : '')
            . '<p>A bientot,<br>' . e($appName) . '</p>';

        $result = $this->gmailService->sendEmail($tenantId, [
            'to' => $recipientEmail,
            'subject' => $subject,
            'body_text' => $bodyText,
            'body_html' => $bodyHtml,
        ]);

        return [
            'result' => 'email_sent',
            'message' => 'Email de relance pour l invitation envoye.',
            'invitation_id' => (int) $invitation->id,
            'recipient_email' => $recipientEmail,
            'gmail_message_id' => (string) ($result['message_id'] ?? ''),
            'thread_id' => (string) ($result['thread_id'] ?? ''),
            'target_url' => $result['web_url'] ?? ($this->routeUrl('users.invitations') ?: $this->routeUrl('google-gmail.index')),
        ];
    }

    protected function assertRecipientEmail(string $email, string $message): void
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            return;
        }

        throw new RuntimeException($message);
    }
}

