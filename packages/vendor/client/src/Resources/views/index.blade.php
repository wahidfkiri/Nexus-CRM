@extends('client::layouts.crm')

@section('title', 'Clients')

@section('breadcrumb')
  <span>CRM</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Clients</span>
@endsection

@section('content')

{{-- Page Header --}}
<div class="page-header">
  <div class="page-header-left">
    <h1>Clients</h1>
    <p>Gérez et suivez votre portefeuille clients</p>
  </div>
  <div class="page-header-actions">
    {{-- Export Dropdown --}}
    <div class="dropdown">
      <button class="btn btn-secondary" data-dropdown-toggle>
        <i class="fas fa-arrow-down-to-line"></i> Exporter
        <i class="fas fa-chevron-down" style="font-size:10px;margin-left:2px;"></i>
      </button>
      <div class="dropdown-menu">
        <a href="{{ route('clients.export.csv') }}"   class="dropdown-item"><i class="fas fa-file-csv"></i>   CSV</a>
        <a href="{{ route('clients.export.excel') }}" class="dropdown-item"><i class="fas fa-file-excel"></i> Excel</a>
        <a href="{{ route('clients.export.pdf') }}"   class="dropdown-item"><i class="fas fa-file-pdf"></i>   PDF</a>
      </div>
    </div>
    {{-- Import --}}
    <button class="btn btn-secondary" data-modal-open="importModal">
      <i class="fas fa-arrow-up-from-line"></i> Importer
    </button>
    {{-- New client --}}
    <a href="{{ route('clients.create') }}" class="btn btn-primary">
      <i class="fas fa-plus"></i> Nouveau client
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
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-users"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="totalClients">—</div>
      <div class="stat-label">Total clients</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-user-check"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="activeClients">—</div>
      <div class="stat-label">Clients actifs</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-clock"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="pendingClients">—</div>
      <div class="stat-label">En attente</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#f3e8ff;color:#7c3aed"><i class="fas fa-circle-euro-sign"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="totalRevenue">—</div>
      <div class="stat-label">Chiffre d'affaires</div>
    </div>
  </div>
</div>

{{-- Table --}}
<div class="table-wrapper">
  {{-- Table header with filters --}}
  <div class="table-header">
    <span class="table-title">Liste des clients</span>
    <span class="table-count">—</span>
    <div class="table-spacer"></div>

    {{-- Search --}}
    <div class="table-search">
      <i class="fas fa-search"></i>
      <input type="text" id="searchInput" placeholder="Rechercher un client…" autocomplete="off">
    </div>

    {{-- Filters --}}
    <select class="filter-select" data-filter="type">
      <option value="">Tous les types</option>
      @foreach($types as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>

    <select class="filter-select" data-filter="status">
      <option value="">Tous les statuts</option>
      @foreach($statuses as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>

    <select class="filter-select" data-filter="source">
      <option value="">Toutes les sources</option>
      @foreach($sources as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>

    <button class="btn btn-ghost btn-sm" id="resetFilters" title="Réinitialiser les filtres">
      <i class="fas fa-rotate-left"></i>
    </button>
  </div>

  {{-- Bulk actions bar --}}
  <div class="bulk-bar" id="bulkBar">
    <span><strong id="selectedCount">0</strong> client(s) sélectionné(s)</span>
    <div class="bulk-bar-actions">
      <button class="btn btn-sm btn-secondary" onclick="bulkStatus('actif')">
        <i class="fas fa-check-circle"></i> Activer
      </button>
      <button class="btn btn-sm btn-secondary" onclick="bulkStatus('inactif')">
        <i class="fas fa-ban"></i> Désactiver
      </button>
      <button class="btn btn-sm btn-danger" onclick="bulkDelete()">
        <i class="fas fa-trash"></i> Supprimer
      </button>
    </div>
  </div>

  {{-- Table --}}
  <table class="crm-table" id="clientsTable">
    <thead>
      <tr>
        <th style="width:40px"><input type="checkbox" id="selectAll"></th>
        <th data-sort="company_name" class="sortable">Client <i class="fas fa-sort" style="font-size:10px;opacity:.4"></i></th>
        <th>Type</th>
        <th data-sort="email" class="sortable">Email</th>
        <th>Téléphone</th>
        <th data-sort="status" class="sortable">Statut</th>
        <th data-sort="revenue" class="sortable">CA</th>
        <th style="text-align:right;padding-right:20px">Actions</th>
      </tr>
    </thead>
    <tbody id="clientsTableBody">
      {{-- Loaded via AJAX --}}
    </tbody>
  </table>

  {{-- Pagination --}}
  <div class="table-pagination">
    <span class="pagination-info" id="paginationInfo"></span>
    <div class="pagination-spacer"></div>
    <div class="pagination-pages" id="paginationControls"></div>
  </div>
</div>

{{-- ============================================================
     IMPORT MODAL
     ============================================================ --}}
<div class="modal-overlay" id="importModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:var(--c-accent-lt);color:var(--c-accent)">
        <i class="fas fa-file-import"></i>
      </div>
      <div>
        <div class="modal-title">Importer des clients</div>
        <div class="modal-subtitle">Formats acceptés : CSV, Excel (.xlsx, .xls)</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <form id="importForm" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
          <label class="form-label">Fichier d'import</label>
          <div style="border:2px dashed var(--c-ink-10);border-radius:var(--r-md);padding:28px;text-align:center;cursor:pointer;transition:all var(--dur-fast);"
               id="dropzone" onclick="document.getElementById('importFile').click()">
            <i class="fas fa-cloud-arrow-up" style="font-size:28px;color:var(--c-ink-20);margin-bottom:10px;display:block;"></i>
            <div style="font-size:14px;color:var(--c-ink-60);margin-bottom:4px;">Glissez votre fichier ici ou <span style="color:var(--c-accent)">cliquez pour parcourir</span></div>
            <div style="font-size:12px;color:var(--c-ink-40);" id="dropzoneText">CSV, XLSX jusqu'à 10 Mo</div>
          </div>
          <input type="file" id="importFile" name="file" accept=".csv,.xlsx,.xls" style="display:none" onchange="handleFileSelect(this)">
        </div>
        <div style="background:var(--c-accent-xl);border-radius:var(--r-sm);padding:12px 14px;font-size:12.5px;color:var(--c-ink-60);">
          <i class="fas fa-info-circle" style="color:var(--c-accent)"></i>
          Téléchargez le <a href="{{ route('clients.import.template') }}" style="color:var(--c-accent)">modèle CSV</a> pour respecter le format attendu.
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
// Routes available globally
window.CRM_ROUTES = {
  data:        '{{ route("clients.data") }}',
  stats:       '{{ route("clients.stats") }}',
  create:      '{{ route("clients.create") }}',
  bulkDelete:  '{{ route("clients.bulk.delete") }}',
  bulkStatus:  '{{ route("clients.bulk.status") }}',
};

// Init table
document.addEventListener('DOMContentLoaded', () => {
  window._crmTable = new CrmTable({
    tableId:  'clientsTable',
    tbodyId:  'clientsTableBody',
    dataUrl:  window.CRM_ROUTES.data,
    statsUrl: window.CRM_ROUTES.stats,
    perPage:  15,
  });
});

// Import file select
function handleFileSelect(input) {
  const file = input.files[0];
  const btn  = document.getElementById('importSubmitBtn');
  const text = document.getElementById('dropzoneText');
  if (file) {
    text.textContent = `✓ ${file.name} (${(file.size / 1024).toFixed(1)} Ko)`;
    text.style.color = 'var(--c-success)';
    document.getElementById('dropzone').style.borderColor = 'var(--c-success)';
    btn.disabled = false;
  }
}

// Import submit
async function submitImport() {
  const btn   = document.getElementById('importSubmitBtn');
  const form  = document.getElementById('importForm');
  const fData = new FormData(form);
  CrmForm.setLoading(btn, true);

  const { ok, data } = await Http.post('{{ route("clients.import") }}', fData);
  CrmForm.setLoading(btn, false);

  if (ok) {
    Modal.close(document.getElementById('importModal'));
    Toast.success('Import réussi !', data.message);
    window._crmTable?.load();
    window._crmTable?.loadStats();
  } else {
    Toast.error('Erreur d\'import', data.message);
  }
}

// Drag & drop
const dropzone = document.getElementById('dropzone');
if (dropzone) {
  dropzone.addEventListener('dragover',  (e) => { e.preventDefault(); dropzone.style.background = 'var(--c-accent-xl)'; });
  dropzone.addEventListener('dragleave', ()  => { dropzone.style.background = ''; });
  dropzone.addEventListener('drop', (e) => {
    e.preventDefault(); dropzone.style.background = '';
    const file = e.dataTransfer.files[0];
    if (file) {
      const dt = new DataTransfer(); dt.items.add(file);
      const inp = document.getElementById('importFile');
      inp.files = dt.files;
      handleFileSelect(inp);
    }
  });
}
</script>
@endpush
