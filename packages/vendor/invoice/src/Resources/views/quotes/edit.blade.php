@extends('invoice::layouts.invoice')

@section('title', 'Modifier ' . $quote->number)

@section('breadcrumb')
  <a href="{{ route('invoices.quotes.index') }}">Devis</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <a href="{{ route('invoices.quotes.show', $quote) }}">{{ $quote->number }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Modifier</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <h1>Modifier {{ $quote->number }}</h1>
    <p>Mettez à jour le devis avant envoi ou conversion</p>
  </div>
  <a href="{{ route('invoices.quotes.show', $quote) }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
</div>

<form id="quoteForm" action="{{ route('invoices.quotes.update', $quote) }}" method="POST">
  @csrf
  @method('PUT')
  <div class="invoice-builder-layout">
    <div>
      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-file-signature"></i> Informations</h3>
        <div class="row">
          <div class="col-8">
            <div class="form-group">
              <label class="form-label">Client <span class="required">*</span></label>
              <input type="hidden" name="client_id" id="clientId" value="{{ $quote->client_id }}">
              <input type="text" class="form-control" value="{{ $quote->client->company_name }}" readonly>
            </div>
          </div>
          <div class="col-4"><div class="form-group"><label class="form-label">Référence</label><input type="text" name="reference" class="form-control" value="{{ $quote->reference }}"></div></div>
          <div class="col-4"><div class="form-group"><label class="form-label">Date d'émission</label><input type="date" name="issue_date" class="form-control" value="{{ optional($quote->issue_date)->format('Y-m-d') }}"></div></div>
          <div class="col-4"><div class="form-group"><label class="form-label">Valide jusqu'au</label><input type="date" name="valid_until" class="form-control" value="{{ optional($quote->valid_until)->format('Y-m-d') }}"></div></div>
          <div class="col-4"><div class="form-group"><label class="form-label">Devise</label><select name="currency" id="currency" class="form-control">@foreach($currencies as $code => $cfg)<option value="{{ $code }}" {{ $quote->currency === $code ? 'selected' : '' }}>{{ $code }} — {{ $cfg['name'] }}</option>@endforeach</select></div></div>
          <div class="col-12"><div class="form-group"><label class="form-label">Taux de change</label><input type="number" name="exchange_rate" class="form-control" step="any" min="0.000001" value="{{ $quote->exchange_rate ?? 1 }}"></div></div>
        </div>
      </div>

      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-list"></i> Lignes</h3>
        <div class="line-items-overflow">
          <table class="line-items-table">
            <thead><tr><th style="width:20px"></th><th>Description</th><th style="width:90px">Qté</th><th style="width:70px">Unité</th><th style="width:120px">Prix HT</th><th style="width:110px">Remise</th><th style="width:80px">TVA %</th><th style="width:110px;text-align:right">Total</th><th style="width:36px"></th></tr></thead>
            <tbody id="lineItemsBody"></tbody>
          </table>
        </div>
        <button type="button" class="add-line-btn" onclick="InvLineItems.addLine()"><i class="fas fa-plus"></i> Ajouter une ligne</button>
      </div>

      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-note-sticky"></i> Notes</h3>
        <div class="row">
          <div class="col-6"><div class="form-group"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3">{{ $quote->notes }}</textarea></div></div>
          <div class="col-6"><div class="form-group"><label class="form-label">Conditions</label><textarea name="terms" class="form-control" rows="3">{{ $quote->terms }}</textarea></div></div>
        </div>
      </div>
    </div>

    <div>
      <div class="form-section" style="margin-bottom:16px;"><button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-check"></i> Enregistrer</button></div>
      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title"><i class="fas fa-percent"></i> Remise globale</h3>
        <div class="form-group"><label class="form-label">Type</label><select name="discount_type" id="discount_type" class="form-control"><option value="none" {{ $quote->discount_type === 'none' ? 'selected' : '' }}>Aucune</option>@foreach($discount_types as $key => $label)<option value="{{ $key }}" {{ $quote->discount_type === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
        <div class="form-group" id="discountValueGroup" style="{{ $quote->discount_type === 'none' ? 'display:none;' : '' }}"><label class="form-label">Valeur</label><input type="number" name="discount_value" id="discount_value" class="form-control" value="{{ $quote->discount_value ?? 0 }}" min="0" step="any"></div>
      </div>
      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title"><i class="fas fa-building-columns"></i> Taxes</h3>
        <div class="form-group"><label class="form-label">TVA globale (%)</label><select name="tax_rate" id="tax_rate" class="form-control">@foreach($tax_rates as $rate)<option value="{{ $rate }}" {{ (float)$quote->tax_rate === (float)$rate ? 'selected' : '' }}>{{ $rate }} %</option>@endforeach</select></div>
        <div class="form-group"><label class="form-label">Retenue à la source (%)</label><select name="withholding_tax_rate" id="withholding_tax_rate" class="form-control">@foreach($withholding_rates as $r)<option value="{{ is_array($r) ? $r['value'] : $r }}" {{ (float)$quote->withholding_tax_rate === (float)(is_array($r) ? $r['value'] : $r) ? 'selected' : '' }}>{{ is_array($r) ? $r['label'] : $r.'%' }}</option>@endforeach</select></div>
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
const existingItems = @json($quote->items->map(function ($item) {
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

  InvLineItems.init({
    currency: '{{ $quote->currency }}',
    defaultTaxRate: {{ (float) $quote->tax_rate }},
    withholdingRate: {{ (float) $quote->withholding_tax_rate }},
    items: existingItems
  });

  ajaxForm('quoteForm');
});
</script>
@endpush
