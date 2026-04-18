@extends('invoice::layouts.invoice')

@section('title', 'Nouvelle facture')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">
            <span class="title-icon">➕</span>
            Nouvelle facture
        </h1>
        <p class="page-subtitle">Créez et configurez votre facture</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('invoices.index') }}" class="btn btn-outline">← Retour</a>
    </div>
</div>

<form id="invoice-form" action="{{ route('invoices.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="invoice-builder">

        {{-- ── COLONNE PRINCIPALE ─────────────────────────────────────────── --}}
        <div class="builder-main">

            {{-- Informations générales --}}
            <div class="inv-card">
                <div class="inv-card-header">
                    <span class="inv-card-title">📋 Informations générales</span>
                </div>
                <div class="inv-card-body">
                    <div class="form-row col-2">
                        <div class="form-group">
                            <label>Client <span class="required">*</span></label>
                            <div class="client-select-wrap">
                                <input type="text" id="client-search-input" class="form-control"
                                       placeholder="Rechercher un client…" autocomplete="off">
                                <input type="hidden" name="client_id" id="client_id">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Référence interne</label>
                            <input type="text" name="reference" class="form-control" placeholder="ex: PO-2024-001">
                        </div>
                    </div>

                    <div class="form-row col-3">
                        <div class="form-group">
                            <label>Date d'émission <span class="required">*</span></label>
                            <input type="date" name="issue_date" id="issue_date" class="form-control"
                                   value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group">
                            <label>Conditions de paiement</label>
                            <select name="payment_terms" id="payment_terms" class="form-select">
                                @foreach($payment_terms as $days => $label)
                                    <option value="{{ $days }}" {{ $days == 30 ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date d'échéance</label>
                            <input type="date" name="due_date" id="due_date" class="form-control">
                        </div>
                    </div>

                    <div class="form-row col-2">
                        <div class="form-group">
                            <label>Mode de paiement</label>
                            <select name="payment_method" class="form-select">
                                <option value="">— Sélectionner —</option>
                                @foreach($payment_methods as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Devise</label>
                            <div class="currency-select-wrap">
                                <select name="currency" id="currency" class="form-select">
                                    @foreach($currencies as $code => $cfg)
                                        <option value="{{ $code }}" {{ $code === config('crm-core.formats.currency','EUR') ? 'selected' : '' }}>
                                            {{ $code }} — {{ $cfg['name'] }} ({{ $cfg['symbol'] }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="exchange-rate-display" id="exchange-rate-display" style="display:none"></div>
                            </div>
                            <input type="hidden" name="exchange_rate" id="exchange_rate" value="1">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Lignes de facture --}}
            <div class="inv-card">
                <div class="inv-card-header">
                    <span class="inv-card-title">📦 Lignes de facture</span>
                    <button type="button" class="btn btn-ghost btn-sm" id="add-line-btn">+ Ajouter une ligne</button>
                </div>
                <div class="line-items-wrap">
                    <table class="line-items">
                        <thead>
                            <tr>
                                <th style="width:20px"></th>
                                <th class="item-desc">Description</th>
                                <th class="item-qty">Qté / Unité</th>
                                <th class="item-price">Prix unit. HT</th>
                                <th class="item-disc">Remise</th>
                                <th class="item-tax">TVA %</th>
                                <th class="item-total">Total TTC</th>
                                <th class="item-actions"></th>
                            </tr>
                        </thead>
                        <tbody id="line-items-body"></tbody>
                    </table>
                    <button type="button" class="add-line-btn" id="add-line-btn-bottom">
                        + Ajouter une ligne
                    </button>
                </div>
            </div>

            {{-- Notes & Conditions --}}
            <div class="inv-card">
                <div class="inv-card-header">
                    <span class="inv-card-title">📝 Notes & Conditions</span>
                </div>
                <div class="inv-card-body">
                    <div class="form-row col-2">
                        <div class="form-group">
                            <label>Notes (visibles par le client)</label>
                            <textarea name="notes" class="form-control" rows="3"
                                      placeholder="Informations complémentaires, instructions de paiement…"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Conditions générales</label>
                            <textarea name="terms" class="form-control" rows="3"
                                      placeholder="Conditions de vente, mentions légales…"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notes internes (non visibles par le client)</label>
                        <textarea name="internal_notes" class="form-control" rows="2"
                                  placeholder="Commentaires internes…"></textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── SIDEBAR TOTAUX ─────────────────────────────────────────────── --}}
        <div class="builder-sidebar">

            {{-- Actions --}}
            <div class="inv-card">
                <div class="inv-card-body" style="display:flex;flex-direction:column;gap:10px">
                    <button type="submit" name="action" value="draft" class="btn btn-primary btn-lg" style="width:100%">
                        💾 Enregistrer comme brouillon
                    </button>
                    <button type="submit" name="action" value="send" class="btn btn-success" style="width:100%">
                        📤 Enregistrer & Envoyer
                    </button>
                    <a href="{{ route('invoices.index') }}" class="btn btn-outline" style="width:100%;text-align:center">
                        Annuler
                    </a>
                </div>
            </div>

            {{-- Remise globale --}}
            <div class="inv-card">
                <div class="inv-card-header"><span class="inv-card-title">💸 Remise globale</span></div>
                <div class="inv-card-body">
                    <div class="form-row col-2">
                        <div class="form-group">
                            <label>Type</label>
                            <select name="discount_type" id="discount_type" class="form-select">
                                @foreach($discount_types as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                                <option value="none" selected>Aucune</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Valeur</label>
                            <input type="number" name="discount_value" id="discount_value"
                                   class="form-control" value="0" min="0" step="any">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Taxes --}}
            <div class="inv-card">
                <div class="inv-card-header"><span class="inv-card-title">🏦 Taxes</span></div>
                <div class="inv-card-body">
                    <div class="form-group">
                        <label>TVA globale (%)</label>
                        <select name="tax_rate" id="tax_rate" class="form-select">
                            @foreach($tax_rates as $rate)
                                <option value="{{ $rate }}" {{ $rate == config('invoice.tax.default_rate') ? 'selected' : '' }}>
                                    {{ $rate }} %
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if(config('invoice.withholding_tax.enabled'))
                    <div class="form-group" style="margin-top:12px">
                        <label>
                            Retenue à la source (%)
                            <span title="{{ __('invoice::invoices.withholding.tooltip') }}"
                                  style="cursor:help;color:var(--c-warning)">ⓘ</span>
                        </label>
                        <select name="withholding_tax_rate" id="withholding_tax_rate" class="form-select">
                            @foreach(config('invoice.withholding_tax.rates') as $r)
                                <option value="{{ $r['value'] }}">{{ $r['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Totaux --}}
            <div class="inv-card">
                <div class="inv-card-header"><span class="inv-card-title">🧮 Récapitulatif</span></div>
                <div class="totals-panel">
                    <div class="totals-row subtotal">
                        <span class="totals-label">Sous-total HT</span>
                        <span class="totals-value" id="total-subtotal">0,00</span>
                    </div>
                    <div class="totals-row discount">
                        <span class="totals-label">Remise</span>
                        <span class="totals-value" id="total-discount">0,00</span>
                    </div>
                    <div class="totals-row tax">
                        <span class="totals-label">TVA</span>
                        <span class="totals-value" id="total-tax">0,00</span>
                    </div>
                    @if(config('invoice.withholding_tax.enabled'))
                    <div class="totals-row withholding">
                        <span class="totals-label">Retenue à la source</span>
                        <span class="totals-value" id="total-withholding">0,00</span>
                    </div>
                    @endif
                    <div class="totals-row grand-total">
                        <span class="totals-label">Total TTC</span>
                        <span class="totals-value" id="total-grand">0,00</span>
                    </div>
                    @if(config('invoice.withholding_tax.enabled'))
                    <div class="withholding-info" id="withholding-info" style="display:none">
                        ⚠ Net à payer : <strong id="total-net">0,00</strong>
                        (après retenue à la source)
                    </div>
                    @endif
                </div>
            </div>
        </div>

    </div>{{-- /invoice-builder --}}
</form>
@endsection

@push('scripts')
<script>
    const INVOICE_CURRENCIES      = @json($currencies);
    const DEFAULT_CURRENCY        = '{{ config('crm-core.formats.currency','EUR') }}';
    const WITHHOLDING_COUNTRIES   = @json(config('invoice.withholding_tax.countries', []));
    const DEFAULT_WITHHOLDING_RATE = {{ config('invoice.withholding_tax.default_rate', 0) }};
    const BASE_CURRENCY = DEFAULT_CURRENCY;

    document.addEventListener('DOMContentLoaded', () => {
        InvoiceForm.init({
            currency:        DEFAULT_CURRENCY,
            taxRate:         {{ config('invoice.tax.default_rate', 20) }},
            withholdingRate: 0,
            discountType:    'none',
            discountValue:   0,
        });

        document.getElementById('add-line-btn-bottom')?.addEventListener('click', () => LineItems.addItem());
    });
</script>
@endpush
