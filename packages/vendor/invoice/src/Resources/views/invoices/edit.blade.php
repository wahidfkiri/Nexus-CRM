@extends('invoice::layouts.invoice')

@section('title', 'Modifier ' . $invoice->number)

@section('breadcrumb')
  <a href="{{ route('invoices.index') }}">Factures</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->number }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Modifier</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <h1>Modifier {{ $invoice->number }}</h1>
    <p>Mettez à jour la facture, ses lignes et ses conditions</p>
  </div>
  <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
</div>

<form id="invoiceForm" action="{{ route('invoices.update', $invoice) }}" method="POST">
  @csrf
  @method('PUT')

  <div class="invoice-builder-layout">
    <div>
      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-file-invoice"></i> Informations générales</h3>
        <div class="row">
          <div class="col-8">
            <div class="form-group">
              <label class="form-label">Client <span class="required">*</span></label>
              <div class="client-select-wrap">
                <div class="input-group" id="clientSearchWrap" style="display:none;">
                  <i class="fas fa-search input-icon"></i>
                  <input type="text" id="clientSearch" class="form-control" placeholder="Rechercher un client…" autocomplete="off">
                </div>
                <input type="hidden" name="client_id" id="clientId" value="{{ $invoice->client_id }}">
                <div id="clientSuggestions" class="client-suggestions" style="display:none;"></div>
              </div>
              <div id="clientSelected" style="margin-top:8px;background:var(--c-accent-xl);border-radius:var(--r-sm);padding:10px 14px;display:flex;align-items:center;gap:10px;">
                <div class="client-avatar-sm" id="clientInitials">{{ strtoupper(substr($invoice->client->company_name ?? 'C', 0, 2)) }}</div>
                <div style="flex:1">
                  <div style="font-weight:var(--fw-medium);font-size:13px" id="clientName">{{ $invoice->client->company_name }}</div>
                  <div style="font-size:12px;color:var(--c-ink-40)" id="clientEmail">{{ $invoice->client->email }}</div>
                </div>
                <button type="button" class="btn-icon btn-sm" onclick="clearClient()" title="Changer"><i class="fas fa-times"></i></button>
              </div>
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">Référence</label>
              <input type="text" name="reference" class="form-control" value="{{ $invoice->reference }}">
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">Date d'émission <span class="required">*</span></label>
              <input type="date" name="issue_date" id="issue_date" class="form-control" value="{{ optional($invoice->issue_date)->format('Y-m-d') }}" required>
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">Conditions de paiement</label>
              <select name="payment_terms" id="payment_terms" class="form-control">
                @foreach($payment_terms as $days => $label)
                  <option value="{{ $days }}" {{ (int)$invoice->payment_terms === (int)$days ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">Date d'échéance</label>
              <input type="date" name="due_date" id="due_date" class="form-control" value="{{ optional($invoice->due_date)->format('Y-m-d') }}">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Mode de paiement</label>
              <select name="payment_method" class="form-control">
                <option value="">— Sélectionner —</option>
                @foreach($payment_methods as $key => $label)
                  <option value="{{ $key }}" {{ $invoice->payment_method === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Devise <span class="required">*</span></label>
              <select name="currency" id="currency" class="form-control" required>
                @foreach($currencies as $code => $cfg)
                  <option value="{{ $code }}" {{ $invoice->currency === $code ? 'selected' : '' }}>{{ $code }} — {{ $cfg['name'] }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Taux de change</label>
              <input type="number" name="exchange_rate" class="form-control" step="any" min="0.000001" value="{{ $invoice->exchange_rate ?? 1 }}">
            </div>
          </div>
        </div>
      </div>

      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-list"></i> Lignes</h3>
        <div class="line-items-overflow">
          <table class="line-items-table">
            <thead>
              <tr>
                <th style="width:20px"></th>
                <th>Description</th>
                <th style="width:90px">Qté</th>
                <th style="width:70px">Unité</th>
                <th style="width:120px">Prix HT</th>
                <th style="width:110px">Remise</th>
                <th style="width:80px">TVA %</th>
                <th style="width:110px;text-align:right">Total</th>
                <th style="width:36px"></th>
              </tr>
            </thead>
            <tbody id="lineItemsBody"></tbody>
          </table>
        </div>
        <button type="button" class="add-line-btn" onclick="InvLineItems.addLine()"><i class="fas fa-plus"></i> Ajouter une ligne</button>
      </div>

      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-note-sticky"></i> Notes</h3>
        <div class="row">
          <div class="col-6"><div class="form-group"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3">{{ $invoice->notes }}</textarea></div></div>
          <div class="col-6"><div class="form-group"><label class="form-label">Conditions</label><textarea name="terms" class="form-control" rows="3">{{ $invoice->terms }}</textarea></div></div>
          <div class="col-12"><div class="form-group"><label class="form-label">Notes internes</label><textarea name="internal_notes" class="form-control" rows="2">{{ $invoice->internal_notes }}</textarea></div></div>
        </div>
      </div>
    </div>

    <div>
      <div class="form-section" style="margin-bottom:16px;">
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-check"></i> Enregistrer</button>
      </div>

      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title"><i class="fas fa-percent"></i> Remise globale</h3>
        <div class="form-group">
          <label class="form-label">Type</label>
          <select name="discount_type" id="discount_type" class="form-control">
            <option value="none" {{ $invoice->discount_type === 'none' ? 'selected' : '' }}>Aucune</option>
            @foreach($discount_types as $key => $label)
              <option value="{{ $key }}" {{ $invoice->discount_type === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group" id="discountValueGroup" style="{{ $invoice->discount_type === 'none' ? 'display:none;' : '' }}">
          <label class="form-label">Valeur</label>
          <input type="number" name="discount_value" id="discount_value" class="form-control" value="{{ $invoice->discount_value ?? 0 }}" min="0" step="any">
        </div>
      </div>

      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title"><i class="fas fa-building-columns"></i> Taxes</h3>
        <div class="form-group">
          <label class="form-label">TVA globale (%)</label>
          <select name="tax_rate" id="tax_rate" class="form-control">
            @foreach($tax_rates as $rate)
              <option value="{{ $rate }}" {{ (float)$invoice->tax_rate === (float)$rate ? 'selected' : '' }}>{{ $rate }} %</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Retenue à la source (%)</label>
          <select name="withholding_tax_rate" id="withholding_tax_rate" class="form-control">
            @foreach($withholding_rates as $r)
              <option value="{{ is_array($r) ? $r['value'] : $r }}" {{ (float)$invoice->withholding_tax_rate === (float)(is_array($r) ? $r['value'] : $r) ? 'selected' : '' }}>
                {{ is_array($r) ? $r['label'] : $r.'%' }}
              </option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-calculator"></i> Totaux</h3>
        <div class="totals-panel">
          <div class="totals-row"><span class="totals-label">Sous-total</span><span class="totals-value" id="tot-subtotal">0,00 €</span></div>
          <div class="totals-row discount" id="tot-discount-row" style="display:none;"><span class="totals-label">Remise</span><span class="totals-value" id="tot-discount">0,00 €</span></div>
          <div class="totals-row"><span class="totals-label">TVA</span><span class="totals-value" id="tot-tax">0,00 €</span></div>
          <div class="totals-row" id="tot-withholding-row" style="display:none;"><span class="totals-label">Retenue</span><span class="totals-value" id="tot-withholding">0,00 €</span></div>
          <div class="totals-row grand-total"><span class="totals-label">Total</span><span class="totals-value" id="tot-grand">0,00 €</span></div>
          <div class="withholding-info" id="withholding-info" style="display:none;">Net à payer : <strong id="tot-net">0,00 €</strong></div>
        </div>
      </div>
    </div>
  </div>
</form>
@endsection

@push('scripts')
<script>
window.INVOICE_CURRENCIES = @json($currencies);
const existingItems = @json($invoice->items->map(function ($item) {
  return [
    'description' => $item->description,
    'reference' => $item->reference,
    'quantity' => (float) $item->quantity,
    'unit' => $item->unit,
    'unit_price' => (float) $item->unit_price,
    'discount_type' => $item->discount_type ?? 'none',
    'discount_value' => (float) $item->discount_value,
    'tax_rate' => (float) $item->tax_rate,
  ];
})->values());

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('discount_type').addEventListener('change', function () {
    document.getElementById('discountValueGroup').style.display = this.value !== 'none' ? 'block' : 'none';
    InvLineItems.recalc();
  });
  document.getElementById('tax_rate')?.addEventListener('change', () => InvLineItems.recalc());
  document.getElementById('withholding_tax_rate')?.addEventListener('change', () => InvLineItems.recalc());
  document.getElementById('discount_value')?.addEventListener('input', () => InvLineItems.recalc());
  document.getElementById('currency')?.addEventListener('change', () => InvLineItems.recalc());

  InvLineItems.init({
    currency: '{{ $invoice->currency }}',
    defaultTaxRate: {{ (float) $invoice->tax_rate }},
    withholdingRate: {{ (float) $invoice->withholding_tax_rate }},
    items: existingItems
  });

  InvClientSearch.init('clientSearch', 'clientId', {
    suggestionsEl: 'clientSuggestions',
    onSelect: (c) => {
      document.getElementById('clientSearchWrap').style.display = 'none';
      document.getElementById('clientSelected').style.display = 'flex';
      document.getElementById('clientInitials').textContent = (c.company_name || '?').substring(0, 2).toUpperCase();
      document.getElementById('clientName').textContent = c.company_name;
      document.getElementById('clientEmail').textContent = c.email || '';
    }
  });

  ajaxForm('invoiceForm');
});

function clearClient() {
  document.getElementById('clientId').value = '';
  document.getElementById('clientSearchWrap').style.display = '';
  document.getElementById('clientSearch').value = '';
  document.getElementById('clientSelected').style.display = 'none';
}
</script>
@endpush
