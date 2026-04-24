@extends('invoice::layouts.invoice')

@section('title', 'Factures')

@section('breadcrumb')
  <span>Facturation</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Factures</span>
@endsection

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Factures</h1>
    <p>Gérez et suivez l'ensemble de vos factures</p>
  </div>
  <div class="page-header-actions">
    <div class="dropdown">
      <button class="btn btn-secondary" data-dropdown-toggle>
        <i class="fas fa-arrow-down-to-line"></i> Exporter
        <i class="fas fa-chevron-down" style="font-size:10px;margin-left:2px;"></i>
      </button>
      <div class="dropdown-menu">
        <a href="{{ route('invoices.export.csv') }}"   class="dropdown-item"><i class="fas fa-file-csv"></i>   CSV</a>
        <a href="{{ route('invoices.export.excel') }}" class="dropdown-item"><i class="fas fa-file-excel"></i> Excel</a>
        <a href="{{ route('invoices.export.pdf') }}"   class="dropdown-item"><i class="fas fa-file-pdf"></i>   PDF</a>
      </div>
    </div>
    <button class="btn btn-secondary" data-modal-open="importModal">
      <i class="fas fa-arrow-up-from-line"></i> Importer
    </button>
    <a href="{{ route('invoices.quotes.create') }}" class="btn btn-secondary">
      <i class="fas fa-file-signature"></i> Nouveau devis
    </a>
    <a href="{{ route('invoices.create') }}" class="btn btn-primary">
      <i class="fas fa-plus"></i> Nouvelle facture
    </a>
  </div>
</div>

@if(!empty($marketplaceSuggestions))
  <div class="module-app-suggestions">
    @foreach($marketplaceSuggestions as $suggestion)
      <article class="module-app-suggestion-card">
        <div class="module-app-suggestion-icon">
          <i class="{{ $suggestion['icon'] ?? 'fas fa-puzzle-piece' }}"></i>
        </div>
        <div class="module-app-suggestion-body">
          <h3>{{ $suggestion['name'] ?? 'Application' }}</h3>
          <p>{{ $suggestion['description'] ?? '' }}</p>
        </div>
        <a href="{{ $suggestion['url'] ?? route('marketplace.index') }}" class="btn btn-secondary btn-sm">
          <i class="fas fa-store"></i> Installer
        </a>
      </article>
    @endforeach
  </div>
@endif

{{-- Stats --}}
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-file-invoice"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statTotal">—</div>
      <div class="stat-label">Total factures</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-circle-check"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statPaid">—</div>
      <div class="stat-label">Payées</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-danger-lt);color:var(--c-danger)"><i class="fas fa-clock-rotate-left"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statOverdue">—</div>
      <div class="stat-label">En retard</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#f3e8ff;color:#7c3aed"><i class="fas fa-circle-euro-sign"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statRevenue">—</div>
      <div class="stat-label">CA encaissé</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-hourglass-half"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statDue">—</div>
      <div class="stat-label">À encaisser</div>
    </div>
  </div>
</div>

{{-- Table --}}
<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Liste des factures</span>
    <span class="table-count" id="invCount">—</span>
    <div class="table-spacer"></div>

    <div class="table-search">
      <i class="fas fa-search"></i>
      <input type="text" id="searchInput" placeholder="Numéro, client, référence…" autocomplete="off">
    </div>

    <select class="filter-select" data-filter="status">
      <option value="">Tous les statuts</option>
      @foreach(config('invoice.invoice_statuses') as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>

    <select class="filter-select" data-filter="currency">
      <option value="">Toutes devises</option>
      @foreach(config('invoice.currencies') as $code => $cfg)
        <option value="{{ $code }}">{{ $code }}</option>
      @endforeach
    </select>

    <input type="date" class="filter-select" data-filter="date_from" style="width:140px" title="Du">
    <input type="date" class="filter-select" data-filter="date_to"   style="width:140px" title="Au">

    <button class="btn btn-ghost btn-sm" id="resetFilters" title="Réinitialiser">
      <i class="fas fa-rotate-left"></i>
    </button>
  </div>

  {{-- Bulk bar --}}
  <div class="bulk-bar" id="bulkBar">
    <span><strong id="selectedCount">0</strong> facture(s) sélectionnée(s)</span>
    <div class="bulk-bar-actions" style="display:flex;gap:6px;">
      <button class="btn btn-sm btn-secondary" onclick="bulkInvoiceAction('send')">
        <i class="fas fa-paper-plane"></i> Marquer envoyée
      </button>
      <button class="btn btn-sm btn-danger" onclick="bulkInvoiceAction('delete')">
        <i class="fas fa-trash"></i> Supprimer
      </button>
    </div>
  </div>

  <table class="crm-table" id="invoicesTable">
    <thead>
      <tr>
        <th style="width:40px"><input type="checkbox" id="selectAll"></th>
        <th data-sort="number" class="sortable">N° Facture <i class="fas fa-sort" style="font-size:10px;opacity:.4"></i></th>
        <th data-sort="client_id" class="sortable">Client</th>
        <th data-sort="issue_date" class="sortable">Émission</th>
        <th data-sort="due_date" class="sortable">Échéance</th>
        <th>Devise</th>
        <th data-sort="total" class="sortable" style="text-align:right">Total TTC</th>
        <th data-sort="amount_due" class="sortable" style="text-align:right">Reste dû</th>
        <th>Statut</th>
        <th style="text-align:right;padding-right:20px">Actions</th>
      </tr>
    </thead>
    <tbody id="invoicesTableBody">
      {{-- AJAX --}}
    </tbody>
  </table>

  <div class="table-pagination">
    <span class="pagination-info" id="paginationInfo"></span>
    <div class="pagination-spacer"></div>
    <div class="pagination-pages" id="paginationControls"></div>
  </div>
</div>

{{-- Import Modal --}}
<div class="modal-overlay" id="importModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:var(--c-accent-lt);color:var(--c-accent)">
        <i class="fas fa-file-import"></i>
      </div>
      <div>
        <div class="modal-title">Importer des factures</div>
        <div class="modal-subtitle">Formats : CSV, Excel (.xlsx, .xls)</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <form id="importForm" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
          <label class="form-label">Fichier d'import</label>
          <div id="dropzone" style="border:2px dashed var(--c-ink-10);border-radius:var(--r-md);padding:28px;text-align:center;cursor:pointer;transition:all var(--dur-fast);"
               onclick="document.getElementById('importFile').click()">
            <i class="fas fa-cloud-arrow-up" style="font-size:28px;color:var(--c-ink-20);margin-bottom:10px;display:block;"></i>
            <div style="font-size:14px;color:var(--c-ink-60);margin-bottom:4px;">Glissez votre fichier ou <span style="color:var(--c-accent)">parcourez</span></div>
            <div style="font-size:12px;color:var(--c-ink-40);" id="dropzoneText">CSV, XLSX jusqu'à 10 Mo</div>
          </div>
          <input type="file" id="importFile" name="file" accept=".csv,.xlsx,.xls" style="display:none" onchange="handleImportFile(this)">
        </div>
        <div style="background:var(--c-accent-xl);border-radius:var(--r-sm);padding:12px 14px;font-size:12.5px;color:var(--c-ink-60);">
          <i class="fas fa-info-circle" style="color:var(--c-accent)"></i>
          Utilisez le <a href="#" style="color:var(--c-accent)">modèle CSV</a> pour respecter le format.
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Annuler</button>
      <button class="btn btn-primary" id="importSubmitBtn" disabled onclick="submitImport()">
        <i class="fas fa-upload"></i> Importer
      </button>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
window.CRM_ROUTES = {
  data:       '{{ route("invoices.data") }}',
  stats:      '{{ route("invoices.stats") }}',
  bulkDelete: '{{ route("invoices.bulk.delete") }}',
  bulkSend:   '{{ route("invoices.bulk.send") }}',
  import:     '{{ route("invoices.import") }}',
};
window.INVOICE_CURRENCIES = @json(config('invoice.currencies'));
window.DEFAULT_CURRENCY   = '{{ config('crm-core.formats.currency','EUR') ?? 'EUR' }}';

document.addEventListener('DOMContentLoaded', () => {
  window._invTable = new InvTable({
    tbodyId:  'invoicesTableBody',
    dataUrl:  window.CRM_ROUTES.data,
    statsUrl: window.CRM_ROUTES.stats,
  });
});

function bulkInvoiceAction(action) {
  const ids = window._invTable?.getSelectedIds();
  if (!ids?.length) return;
  if (action === 'delete') {
    Modal.confirm({
      title: `Supprimer ${ids.length} facture(s) ?`,
      message: 'Cette action est irréversible.',
      confirmText: 'Supprimer',
      type: 'danger',
      onConfirm: async () => {
        const { ok, data } = await Http.post(window.CRM_ROUTES.bulkDelete, { ids });
        if (ok) { Toast.success('Succès', data.message); window._invTable?.load(); }
        else Toast.error('Erreur', data.message);
      }
    });
  } else if (action === 'send') {
    Http.post(window.CRM_ROUTES.bulkSend, { ids }).then(({ ok, data }) => {
      if (ok) { Toast.success('Succès', data.message); window._invTable?.load(); }
      else Toast.error('Erreur', data.message);
    });
  }
}

function handleImportFile(input) {
  const file = input.files[0];
  if (file) {
    document.getElementById('dropzoneText').textContent = `✓ ${file.name} (${(file.size/1024).toFixed(1)} Ko)`;
    document.getElementById('dropzoneText').style.color = 'var(--c-success)';
    document.getElementById('dropzone').style.borderColor = 'var(--c-success)';
    document.getElementById('importSubmitBtn').disabled = false;
  }
}

async function submitImport() {
  const btn  = document.getElementById('importSubmitBtn');
  const fData = new FormData(document.getElementById('importForm'));
  CrmForm.setLoading(btn, true);
  const { ok, data } = await Http.post(window.CRM_ROUTES.import, fData);
  CrmForm.setLoading(btn, false);
  if (ok) {
    Modal.close(document.getElementById('importModal'));
    Toast.success('Import réussi !', data.message);
    window._invTable?.load();
  } else {
    Toast.error("Erreur d'import", data.message);
  }
}
</script>
@endpush
