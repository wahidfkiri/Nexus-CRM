@extends('invoice::layouts.invoice')

@section('title', 'Devis ' . $quote->number)

@section('breadcrumb')
  <a href="{{ route('invoices.quotes.index') }}">Devis</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $quote->number }}</span>
@endsection

@php
  $canConvertQuote = $quote->canBeConvertedToInvoice();
  $convertBlockedReason = $quote->conversionBlockedReason();
@endphp

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <h1>{{ $quote->number }}</h1>
    <p>
      <span class="badge badge-{{ $quote->status }}"><span class="badge-dot" style="background:currentColor"></span>{{ $quote->status_label }}</span>
      <span style="margin-left:10px;color:var(--c-ink-40);">Émis le {{ optional($quote->issue_date)->format('d/m/Y') }}</span>
    </p>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('invoices.quotes.pdf', $quote) }}" class="btn btn-secondary"><i class="fas fa-file-pdf"></i> PDF</a>
    @if(!$quote->is_converted && !in_array($quote->status, ['declined']))
      @if($canConvertQuote)
        <button class="btn btn-success" onclick="convertQuote({{ $quote->id }}, '{{ $quote->number }}')"><i class="fas fa-arrow-right"></i> Convertir</button>
      @else
        <button class="btn btn-secondary" type="button" disabled title="{{ $convertBlockedReason }}"><i class="fas fa-lock"></i> Convertir</button>
      @endif
    @endif
    @if(!in_array($quote->status, ['accepted', 'declined']))
      <a href="{{ route('invoices.quotes.edit', $quote) }}" class="btn btn-primary"><i class="fas fa-pen"></i> Modifier</a>
    @endif
  </div>
</div>

@if(!$canConvertQuote && !$quote->is_converted)
  <div class="info-card" style="margin-bottom:16px;">
    <div class="info-card-header"><i class="fas fa-circle-info"></i><h3>Conversion indisponible</h3></div>
    <div class="info-card-body">
      <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">{{ $convertBlockedReason }}</p>
      @if(!in_array($quote->status, ['accepted', 'declined']))
        <a href="{{ route('invoices.quotes.edit', $quote) }}" class="btn btn-primary"><i class="fas fa-pen"></i> Modifier ce devis</a>
      @endif
    </div>
  </div>
@endif

<div class="row" style="align-items:flex-start;">
  <div class="col-8" style="padding:0 12px 0 0;">
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-list"></i><h3>Lignes du devis</h3></div>
      <table class="crm-table">
        <thead><tr><th>#</th><th>Description</th><th style="text-align:right">Qté</th><th style="text-align:right">PU</th><th style="text-align:right">TVA</th><th style="text-align:right">Total</th></tr></thead>
        <tbody>
          @foreach($quote->items as $i => $item)
            <tr>
              <td>{{ $i + 1 }}</td>
              <td>{{ $item->description }}</td>
              <td style="text-align:right">{{ number_format($item->quantity, 2, ',', ' ') }}</td>
              <td style="text-align:right">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
              <td style="text-align:right">{{ $item->tax_rate }}%</td>
              <td style="text-align:right">{{ number_format($item->total, 2, ',', ' ') }} {{ config('invoice.currencies.'.$quote->currency.'.symbol', $quote->currency) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
      <div style="display:flex;justify-content:flex-end;padding:18px;">
        <div style="width:280px;">
          <div class="totals-row"><span class="totals-label">Sous-total</span><span class="totals-value">{{ number_format($quote->subtotal, 2, ',', ' ') }}</span></div>
          <div class="totals-row"><span class="totals-label">TVA</span><span class="totals-value">{{ number_format($quote->tax_amount, 2, ',', ' ') }}</span></div>
          <div class="totals-row grand-total"><span class="totals-label">Total</span><span class="totals-value">{{ number_format($quote->total, 2, ',', ' ') }} {{ config('invoice.currencies.'.$quote->currency.'.symbol', $quote->currency) }}</span></div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-4" style="padding:0 0 0 12px;">
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-user"></i><h3>Client</h3></div>
      <div class="info-card-body">
        <div class="info-row"><span class="info-row-label">Société</span><span class="info-row-value">{{ $quote->client->company_name }}</span></div>
        <div class="info-row"><span class="info-row-label">Contact</span><span class="info-row-value">{{ $quote->client->contact_name }}</span></div>
        <div class="info-row"><span class="info-row-label">Email</span><span class="info-row-value">{{ $quote->client->email }}</span></div>
      </div>
    </div>

    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-circle-info"></i><h3>Détails</h3></div>
      <div class="info-card-body">
        <div class="info-row"><span class="info-row-label">Référence</span><span class="info-row-value">{{ $quote->reference ?? '—' }}</span></div>
        <div class="info-row"><span class="info-row-label">Validité</span><span class="info-row-value">{{ optional($quote->valid_until)->format('d/m/Y') ?? '—' }}</span></div>
        <div class="info-row"><span class="info-row-label">Devise</span><span class="info-row-value">{{ $quote->currency }}</span></div>
        @if($quote->invoice)
          <div class="info-row"><span class="info-row-label">Facture liée</span><span class="info-row-value"><a href="{{ route('invoices.show', $quote->invoice) }}">{{ $quote->invoice->number }}</a></span></div>
        @endif
      </div>
    </div>

    @if($quote->notes)
      <div class="info-card">
        <div class="info-card-header"><i class="fas fa-note-sticky"></i><h3>Notes</h3></div>
        <div class="info-card-body">{{ $quote->notes }}</div>
      </div>
    @endif
  </div>
</div>
@endsection

@push('scripts')
<script>
async function convertQuote(id, number) {
  Modal.confirm({
    title: `Convertir le devis ${number} ?`,
    message: 'Une facture sera créée automatiquement à partir de ce devis.',
    confirmText: 'Convertir',
    type: 'success',
    onConfirm: async () => {
      const { ok, data } = await Http.post(`/invoices/quotes/${id}/convert`, {});
      if (ok) {
        Toast.success('Converti', data.message || 'Devis converti en facture.');
        if (data.redirect) setTimeout(() => window.location.href = data.redirect, 800);
      } else {
        Toast.error('Erreur', data.message || 'Conversion impossible.');
      }
    }
  });
}
</script>
@endpush
