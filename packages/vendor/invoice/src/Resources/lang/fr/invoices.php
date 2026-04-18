<?php

return [

    // ─── GÉNÉRAL ─────────────────────────────────────────────────────────
    'module_name'   => 'Facturation',
    'invoices'      => 'Factures',
    'quotes'        => 'Devis',
    'payments'      => 'Paiements',
    'currencies'    => 'Devises',

    // ─── ACTIONS ─────────────────────────────────────────────────────────
    'create_invoice'  => 'Nouvelle facture',
    'create_quote'    => 'Nouveau devis',
    'edit'            => 'Modifier',
    'delete'          => 'Supprimer',
    'send'            => 'Envoyer',
    'duplicate'       => 'Dupliquer',
    'convert'         => 'Convertir en facture',
    'download_pdf'    => 'Télécharger PDF',
    'add_payment'     => 'Enregistrer un paiement',
    'export'          => 'Exporter',
    'import'          => 'Importer',

    // ─── STATUTS FACTURES ─────────────────────────────────────────────────
    'status' => [
        'draft'     => 'Brouillon',
        'sent'      => 'Envoyée',
        'viewed'    => 'Vue',
        'partial'   => 'Partiellement payée',
        'paid'      => 'Payée',
        'overdue'   => 'En retard',
        'cancelled' => 'Annulée',
        'refunded'  => 'Remboursée',
    ],

    // ─── STATUTS DEVIS ────────────────────────────────────────────────────
    'quote_status' => [
        'draft'    => 'Brouillon',
        'sent'     => 'Envoyé',
        'viewed'   => 'Vu',
        'accepted' => 'Accepté',
        'declined' => 'Refusé',
        'expired'  => 'Expiré',
    ],

    // ─── CHAMPS ──────────────────────────────────────────────────────────
    'fields' => [
        'number'               => 'Numéro',
        'reference'            => 'Référence',
        'client'               => 'Client',
        'status'               => 'Statut',
        'currency'             => 'Devise',
        'exchange_rate'        => 'Taux de change',
        'issue_date'           => 'Date d\'émission',
        'due_date'             => 'Date d\'échéance',
        'valid_until'          => 'Valide jusqu\'au',
        'payment_terms'        => 'Conditions de paiement',
        'payment_method'       => 'Mode de paiement',
        'subtotal'             => 'Sous-total HT',
        'discount'             => 'Remise',
        'tax'                  => 'TVA',
        'withholding_tax'      => 'Retenue à la source',
        'total'                => 'Total TTC',
        'amount_paid'          => 'Montant payé',
        'amount_due'           => 'Reste à payer',
        'notes'                => 'Notes',
        'terms'                => 'Conditions',
        'footer'               => 'Pied de page',
        'internal_notes'       => 'Notes internes',
        'description'          => 'Description',
        'quantity'             => 'Quantité',
        'unit'                 => 'Unité',
        'unit_price'           => 'Prix unitaire HT',
    ],

    // ─── MESSAGES ─────────────────────────────────────────────────────────
    'messages' => [
        'created'        => 'Créé avec succès.',
        'updated'        => 'Mis à jour avec succès.',
        'deleted'        => 'Supprimé avec succès.',
        'sent'           => 'Envoyé avec succès.',
        'payment_added'  => 'Paiement enregistré.',
        'converted'      => 'Devis converti en facture.',
        'cannot_delete_paid' => 'Impossible de supprimer une facture payée.',
        'cannot_edit_paid'   => 'Impossible de modifier une facture payée.',
        'already_converted'  => 'Ce devis a déjà été converti en facture.',
    ],

    // ─── STATS ────────────────────────────────────────────────────────────
    'stats' => [
        'total_invoices'    => 'Total factures',
        'total_quotes'      => 'Total devis',
        'paid'              => 'Encaissé',
        'outstanding'       => 'En attente',
        'overdue'           => 'En retard',
        'revenue_month'     => 'CA du mois',
        'revenue_year'      => 'CA de l\'année',
        'conversion_rate'   => 'Taux de conversion devis',
    ],

    // ─── RETENUE ─────────────────────────────────────────────────────────
    'withholding' => [
        'label'   => 'Retenue à la source',
        'tooltip' => 'Montant prélevé à la source sur le paiement (applicable dans certains pays : Tunisie, Maroc, etc.)',
    ],
];
