<?php

use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationLog;
use Vendor\Automation\Models\AutomationSuggestion;

return [
    'event_prefix' => 'automation.execute',

    'queue' => [
        'connection' => env('AUTOMATION_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
        'name' => env('AUTOMATION_QUEUE_NAME', 'automation'),
    ],

    'suggestions' => [
        'default_expiration_hours' => 72,
        'enabled_by_default' => true,
    ],

    'models' => [
        'suggestion' => AutomationSuggestion::class,
        'event' => AutomationEvent::class,
        'log' => AutomationLog::class,
    ],

    'providers' => [
        'client_created' => [
            \Vendor\Automation\SuggestionProviders\ClientCreatedSuggestionProvider::class,
        ],
        'invoice_created' => [
            \Vendor\Automation\SuggestionProviders\InvoiceCreatedSuggestionProvider::class,
        ],
        'quote_created' => [
            \Vendor\Automation\SuggestionProviders\QuoteCreatedSuggestionProvider::class,
        ],
        'project_created' => [
            \Vendor\Automation\SuggestionProviders\ProjectCreatedSuggestionProvider::class,
        ],
        'project_task_created' => [
            \Vendor\Automation\SuggestionProviders\ProjectTaskCreatedSuggestionProvider::class,
        ],
        'user_invited' => [
            \Vendor\Automation\SuggestionProviders\UserInvitedSuggestionProvider::class,
        ],
        'extension_activated' => [
            \Vendor\Automation\SuggestionProviders\ExtensionActivatedSuggestionProvider::class,
        ],
    ],

    'actions' => [
        'send_welcome_email' => \Vendor\Automation\Actions\SendEmailAutomationAction::class,
        'create_followup_meeting' => \Vendor\Automation\Actions\ScheduleCalendarAutomationAction::class,
        'create_quote' => \Vendor\Automation\Actions\CreateQuoteAutomationAction::class,
        'send_invoice_email' => \Vendor\Automation\Actions\SendEmailAutomationAction::class,
        'schedule_invoice_reminder' => \Vendor\Automation\Actions\ScheduleCalendarAutomationAction::class,
        'create_payment_followup_task' => \Vendor\Automation\Actions\CreateProjectTaskAutomationAction::class,
        'send_quote_email' => \Vendor\Automation\Actions\SendEmailAutomationAction::class,
        'schedule_quote_followup' => \Vendor\Automation\Actions\ScheduleCalendarAutomationAction::class,
        'create_quote_followup_task' => \Vendor\Automation\Actions\CreateProjectTaskAutomationAction::class,
        'schedule_project_kickoff' => \Vendor\Automation\Actions\ScheduleCalendarAutomationAction::class,
        'schedule_project_task_calendar' => \Vendor\Automation\Actions\ScheduleCalendarAutomationAction::class,
        'send_team_invitation_followup_email' => \Vendor\Automation\Actions\SendEmailAutomationAction::class,
        'schedule_user_onboarding_meeting' => \Vendor\Automation\Actions\ScheduleCalendarAutomationAction::class,
        'create_user_onboarding_task' => \Vendor\Automation\Actions\CreateProjectTaskAutomationAction::class,
        'create_project_drive_folder' => \Vendor\Automation\Actions\CreateProjectDriveFolderAction::class,
        'create_project_dropbox_folder' => \Vendor\Automation\Actions\CreateProjectDropboxFolderAction::class,
        'create_project_channel' => \Vendor\Automation\Actions\CreateProjectChannelAction::class,
        'open_extension_workspace' => \Vendor\Automation\Actions\OpenExtensionWorkspaceAction::class,
        'install_extension' => \Vendor\Automation\Actions\DeferredAutomationAction::class,
    ],
];
