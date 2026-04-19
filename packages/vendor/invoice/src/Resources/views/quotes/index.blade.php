@extends('invoice::layouts.invoice')

@section('title', 'Devis')

@section('breadcrumb')
  <span>Facturation</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Devis</span>
@endsection

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Devis</h1>
    <p>Gérez vos propositions commerciales et convertissez-les en factures</p>
  </div>
  <div class="page-header-actions">
    <div class="dropdown">
      <button class="btn btn-secondary" data-dropdown-toggle>
        <i class="fas fa-arrow-down-to-line"></i> Exporter
        <i class="fas fa-chevron-down" style="font-size:10px;margin-left:2px;"></i>
      </button>
      <div class="dropdown-menu">
        <a href="{{ route('invoices.quotes.export.csv') }}"   class="dropdown-item"><i class="fas fa-file-csv"></i>   CSV</a>
        <a href="{{ route('invoices.quotes.export.excel') }}" class="dropdown-item"><i class="fas fa-file-excel"></i> Excel</a>
      </div>
    </div>
    <a href="{{ route('invoices.index') }}" class="btn btn-secondary">
      <i class="fas fa-file-invoice"></i> Factures
    </a>
    <a href="{{ route('invoices.quotes.create') }}" class="btn btn-primary">
      <i class="fas fa-plus"></i> Nouveau devis
    </a>
  </div>
</div>

{{-- Stats --}}
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:#f3e8ff;color:#7c3aed"><i class="fas fa-file-signature"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statQTotal">—</div>
      <div class="stat-label">Total devis</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-info-lt);color:var(--c-info)"><i class="fas fa-paper-plane"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statQSent">—</div>
      <div class="stat-label">Envoyés</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-handshake"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statQAccepted">—</div>
      <div class="stat-label">Acceptés</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-clock-rotate-left"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statQExpired">—</div>
      <div class="stat-label">Expirés</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-arrow-trend-up"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statQConversion">—</div>
      <div class="stat-label">Taux de conversion</div>
    </div>
  </div>
</div>

{{-- Table --}}
<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Liste des devis</span>
    <span class="table-count" id="quoteCount">—</span>
    <div class="table-spacer"></div>

    <div class="table-search">
      <i class="fas fa-search"></i>
      <input type="text" id="searchInput" placeholder="Numéro, client…" autocomplete="off">
    </div>

    <select class="filter-select" data-filter="status">
      <option value="">Tous les statuts</option>
      @foreach(config('invoice.quote_statuses') as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>

    <input type="date" class="filter-select" data-filter="date_from" style="width:140px" title="Du">
    <input type="date" class="filter-select" data-filter="date_to"   style="width:140px" title="Au">

    <button class="btn btn-ghost btn-sm" id="resetFilters">
      <i class="fas fa-rotate-left"></i>
    </button>
  </div>

  <table class="crm-table" id="quotesTable">
    <thead>
      <tr>
        <th style="width:40px"><input type="checkbox" id="selectAll"></th>
        <th data-sort="number" class="sortable">N° Devis <i class="fas fa-sort" style="font-size:10px;opacity:.4"></i></th>
        <th data-sort="client_id" class="sortable">Client</th>
        <th data-sort="issue_date" class="sortable">Émission</th>
        <th data-sort="valid_until" class="sortable">Valide jusqu'au</th>
        <th>Devise</th>
        <th data-sort="total" class="sortable" style="text-align:right">Total TTC</th>
        <th>Statut</th>
        <th style="text-align:right;padding-right:20px">Actions</th>
      </tr>
    </thead>
    <tbody id="quotesTableBody">
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
window.QUOTE_ROUTES = {
  data:  '{{ route("invoices.quotes.data") }}',
  stats: '{{ route("invoices.stats") }}',
};
window.INVOICE_CURRENCIES = @json(config('invoice.currencies'));

document.addEventListener('DOMContentLoaded', () => {
  window._quoteTable = new InvTable({
    tbodyId:  'quotesTableBody',
    dataUrl:  window.QUOTE_ROUTES.data,
    statsUrl: window.QUOTE_ROUTES.stats,
    mode:     'quote',
    countEl:  'quoteCount',
    statsMap: {
      total:    'statQTotal',
      sent:     'statQSent',
      accepted: 'statQAccepted',
      expired:  'statQExpired',
      conversion: 'statQConversion',
    }
  });
});

async function convertQuote(id, number) {
  Modal.confirm({
    title: `Convertir le devis ${number} ?`,
    message: 'Une nouvelle facture sera créée et le devis sera marqué comme accepté.',
    confirmText: 'Convertir en facture',
    type: 'danger',
    onConfirm: async () => {
      const { ok, data } = await Http.post(`/invoices/quotes/${id}/convert`, {});
      if (ok) { Toast.success('Converti !', data.message); setTimeout(() => window.location.href = data.redirect, 1000); }
      else Toast.error('Erreur', data.message);
    }
  });
}

async function deleteQuote(id) {
  Modal.confirm({
    title: 'Supprimer ce devis ?',
    message: 'Cette action est irréversible.',
    confirmText: 'Supprimer',
    type: 'danger',
    onConfirm: async () => {
      const { ok, data } = await Http.delete(`/invoices/quotes/${id}`);
      if (ok) { Toast.success('Supprimé', data.message); window._quoteTable?.load(); }
      else Toast.error('Erreur', data.message);
    }
  });
}
</script>
@endpush
