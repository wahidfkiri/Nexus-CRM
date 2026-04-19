@extends('invoice::layouts.invoice')

@section('title', 'Paiements')

@section('breadcrumb')
  <span>Facturation</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Paiements</span>
@endsection

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Paiements</h1>
    <p>Historique et suivi de tous les paiements reçus</p>
  </div>
  <div class="page-header-actions">
    <div class="dropdown">
      <button class="btn btn-secondary" data-dropdown-toggle>
        <i class="fas fa-arrow-down-to-line"></i> Exporter
        <i class="fas fa-chevron-down" style="font-size:10px;margin-left:2px;"></i>
      </button>
      <div class="dropdown-menu">
        <a href="{{ route('invoices.payments.export.csv') }}"   class="dropdown-item"><i class="fas fa-file-csv"></i>   CSV</a>
        <a href="{{ route('invoices.payments.export.excel') }}" class="dropdown-item"><i class="fas fa-file-excel"></i> Excel</a>
      </div>
    </div>
  </div>
</div>

{{-- Stats --}}
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-money-bill-wave"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statPTotal">—</div>
      <div class="stat-label">Total encaissé</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-calendar-check"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statPMonth">—</div>
      <div class="stat-label">Ce mois</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-info-lt);color:var(--c-info)"><i class="fas fa-credit-card"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statPCount">—</div>
      <div class="stat-label">Nb paiements</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-building-columns"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statPTransfer">—</div>
      <div class="stat-label">Par virement</div>
    </div>
  </div>
</div>

{{-- Table --}}
<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Historique des paiements</span>
    <span class="table-count" id="payCount">—</span>
    <div class="table-spacer"></div>

    <div class="table-search">
      <i class="fas fa-search"></i>
      <input type="text" id="searchInput" placeholder="Référence, facture, client…" autocomplete="off">
    </div>

    <select class="filter-select" data-filter="payment_method">
      <option value="">Tous modes</option>
      @foreach(config('invoice.payment_methods') as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>

    <input type="date" class="filter-select" data-filter="date_from" style="width:140px" title="Du">
    <input type="date" class="filter-select" data-filter="date_to"   style="width:140px" title="Au">

    <button class="btn btn-ghost btn-sm" id="resetFilters">
      <i class="fas fa-rotate-left"></i>
    </button>
  </div>

  <table class="crm-table" id="paymentsTable">
    <thead>
      <tr>
        <th data-sort="payment_date" class="sortable">Date <i class="fas fa-sort" style="font-size:10px;opacity:.4"></i></th>
        <th>Facture</th>
        <th>Client</th>
        <th data-sort="payment_method" class="sortable">Mode</th>
        <th>Référence</th>
        <th>Banque</th>
        <th data-sort="amount" class="sortable" style="text-align:right">Montant</th>
        <th style="text-align:right;padding-right:20px">Actions</th>
      </tr>
    </thead>
    <tbody id="paymentsTableBody">
      {{-- AJAX --}}
    </tbody>
  </table>

  <div class="table-pagination">
    <span class="pagination-info" id="paginationInfo"></span>
    <div class="pagination-spacer"></div>
    <div class="pagination-pages" id="paginationControls"></div>
  </div>
</div>

@endsection

@push('scripts')
<script>
window.INVOICE_CURRENCIES = @json(config('invoice.currencies'));

document.addEventListener('DOMContentLoaded', () => {
  window._payTable = new InvTable({
    tbodyId: 'paymentsTableBody',
    dataUrl: '{{ route("invoices.payments.data") }}',
    statsUrl:'{{ route("invoices.payments.stats") }}',
    mode:    'payment',
    countEl: 'payCount',
    statsMap:{ total:'statPTotal', month:'statPMonth', count:'statPCount', transfer:'statPTransfer' }
  });
});
</script>
@endpush