<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuration RBAC — Rôles & Permissions
    |--------------------------------------------------------------------------
    | Chaque tenant peut personnaliser ses rôles et permissions via l'UI.
    | Les permissions sont groupées par module pour faciliter l'affichage.
    |--------------------------------------------------------------------------
    */

    // Rôles système (non modifiables, toujours présents)
    'system_roles' => ['owner', 'super-admin'],

    // Rôles par défaut créés à l'installation
    'default_roles' => [
        'owner'   => ['label' => 'Propriétaire',    'color' => '#7c3aed', 'description' => 'Accès total, non modifiable'],
        'admin'   => ['label' => 'Administrateur',  'color' => '#2563eb', 'description' => 'Accès complet à la gestion'],
        'manager' => ['label' => 'Gestionnaire',    'color' => '#0891b2', 'description' => 'Gestion opérationnelle'],
        'user'    => ['label' => 'Utilisateur',     'color' => '#059669', 'description' => 'Opérations courantes'],
        'viewer'  => ['label' => 'Visiteur',        'color' => '#64748b', 'description' => 'Lecture seule'],
    ],

    // Permissions groupées par module
    'permission_groups' => [

        'users' => [
            'label' => 'Utilisateurs & Équipe',
            'icon'  => 'fa-users',
            'permissions' => [
                'users.read'   => 'Voir les membres',
                'users.invite' => 'Inviter des membres',
                'users.update' => 'Modifier les membres',
                'users.delete' => 'Supprimer des membres',
                'roles.read'   => 'Voir les rôles',
                'roles.manage' => 'Gérer les rôles et permissions',
            ],
        ],

        'clients' => [
            'label' => 'Clients & CRM',
            'icon'  => 'fa-handshake',
            'permissions' => [
                'clients.read'   => 'Voir les clients',
                'clients.create' => 'Créer des clients',
                'clients.update' => 'Modifier les clients',
                'clients.delete' => 'Supprimer des clients',
                'clients.export' => 'Exporter les clients',
                'clients.import' => 'Importer des clients',
            ],
        ],

        'invoices' => [
            'label' => 'Facturation',
            'icon'  => 'fa-file-invoice',
            'permissions' => [
                'invoices.read'    => 'Voir les factures',
                'invoices.create'  => 'Créer des factures',
                'invoices.update'  => 'Modifier les factures',
                'invoices.delete'  => 'Supprimer des factures',
                'invoices.send'    => 'Envoyer des factures',
                'invoices.export'  => 'Exporter les factures',
                'quotes.read'      => 'Voir les devis',
                'quotes.create'    => 'Créer des devis',
                'quotes.update'    => 'Modifier les devis',
                'quotes.delete'    => 'Supprimer les devis',
                'quotes.convert'   => 'Convertir devis en facture',
                'payments.read'    => 'Voir les paiements',
                'payments.create'  => 'Enregistrer des paiements',
                'payments.delete'  => 'Supprimer des paiements',
            ],
        ],

        'stock' => [
            'label' => 'Stock & Inventaire',
            'icon'  => 'fa-warehouse',
            'permissions' => [
                'stock.read'       => 'Voir les articles',
                'stock.create'     => 'Créer des articles',
                'stock.update'     => 'Modifier les articles',
                'stock.delete'     => 'Supprimer des articles',
                'suppliers.read'   => 'Voir les fournisseurs',
                'suppliers.manage' => 'Gérer les fournisseurs',
                'orders.read'      => 'Voir les commandes',
                'orders.create'    => 'Créer des commandes',
                'orders.receive'   => 'Réceptionner des commandes',
            ],
        ],

        'reports' => [
            'label' => 'Rapports & Analytiques',
            'icon'  => 'fa-chart-line',
            'permissions' => [
                'reports.read'   => 'Voir les rapports',
                'reports.export' => 'Exporter les rapports',
            ],
        ],

        'settings' => [
            'label' => 'Paramètres',
            'icon'  => 'fa-gear',
            'permissions' => [
                'settings.read'   => 'Voir les paramètres',
                'settings.update' => 'Modifier les paramètres',
                'settings.billing'=> 'Gérer l\'abonnement',
            ],
        ],
    ],

    // Permissions par défaut pour chaque rôle
    'default_role_permissions' => [
        'owner' => ['*'],   // Toutes les permissions
        'admin' => [
            'users.read','users.invite','users.update','users.delete',
            'roles.read',
            'clients.read','clients.create','clients.update','clients.delete','clients.export','clients.import',
            'invoices.read','invoices.create','invoices.update','invoices.delete','invoices.send','invoices.export',
            'quotes.read','quotes.create','quotes.update','quotes.delete','quotes.convert',
            'payments.read','payments.create','payments.delete',
            'stock.read','stock.create','stock.update','stock.delete',
            'suppliers.read','suppliers.manage',
            'orders.read','orders.create','orders.receive',
            'reports.read','reports.export',
            'settings.read','settings.update',
        ],
        'manager' => [
            'users.read',
            'clients.read','clients.create','clients.update','clients.export',
            'invoices.read','invoices.create','invoices.update','invoices.send',
            'quotes.read','quotes.create','quotes.update','quotes.convert',
            'payments.read','payments.create',
            'stock.read','stock.create','stock.update',
            'suppliers.read',
            'orders.read','orders.create','orders.receive',
            'reports.read',
        ],
        'user' => [
            'clients.read','clients.create','clients.update',
            'invoices.read','invoices.create',
            'quotes.read','quotes.create',
            'payments.read',
            'stock.read',
            'orders.read',
        ],
        'viewer' => [
            'clients.read',
            'invoices.read',
            'quotes.read',
            'stock.read',
            'reports.read',
        ],
    ],

    // Pagination
    'pagination' => [
        'per_page' => 15,
    ],

    // Cache
    'cache' => [
        'enabled' => true,
        'ttl'     => 3600,
        'prefix'  => 'rbac_',
    ],
];