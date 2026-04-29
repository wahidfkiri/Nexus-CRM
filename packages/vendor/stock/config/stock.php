<?php

return [
    'pagination' => [
        'per_page' => 15,
    ],

    'article_statuses' => [
        'active' => 'Actif',
        'inactive' => 'Inactif',
    ],

    'order_statuses' => [
        'draft' => 'Brouillon',
        'ordered' => 'Commandee',
        'received' => 'Recue',
        'cancelled' => 'Annulee',
    ],

    'delivery_note_statuses' => [
        'draft' => 'Brouillon',
        'validated' => 'Valide',
        'cancelled' => 'Annule',
    ],

    'movement_types' => [
        'opening_balance' => 'Stock initial',
        'delivery_note_in' => 'BL entree',
        'delivery_note_out' => 'BL sortie',
        'delivery_note_reversal' => 'Contre-passation BL',
        'adjustment' => 'Ajustement',
        'return' => 'Retour',
    ],
];
