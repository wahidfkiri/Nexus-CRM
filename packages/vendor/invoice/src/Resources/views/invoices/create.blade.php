@extends('invoice::layouts.invoice')

@section('title', 'Nouvelle facture')

@section('breadcrumb')
  <a href="{{ route('invoices.index') }}">Factures</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Nouvelle facture</span>
@endsection

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Nouvelle facture</h1>
    <p>Créez et configurez votre facture client</p>
  </div>
  <a href="{{ route('invoices.index') }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> Retour
  </a>
</div>

<form id="invoiceForm" action="{{ route('invoices.store') }}" method="POST">
  @csrf

  <div class="invoice-builder-layout">

    {{-- ── COLONNE PRINCIPALE ── --}}
    <div>

      {{-- Informations générales --}}
      <div class="form-section">
        <h3 class="form-section-title">
          <i class="fas fa-file-invoice"></i> Informations générales
          <span class="form-section-badge">Étape 1/4</span>
        </h3>
        <div class="row">
          <div class="col-8">
            <div class="form-group">
              <label class="form-label">Client <span class="required">*</span></label>
              <div class="client-select-wrap">
                <div class="input-group">
                  <i class="fas fa-search input-icon"></i>
                  <input type="text" id="clientSearch" class="form-control" placeholder="Rechercher un client…" autocomplete="off">
                </div>
                <input type="hidden" name="client_id" id="clientId">
                <div id="clientSuggestions" class="client-suggestions" style="display:none;"></div>
              </div>
              <div id="clientSelected" style="display:none;margin-top:8px;background:var(--c-accent-xl);border-radius:var(--r-sm);padding:10px 14px;display:flex;align-items:center;gap:10px;">
                <div class="client-avatar-sm" id="clientInitials">?</div>
                <div style="flex:1">
                  <div style="font-weight:var(--fw-medium);font-size:13px" id="clientName"></div>
                  <div style="font-size:12px;color:var(--c-ink-40)" id="clientEmail"></div>
                </div>
                <button type="button" class="btn-icon btn-sm" onclick="clearClient()" title="Changer"><i class="fas fa-times"></i></button>
              </div>
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">Référence interne</label>
              <input type="text" name="reference" class="form-control" placeholder="PO-2024-001">
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">Date d'émission <span class="required">*</span></label>
              <input type="date" name="issue_date" id="issue_date" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">Conditions de paiement</label>
              <select name="payment_terms" id="payment_terms" class="form-control">
                @foreach($payment_terms as $days => $label)
                  <option value="{{ $days }}" {{ $days == 30 ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">Date d'échéance</label>
              <input type="date" name="due_date" id="due_date" class="form-control">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Mode de paiement</label>
              <select name="payment_method" class="form-control">
                <option value="">— Sélectionner —</option>
                @foreach($payment_methods as $key => $label)
                  <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Devise <span class="required">*</span></label>
              <select name="currency" id="currency" class="form-control">
                @foreach($currencies as $code => $cfg)
                  <option value="{{ $code }}" {{ $code === 'EUR' ? 'selected' : '' }}>
                    {{ $code }} — {{ $cfg['name'] }} ({{ $cfg['symbol'] }})
                  </option>
                @endforeach
              </select>
              <input type="hidden" name="exchange_rate" id="exchange_rate" value="1">
            </div>
          </div>
        </div>
      </div>

      {{-- Lignes de facture --}}
      <div class="form-section" style="padding:0;overflow:hidden;">
        <h3 class="form-section-title" style="margin:0;padding:20px 28px 16px;border-radius:0;border-bottom:1px solid var(--c-ink-05);">
          <i class="fas fa-list"></i> Lignes de facture
          <span class="form-section-badge">Étape 2/4</span>
          <button type="button" class="btn btn-ghost btn-sm" onclick="InvLineItems.addLine()" style="margin-left:auto;color:var(--c-accent);">
            <i class="fas fa-plus"></i> Ajouter une ligne
          </button>
        </h3>
        <div class="line-items-overflow">
          <table class="line-items-table">
            <thead>
              <tr>
                <th style="width:20px"></th>
                <th>Description</th>
                <th style="width:90px">Qté</th>
                <th style="width:70px">Unité</th>
                <th style="width:120px">Prix unit. HT</th>
                <th style="width:110px">Remise</th>
                <th style="width:80px">TVA %</th>
                <th style="width:110px;text-align:right">Total TTC</th>
                <th style="width:36px"></th>
              </tr>
            </thead>
            <tbody id="lineItemsBody"></tbody>
          </table>
        </div>
        <button type="button" class="add-line-btn" onclick="InvLineItems.addLine()">
          <i class="fas fa-plus"></i> Ajouter une ligne
        </button>
      </div>

      {{-- Notes --}}
      <div class="form-section">
        <h3 class="form-section-title">
          <i class="fas fa-note-sticky"></i> Notes & Conditions
          <span class="form-section-badge">Étape 4/4</span>
        </h3>
        <div class="row">
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Notes <span class="hint">(visibles par le client)</span></label>
              <textarea name="notes" class="form-control" rows="3" placeholder="Informations de paiement, instructions…"></textarea>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Conditions générales</label>
              <textarea name="terms" class="form-control" rows="3" placeholder="Conditions de vente, mentions légales…"></textarea>
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Notes internes <span class="hint">(non visibles)</span></label>
              <textarea name="internal_notes" class="form-control" rows="2" placeholder="Commentaires internes à votre équipe…"></textarea>
            </div>
          </div>
        </div>
      </div>

    </div>

    {{-- ── SIDEBAR ── --}}
    <div>

      {{-- Actions --}}
      <div class="form-section" style="margin-bottom:16px;">
        <div style="display:flex;flex-direction:column;gap:10px;">
          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
            <i class="fas fa-check"></i> Enregistrer la facture
          </button>
          <a href="{{ route('invoices.index') }}" class="btn btn-secondary" style="width:100%;justify-content:center;">
            <i class="fas fa-times"></i> Annuler
          </a>
        </div>
      </div>

      {{-- Remise globale --}}
      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title">
          <i class="fas fa-percent"></i> Remise globale
          <span class="form-section-badge">Étape 3/4</span>
        </h3>
        <div class="form-group">
          <label class="form-label">Type de remise</label>
          <select name="discount_type" id="discount_type" class="form-control">
            <option value="none">Aucune</option>
            @foreach($discount_types as $key => $label)
              <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group" id="discountValueGroup" style="display:none;">
          <label class="form-label">Valeur</label>
          <input type="number" name="discount_value" id="discount_value" class="form-control" value="0" min="0" step="any">
        </div>
      </div>

      {{-- Taxes --}}
      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title">
          <i class="fas fa-building-columns"></i> Taxes
        </h3>
        <div class="form-group">
          <label class="form-label">TVA globale (%)</label>
          <select name="tax_rate" id="tax_rate" class="form-control">
            @foreach($tax_rates as $rate)
              <option value="{{ $rate }}" {{ $rate == config('invoice.tax.default_rate', 20) ? 'selected' : '' }}>{{ $rate }} %</option>
            @endforeach
          </select>
        </div>
        @if(config('invoice.withholding_tax.enabled'))
        <div class="form-group">
          <label class="form-label">
            Retenue à la source (%)
            <span class="hint" title="Applicable dans certains pays (TN, MA, DZ…)">ⓘ</span>
          </label>
          <select name="withholding_tax_rate" id="withholding_tax_rate" class="form-control">
            @foreach(config('invoice.withholding_tax.rates') as $r)
              <option value="{{ $r['value'] }}">{{ $r['label'] }}</option>
            @endforeach
          </select>
        </div>
        @endif
      </div>

      {{-- Récapitulatif --}}
      <div class="form-section" style="margin-bottom:0;">
        <h3 class="form-section-title">
          <i class="fas fa-calculator"></i> Récapitulatif
        </h3>
        <div class="totals-panel">
          <div class="totals-row">
            <span class="totals-label">Sous-total HT</span>
            <span class="totals-value" id="tot-subtotal">0,00 €</span>
          </div>
          <div class="totals-row discount" id="tot-discount-row" style="display:none;">
            <span class="totals-label">Remise</span>
            <span class="totals-value" id="tot-discount">— €</span>
          </div>
          <div class="totals-row">
            <span class="totals-label">TVA</span>
            <span class="totals-value" id="tot-tax">0,00 €</span>
          </div>
          @if(config('invoice.withholding_tax.enabled'))
          <div class="totals-row" id="tot-withholding-row" style="display:none;">
            <span class="totals-label">Retenue à la source</span>
            <span class="totals-value" id="tot-withholding">0,00 €</span>
          </div>
          @endif
          <div class="totals-row grand-total">
            <span class="totals-label">Total TTC</span>
            <span class="totals-value" id="tot-grand">0,00 €</span>
          </div>
          <div class="withholding-info" id="withholding-info" style="display:none;">
            <i class="fas fa-circle-info"></i>
            Net à payer après retenue : <strong id="tot-net">0,00 €</strong>
          </div>
        </div>
      </div>

    </div>
  </div>
</form>

@endsection

@push('scripts')
<script>
window.INVOICE_CURRENCIES    = @json($currencies);
window.DEFAULT_CURRENCY      = '{{ 'EUR' }}';
window.WITHHOLDING_COUNTRIES = @json(config('invoice.withholding_tax.countries', []));

document.addEventListener('DOMContentLoaded', () => {
  // Date auto-calc
  const issueDate = document.getElementById('issue_date');
  const terms     = document.getElementById('payment_terms');
  const dueDate   = document.getElementById('due_date');
  function calcDue() {
    if (!issueDate.value || !terms.value) return;
    const d = new Date(issueDate.value);
    d.setDate(d.getDate() + parseInt(terms.value));
    dueDate.value = d.toISOString().split('T')[0];
  }
  issueDate.addEventListener('change', calcDue);
  terms.addEventListener('change', calcDue);
  calcDue();

  // Discount type toggle
  document.getElementById('discount_type').addEventListener('change', function() {
    document.getElementById('discountValueGroup').style.display = this.value !== 'none' ? 'block' : 'none';
    InvLineItems.recalc();
  });

  // Tax/withholding change
  document.getElementById('tax_rate')?.addEventListener('change', () => InvLineItems.recalc());
  document.getElementById('withholding_tax_rate')?.addEventListener('change', () => InvLineItems.recalc());
  document.getElementById('discount_value')?.addEventListener('input', () => InvLineItems.recalc());
  document.getElementById('currency')?.addEventListener('change', () => InvLineItems.recalc());

  // Init line items
  InvLineItems.init({ currency: 'EUR', defaultTaxRate: {{ config('invoice.tax.default_rate', 20) }} });

  // Init client search
  InvClientSearch.init('clientSearch', 'clientId', {
    suggestionsEl: 'clientSuggestions',
    onSelect: (c) => {
      document.getElementById('clientSearch').style.display = 'none';
      const sel = document.getElementById('clientSelected');
      sel.style.display = 'flex';
      document.getElementById('clientInitials').textContent = (c.company_name||'?').substring(0,2).toUpperCase();
      document.getElementById('clientName').textContent = c.company_name;
      document.getElementById('clientEmail').textContent = c.email || '';
    }
  });

  // AJAX form
  ajaxForm('invoiceForm');
});

function clearClient() {
  document.getElementById('clientId').value = '';
  document.getElementById('clientSearch').style.display = '';
  document.getElementById('clientSearch').value = '';
  document.getElementById('clientSelected').style.display = 'none';
}
</script>
@endpush