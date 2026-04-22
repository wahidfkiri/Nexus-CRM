<?php

namespace Vendor\Extensions\Console\Commands;

use Illuminate\Console\Command;
use Vendor\Extensions\Models\Extension;

class SeedExtensionsCommand extends Command
{
    protected $signature   = 'extensions:seed {--reset : Supprime et recrÃ©e le catalogue}';
    protected $description = 'Peuple le catalogue avec des extensions de dÃ©monstration';

    public function handle(): int
    {
        if ($this->option('reset')) {
            Extension::query()->forceDelete();
            $this->warn('  â†º Catalogue rÃ©initialisÃ©.');
        }

        $extensions = $this->getDemoExtensions();
        $this->info("ðŸ“¦ CrÃ©ation de " . count($extensions) . " extensions...");

        foreach ($extensions as $idx => $data) {
            $ext = Extension::firstOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['sort_order' => ($idx + 1) * 10])
            );
            $this->line("  âœ“ <comment>{$ext->name}</comment> " . ($ext->wasRecentlyCreated ? '(crÃ©Ã©e)' : '(existante)'));
        }

        $this->info('âœ… Catalogue prÃªt.');
        return self::SUCCESS;
    }

    private function getDemoExtensions(): array
    {
        return [
            // â”€â”€ STOCKAGE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            [
                'slug'          => 'google-drive',
                'name'          => 'Google Drive',
                'tagline'       => 'Stockez, partagez et accÃ©dez Ã  vos fichiers partout',
                'description'   => 'Connectez Google Drive pour joindre des fichiers directement Ã  vos clients et factures.',
                'category'      => 'storage',
                'icon'          => 'fa-google-drive',
                'icon_bg_color' => '#4285F4',
                'developer_name'=> 'Google LLC',
                'pricing_type'  => 'free',
                'status'        => 'active',
                'is_featured'   => true,
                'is_official'   => false,
                'is_verified'   => true,
                'installs_count'=> 1240,
                'rating'        => 4.8,
            ],
            [
                'slug'          => 'dropbox',
                'name'          => 'Dropbox',
                'tagline'       => 'Synchronisez vos documents d\'affaires',
                'description'   => 'Reliez votre espace Dropbox pour un accÃ¨s rapide Ã  vos fichiers mÃ©tiers.',
                'category'      => 'storage',
                'icon'          => 'fa-dropbox',
                'icon_bg_color' => '#0061FF',
                'developer_name'=> 'Dropbox Inc.',
                'pricing_type'  => 'freemium',
                'price'         => 9.99,
                'billing_cycle' => 'monthly',
                'status'        => 'active',
                'is_new'        => true,
                'installs_count'=> 430,
                'rating'        => 4.5,
            ],

            // â”€â”€ COMMUNICATION â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            [
                'slug'          => 'slack',
                'name'          => 'Slack',
                'tagline'       => 'Recevez vos alertes CRM dans Slack',
                'description'   => 'Notifications de nouveaux clients, factures et opportunitÃ©s directement dans vos canaux Slack.',
                'category'      => 'communication',
                'icon'          => 'fa-slack',
                'icon_bg_color' => '#4A154B',
                'developer_name'=> 'Slack Technologies',
                'pricing_type'  => 'free',
                'status'        => 'active',
                'is_featured'   => true,
                'is_verified'   => true,
                'installs_count'=> 2100,
                'rating'        => 4.9,
            ],
            [
                'slug'          => 'microsoft-teams',
                'name'          => 'Microsoft Teams',
                'tagline'       => 'IntÃ©gration native avec l\'Ã©cosystÃ¨me Microsoft',
                'description'   => 'Synchronisez vos contacts, rÃ©unions et documents avec Microsoft Teams.',
                'category'      => 'communication',
                'icon'          => 'fa-microsoft',
                'icon_bg_color' => '#6264A7',
                'pricing_type'  => 'free',
                'status'        => 'active',
                'installs_count'=> 890,
                'rating'        => 4.6,
            ],
            [
                'slug'          => 'twilio-sms',
                'name'          => 'Twilio SMS',
                'tagline'       => 'Envoyez des SMS Ã  vos clients',
                'description'   => 'Campagnes SMS, rappels de rendez-vous et notifications personnalisÃ©es.',
                'category'      => 'communication',
                'icon'          => 'fa-sms',
                'icon_bg_color' => '#F22F46',
                'developer_name'=> 'Twilio Inc.',
                'pricing_type'  => 'usage',
                'price'         => 0.05,
                'status'        => 'active',
                'has_trial'     => true,
                'trial_days'    => 30,
                'installs_count'=> 320,
                'rating'        => 4.4,
            ],

            // â”€â”€ IA â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            [
                'slug'          => 'nexus-ai',
                'name'          => 'Nexus AI Assistant',
                'tagline'       => 'IA gÃ©nÃ©rative pour votre CRM',
                'description'   => 'GÃ©nÃ©rez des emails, rÃ©sumÃ©s clients et analyses commerciales grÃ¢ce Ã  l\'IA.',
                'category'      => 'ai',
                'icon'          => 'fa-robot',
                'icon_bg_color' => '#f59e0b',
                'pricing_type'  => 'paid',
                'price'         => 29.00,
                'billing_cycle' => 'monthly',
                'has_trial'     => true,
                'trial_days'    => 14,
                'status'        => 'active',
                'is_featured'   => true,
                'is_official'   => true,
                'is_new'        => true,
                'installs_count'=> 540,
                'rating'        => 4.9,
            ],
            [
                'slug'          => 'chatgpt-integration',
                'name'          => 'ChatGPT',
                'tagline'       => 'IntÃ©gration OpenAI ChatGPT',
                'description'   => 'Utilisez GPT-4 pour automatiser vos rÃ©ponses clients et crÃ©er du contenu.',
                'category'      => 'ai',
                'icon'          => 'fa-brain',
                'icon_bg_color' => '#10a37f',
                'developer_name'=> 'OpenAI',
                'pricing_type'  => 'paid',
                'price'         => 19.00,
                'billing_cycle' => 'monthly',
                'has_trial'     => true,
                'trial_days'    => 7,
                'status'        => 'active',
                'installs_count'=> 710,
                'rating'        => 4.7,
            ],

            // â”€â”€ MARKETING â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            [
                'slug'          => 'mailchimp',
                'name'          => 'Mailchimp',
                'tagline'       => 'Synchronisez vos listes et campagnes email',
                'description'   => 'Exportez vos contacts vers Mailchimp et dÃ©clenchez des campagnes depuis le CRM.',
                'category'      => 'marketing',
                'icon'          => 'fa-mailchimp',
                'icon_bg_color' => '#FFE01B',
                'developer_name'=> 'Intuit Mailchimp',
                'pricing_type'  => 'free',
                'status'        => 'active',
                'installs_count'=> 980,
                'rating'        => 4.5,
            ],
            [
                'slug'          => 'hubspot',
                'name'          => 'HubSpot CRM',
                'tagline'       => 'Synchronisation bidirectionnelle HubSpot',
                'description'   => 'Importez/exportez contacts, deals et activitÃ©s entre NexusCRM et HubSpot.',
                'category'      => 'marketing',
                'icon'          => 'fa-hubspot',
                'icon_bg_color' => '#FF7A59',
                'developer_name'=> 'HubSpot Inc.',
                'pricing_type'  => 'freemium',
                'price'         => 49.00,
                'billing_cycle' => 'monthly',
                'status'        => 'active',
                'is_verified'   => true,
                'installs_count'=> 620,
                'rating'        => 4.6,
            ],

            // â”€â”€ PRODUCTIVITÃ‰ â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            [
                'slug'          => 'google-calendar',
                'name'          => 'Google Calendar',
                'tagline'       => 'Synchronisez vos rendez-vous clients',
                'description'   => 'CrÃ©ez des Ã©vÃ©nements depuis vos fiches clients, synchronisation temps rÃ©el.',
                'category'      => 'productivity',
                'icon'          => 'fa-calendar-days',
                'icon_bg_color' => '#4285F4',
                'developer_name'=> 'Google LLC',
                'pricing_type'  => 'free',
                'status'        => 'active',
                'is_featured'   => true,
                'installs_count'=> 1560,
                'rating'        => 4.8,
            ],

            // â”€â”€ PRODUCTIVITÃ‰ â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            [
                'slug'           => 'google-sheets',
                'name'           => 'Google Sheets',
                'tagline'        => 'CrÃ©ez et gÃ©rez vos feuilles de calcul Google',
                'description'    => 'Connectez Google Sheets pour crÃ©er, lire, modifier et supprimer des feuilles de calcul directement depuis le CRM.',
                'category'       => 'productivity',
                'icon'           => 'fa-file-excel',
                'icon_bg_color'  => '#0f9d58',
                'developer_name' => 'Google LLC',
                'pricing_type'   => 'free',
                'status'         => 'active',
                'is_featured'    => true,
                'is_official'    => false,
                'is_verified'    => true,
                'installs_count' => 980,
                'rating'         => 4.7,
            ],
            [
                'slug'           => 'google-docx',
                'name'           => 'Google Docs',
                'tagline'        => 'Create and manage your Google documents',
                'description'    => 'Connect Google Docs to create, read, edit, duplicate and export your documents directly from the CRM.',
                'category'       => 'productivity',
                'icon'           => 'fa-file-word',
                'icon_bg_color'  => '#1a73e8',
                'developer_name' => 'Google LLC',
                'pricing_type'   => 'free',
                'status'         => 'active',
                'is_featured'    => true,
                'is_official'    => false,
                'is_verified'    => true,
                'installs_count' => 640,
                'rating'         => 4.6,
            ],
            [
                'slug'           => 'google-gmail',
                'name'           => 'Google Gmail',
                'tagline'        => 'Read, send and manage your Gmail inbox',
                'description'    => 'Connect Gmail to read, send, reply, forward, archive and manage email directly inside the CRM.',
                'category'       => 'communication',
                'icon'           => 'fa-envelope-open-text',
                'icon_bg_color'  => '#ea4335',
                'developer_name' => 'Google LLC',
                'pricing_type'   => 'free',
                'status'         => 'active',
                'is_featured'    => true,
                'is_official'    => false,
                'is_verified'    => true,
                'installs_count' => 520,
                'rating'         => 4.7,
            ],
            [
                'slug'          => 'zapier',
                'name'          => 'Zapier',
                'tagline'       => 'Connectez 5000+ applications',
                'description'   => 'Automatisez vos workflows en connectant NexusCRM Ã  toutes vos applications via Zapier.',
                'category'      => 'integration',
                'icon'          => 'fa-bolt',
                'icon_bg_color' => '#FF4A00',
                'developer_name'=> 'Zapier Inc.',
                'pricing_type'  => 'freemium',
                'price'         => 19.99,
                'billing_cycle' => 'monthly',
                'has_trial'     => true,
                'trial_days'    => 14,
                'status'        => 'active',
                'is_verified'   => true,
                'installs_count'=> 750,
                'rating'        => 4.7,
            ],

            // â”€â”€ FINANCE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            [
                'slug'          => 'stripe-payments',
                'name'          => 'Stripe',
                'tagline'       => 'Encaissez vos factures en ligne',
                'description'   => 'Envoyez des liens de paiement Stripe depuis vos factures NexusCRM.',
                'category'      => 'finance',
                'icon'          => 'fa-stripe',
                'icon_bg_color' => '#635BFF',
                'developer_name'=> 'Stripe Inc.',
                'pricing_type'  => 'free',
                'status'        => 'active',
                'is_official'   => true,
                'is_featured'   => true,
                'installs_count'=> 1890,
                'rating'        => 4.9,
            ],
            [
                'slug'          => 'quickbooks',
                'name'          => 'QuickBooks',
                'tagline'       => 'Synchronisation comptable complÃ¨te',
                'description'   => 'Exportez automatiquement vos factures vers QuickBooks pour la comptabilitÃ©.',
                'category'      => 'finance',
                'icon'          => 'fa-calculator',
                'icon_bg_color' => '#2CA01C',
                'developer_name'=> 'Intuit Inc.',
                'pricing_type'  => 'paid',
                'price'         => 15.00,
                'billing_cycle' => 'monthly',
                'status'        => 'active',
                'installs_count'=> 430,
                'rating'        => 4.4,
            ],
        ];
    }
}
