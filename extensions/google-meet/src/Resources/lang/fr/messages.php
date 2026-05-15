<?php

return [
    'common' => [
        'success' => 'Succès',
        'error' => 'Erreur',
        'validation' => 'Validation',
    ],

    'errors' => [
        'oauth_state_mismatch' => 'L état OAuth ne correspond pas à la session en cours.',
        'extension_inactive' => 'Google Meet n est pas active pour ce tenant. Activez-la depuis le Marketplace.',
        'storage_missing' => 'Les tables Google Meet sont absentes. Executez: php artisan migrate',
        'client_id_missing' => 'GOOGLE_MEET_CLIENT_ID est manquant.',
        'invalid_oauth_state' => 'État OAuth invalide.',
        'not_connected' => 'Google Meet n est pas connecté pour ce tenant.',
        'session_expired' => 'Session Google Meet expirée ou révoquée. Reconnectez votre compte Google.',
        'calendar_missing' => 'Le calendrier sélectionné n existe pas pour ce tenant.',
        'no_calendar_selected' => 'Aucun calendrier sélectionné.',
        'end_after_start' => 'La date de fin de réunion doit être après la date de début.',
        'event_id_missing' => 'L identifiant d événement Google Meet est manquant.',
        'google_session_invalid' => 'Session Google invalide ou expirée. Reconnectez Google Meet.',
        'google_event_not_found' => 'Réunion introuvable sur Google Calendar',
        'google_permission_denied' => 'Google a refusé la requête. Vérifiez les scopes OAuth et les droits du compte.',
        'google_access_blocked' => 'Accès Google bloqué. Vérifiez la configuration OAuth et les URI de redirection.',
        'unexpected' => 'Erreur Google Meet inattendue.',
    ],

    'success' => [
        'connected' => 'Google Meet connecté avec succès.',
        'disconnected' => 'Google Meet déconnecté.',
        'calendar_selected' => 'Calendrier sélectionné avec succès.',
        'sync_count' => ':count réunion(s) synchronisée(s).',
        'meeting_created' => 'Réunion Google Meet créée avec succès.',
        'meeting_updated' => 'Réunion mise à jour avec succès.',
        'meeting_deleted' => 'Réunion supprimée.',
    ],

    'validation' => [
        'calendar_required' => 'Veuillez sélectionner un calendrier.',
        'summary_required' => 'Le titre de la réunion est obligatoire.',
        'summary_min' => 'Le titre doit contenir au moins 2 caractères.',
        'start_required' => 'La date de début est obligatoire.',
        'end_required' => 'La date de fin est obligatoire.',
        'end_after' => 'La date de fin doit être après la date de début.',
        'send_updates_in' => 'La valeur de notification est invalide.',
        'attendees_invalid' => 'Un ou plusieurs e-mails participants sont invalides.',
    ],
];
