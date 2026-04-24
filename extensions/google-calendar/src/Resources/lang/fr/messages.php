<?php

return [
    'breadcrumb' => [
        'applications' => 'Applications',
    ],

    'common' => [
        'success' => 'Succès',
        'error' => 'Erreur',
        'validation' => 'Validation',
        'no_title' => '(Sans titre)',
        'no_data_title' => 'Aucune donnée',
        'no_data_message' => 'Aucune donnée disponible.',
        'all_day' => 'Toute la journée',
        'no_events' => 'Aucun événement',
        'more' => 'de plus',
    ],

    'page' => [
        'title' => 'Google Calendar',
        'subtitle' => 'Synchronisez vos calendriers et gérez vos événements tenant avec OAuth Google.',
    ],

    'actions' => [
        'migration_required' => 'Migration requise',
        'activate_marketplace' => 'Activer depuis Marketplace',
        'sync' => 'Synchroniser',
        'new_event' => 'Nouvel événement',
        'disconnect' => 'Déconnecter',
        'connect_google' => 'Connecter Google Calendar',
        'cancel' => 'Annuler',
        'save_event' => 'Enregistrer',
        'open_google' => 'Ouvrir dans Google',
        'edit' => 'Modifier',
        'delete' => 'Supprimer',
    ],

    'storage' => [
        'title' => 'Migration base de données requise',
        'description' => 'Les tables Google Calendar sont absentes. Exécutez la migration avant d’utiliser ce module.',
    ],

    'extension' => [
        'title' => 'Extension non activée',
        'description' => 'Google Calendar est installé sur la plateforme mais pas encore activé pour ce tenant. Activez-le depuis Marketplace pour utiliser OAuth et la synchronisation des événements.',
        'open_app_page' => 'Ouvrir la page application',
        'browse_apps' => 'Parcourir les applications',
    ],

    'connection' => [
        'title' => 'Connexion Google Calendar',
        'description' => 'Ce tenant n’a pas encore connecté Google Calendar. Lancez l’authentification OAuth pour activer la synchronisation, la sélection de calendrier et la gestion complète des événements.',
        'connect_now' => 'Se connecter maintenant',
        'open_marketplace' => 'Ouvrir Marketplace',
    ],

    'stats' => [
        'calendars' => 'Calendriers',
        'events_today' => 'Événements du jour',
        'this_month' => 'Ce mois',
        'next_30_days' => '30 prochains jours',
        'holidays_year' => 'Jours fériés (année)',
    ],

    'account' => [
        'title' => 'Compte connecté',
        'name' => 'Nom',
        'email' => 'Email',
        'connected' => 'Connecté',
        'last_sync' => 'Dernière sync',
        'unknown' => 'Inconnu',
        'never' => 'Jamais',
    ],

    'calendars' => [
        'title' => 'Calendriers',
        'primary' => 'Principal',
        'no_calendars_title' => 'Aucun calendrier',
        'no_calendars_desc' => 'Lancez une synchronisation après connexion Google Calendar.',
    ],

    'table' => [
        'events' => 'Événements',
        'count_results' => ':count résultat(s)',
        'pagination_showing' => 'Affichage :from à :to sur :total événement(s)',
        'empty_filtered' => 'Aucun événement trouvé pour les filtres sélectionnés.',
    ],

    'columns' => [
        'title' => 'Titre',
        'calendar' => 'Calendrier',
        'start' => 'Début',
        'end' => 'Fin',
        'status' => 'Statut',
        'actions' => 'Actions',
    ],

    'filters' => [
        'search' => 'Rechercher titre, description, lieu...',
        'from' => 'Du',
        'to' => 'Au',
        'include_holidays' => 'Inclure jours fériés',
        'reset' => 'Réinitialiser',
    ],

    'views' => [
        'aria' => 'Mode d’affichage calendrier',
        'month' => 'Mois',
        'week' => 'Semaine',
        'day' => 'Jour',
        'year' => 'Année',
        'list' => 'Liste',
    ],

    'period' => [
        'previous' => 'Période précédente',
        'today' => 'Aujourd’hui',
        'next' => 'Période suivante',
    ],

    'modal' => [
        'create_event' => 'Créer un événement',
        'edit_event' => 'Modifier un événement',
        'subtitle' => 'Les données sont enregistrées sur Google Calendar et synchronisées localement.',
    ],

    'form' => [
        'title' => 'Titre',
        'start' => 'Début',
        'end' => 'Fin',
        'location' => 'Lieu',
        'visibility' => 'Visibilité',
        'reminder' => 'Rappel (min)',
        'reminder_placeholder' => '10',
        'attendees' => 'Participants (emails séparés par virgule)',
        'attendees_placeholder' => 'john@entreprise.com, jane@entreprise.com',
        'description' => 'Description',
    ],

    'visibility' => [
        'default' => 'Par défaut',
        'public' => 'Public',
        'private' => 'Privé',
        'confidential' => 'Confidentiel',
    ],

    'status' => [
        'confirmed' => 'Confirmé',
        'tentative' => 'Provisoire',
        'cancelled' => 'Annulé',
        'unknown' => 'Inconnu',
    ],

    'badges' => [
        'holiday' => 'Férié',
    ],

    'validation' => [
        'calendar' => 'Veuillez sélectionner un calendrier.',
        'title_required' => 'Le titre est obligatoire.',
        'start_required' => 'La date de début est obligatoire.',
        'end_required' => 'La date de fin est obligatoire.',
        'end_after_start' => 'La date de fin doit être après la date de début.',
        'attendees' => 'Un ou plusieurs emails participants sont invalides.',
    ],

    'errors' => [
        'load_calendars' => 'Impossible de charger les calendriers.',
        'select_calendar' => 'Impossible de sélectionner ce calendrier.',
        'load_events' => 'Impossible de charger les événements.',
        'sync' => 'Échec de la synchronisation.',
        'disconnect' => 'Impossible de déconnecter Google Calendar.',
        'delete' => 'Impossible de supprimer cet événement.',
        'save' => 'Impossible d’enregistrer cet événement.',
        'validation' => 'Veuillez corriger les erreurs du formulaire.',
    ],

    'success' => [
        'calendar_selected' => 'Calendrier sélectionné.',
        'sync' => 'Synchronisation terminée.',
        'disconnected_title' => 'Déconnecté',
        'disconnected_message' => 'Google Calendar a été déconnecté.',
        'deleted_title' => 'Supprimé',
        'deleted_message' => 'Événement supprimé.',
        'saved' => 'Événement enregistré.',
    ],

    'confirm' => [
        'disconnect_title' => 'Déconnecter Google Calendar ?',
        'disconnect_message' => 'Les tokens OAuth seront supprimés pour ce tenant.',
        'disconnect_button' => 'Déconnecter',
        'delete_title' => 'Supprimer cet événement ?',
        'delete_message' => 'L’événement ":title" sera supprimé de Google Calendar.',
        'delete_button' => 'Supprimer',
    ],

    'mode' => [
        'no_events_title' => 'Aucun événement',
        'no_events_message' => 'Aucun événement trouvé sur cette période.',
        'load_error_title' => 'Erreur de chargement',
    ],
];
