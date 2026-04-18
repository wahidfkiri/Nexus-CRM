<?php

return [
    'title' => 'Gestion des clients',
    'subtitle' => 'Gérez votre portefeuille clients',
    
    'fields' => [
        'company_name' => 'Nom de l\'entreprise',
        'contact_name' => 'Personne de contact',
        'email' => 'Email',
        'phone' => 'Téléphone',
        'mobile' => 'Mobile',
        'address' => 'Adresse',
        'city' => 'Ville',
        'postal_code' => 'Code postal',
        'country' => 'Pays',
        'type' => 'Type',
        'status' => 'Statut',
        'source' => 'Source',
        'revenue' => 'Chiffre d\'affaires',
        'notes' => 'Notes',
    ],
    
    'types' => [
        'entreprise' => 'Entreprise',
        'particulier' => 'Particulier',
        'startup' => 'Startup',
    ],
    
    'statuses' => [
        'actif' => 'Actif',
        'inactif' => 'Inactif',
        'en_attente' => 'En attente',
    ],
    
    'sources' => [
        'direct' => 'Direct',
        'site_web' => 'Site web',
        'reference' => 'Recommandation',
        'reseau_social' => 'Réseau social',
        'autre' => 'Autre',
    ],
    
    'actions' => [
        'create' => 'Nouveau client',
        'edit' => 'Modifier',
        'delete' => 'Supprimer',
        'export' => 'Exporter',
        'import' => 'Importer',
    ],
    
    'messages' => [
        'created' => 'Client créé avec succès',
        'updated' => 'Client mis à jour avec succès',
        'deleted' => 'Client supprimé avec succès',
        'imported' => 'Clients importés avec succès',
        'exported' => 'Export réussi',
    ],
];