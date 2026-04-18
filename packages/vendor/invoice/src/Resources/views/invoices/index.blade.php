@extends('invoice::layouts.invoice')

@section('title', 'Factures')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">
            <span class="title-icon">📄</span>
            Factures
        </h1>
        <p class="page-subtitle">Gérez l'ensemble de vos factures</p>
    </div>
    <div class="page-actions">
        <div class="btn-dropdown-wrap">
            <button class="btn btn-outline" data-dropdown-toggle="export-menu">
                📊 Exporter ▾
            </button>
            <div class="btn-dropdown" id="export-menu">
                <a href="{{ route('invoices.export.excel') }}" class="btn-dropdown-item">📗 Excel (.xlsx)</a>
                <a href="{{ route('invoices.export.csv') }}"   class="btn-dropdown-item">📋 CSV</a>
                <a href="{{ route('invoices.export.pdf') }}"   class="btn-dropdown-item">📕 PDF</a>
            </div>
        </div>

        <button class="btn btn-outline" onclick="document.getElementById('import-modal').classList.add('open')">
            📥 Importer
        </button>

        <a href="{{ route('invoices.quotes.create') }}" class="btn btn-outline">
            📝 Nouveau devis
        </a>
        <a href="{{ route('invoices.create') }}" class="btn btn-primary">
            + Nouvelle facture
        </a>
    </div>
</div>

{{-- Stats --}}
<div class="stats-grid" id="inv-stats-bar">
    <div class="stat-card" style="min-height:84px;animation:pulse 1.5s infinite">
        <div class="stat-body"><div class="stat-label">Chargement…</div></div>
    </div>
</div>

{{-- Filters --}}
<div class="filters-bar">
    <div class="filter-search">
        <span class="filter-search-icon">🔍</span>
        <input type="text" class="form-control" placeholder="Rechercher (numéro, client…)"
               data-inv-filter="search" id="search-input">
    </div>

    <div class="filter-group">
        <label>Statut</label>
        <select class="form-select" data-inv-filter="status" style="min-width:140px">
            <option value="">Tous les statuts</option>
            @foreach($statuses as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="filter-group">
        <label>Devise</label>
        <select class="form-select" data-inv-filter="currency" style="min-width:100px">
            <option value="">Toutes</option>
            @foreach($currencies as $code => $cfg)
                <option value="{{ $code }}">{{ $code }} {{ $cfg['symbol'] }}</option>
            @endforeach
        </select>
    </div>

    <div class="filter-group">
        <label>Du</label>
        <input type="date" class="form-control" data-inv-filter="date_from">
    </div>
    <div class="filter-group">
        <label>Au</label>
        <input type="date" class="form-control" data-inv-filter="date_to">
    </div>

    <div class="filter-group">
        <label>En retard</label>
        <select class="form-select" data-inv-filter="overdue" style="min-width:110px">
            <option value="">Toutes</option>
            <option value="1">En retard</option>
        </select>
    </div>

    <button class="btn btn-outline btn-sm" onclick="document.querySelectorAll('[data-inv-filter]').forEach(e=>e.value=''); InvoiceTable.applyFilters()">
        ✕ Réinitialiser
    </button>
</div>

{{-- Table --}}
<div class="data-table-wrap">
    <div class="data-table-header">
        <span class="table-title">Factures</span>
        <div style="display:flex;gap:8px;align-items:center">
            <select class="form-select" style="width:auto;font-size:13px" onchange="InvoiceTable.init({perPage:this.value})">
                @foreach([15,25,50,100] as $n)
                    <option value="{{ $n }}">{{ $n }} / page</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Bulk actions bar --}}
    <div id="inv-bulk-bar" style="display:none;background:var(--c-accent-lt);padding:10px 20px;align-items:center;gap:12px;border-bottom:1px solid var(--c-ink-05)">
        <span class="bulk-count" style="font-size:13px;font-weight:600;color:var(--c-accent)"></span>
        <button class="btn btn-outline btn-sm" onclick="bulkAction('delete')">🗑 Supprimer</button>
        <button class="btn btn-outline btn-sm" onclick="bulkAction('send')">📤 Marquer envoyées</button>
    </div>

    <div class="table-responsive" style="position:relative">
        <div id="inv-table-loader" style="display:none;position:absolute;inset:0;z-index:5;align-items:center;justify-content:center;background:rgba(248,250,252,.7)">
            <div style="width:32px;height:32px;border:3px solid var(--c-ink-05);border-top-color:var(--c-accent);border-radius:50%;animation:spin .7s linear infinite"></div>
        </div>
        <table class="inv-table">
            <thead>
                <tr>
                    <th style="width:36px"><input type="checkbox" id="inv-select-all"></th>
                    <th data-sort="number">Numéro</th>
                    <th data-sort="client_id">Client</th>
                    <th data-sort="status">Statut</th>
                    <th data-sort="issue_date">Émission</th>
                    <th data-sort="due_date">Échéance</th>
                    <th data-sort="currency">Devise</th>
                    <th data-sort="total" style="text-align:right">Total TTC</th>
                    <th data-sort="amount_due" style="text-align:right">Reste dû</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody id="inv-table-body">
                <tr><td colspan="10" style="text-align:center;padding:40px;color:var(--c-ink-40)">
                    <div style="width:24px;height:24px;border:3px solid var(--c-ink-05);border-top-color:var(--c-accent);border-radius:50%;animation:spin .7s linear infinite;margin:0 auto"></div>
                </td></tr>
            </tbody>
        </table>
    </div>

    <div id="inv-pagination" class="pagination-bar"></div>
</div>

{{-- Import Modal --}}
<div class="modal-overlay" id="import-modal">
    <div class="modal-box" style="max-width:440px">
        <div class="modal-header">
            <h3 class="modal-title">📥 Importer des factures</h3>
            <button class="modal-close" onclick="document.getElementById('import-modal').classList.remove('open')">×</button>
        </div>
        <div class="modal-body">
            <p style="font-size:13px;color:var(--c-ink-40);margin-bottom:16px">
                Importez vos factures via un fichier Excel ou CSV.<br>
                <a href="#" style="color:var(--c-accent)">📄 Télécharger le modèle</a>
            </p>
            <form id="import-form" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label>Fichier (.xlsx, .xls, .csv)</label>
                    <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="document.getElementById('import-modal').classList.remove('open')">Annuler</button>
            <button class="btn btn-primary" onclick="submitImport()">📥 Importer</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const DEFAULT_CURRENCY    = '{{ config('crm-core.formats.currency', 'EUR') }}';
    const INVOICE_CURRENCIES  = @json(config('invoice.currencies'));

    document.addEventListener('DOMContentLoaded', () => {
        InvoiceTable.init({
            dataUrl:  '{{ route('invoices.data') }}',
            statsUrl: '{{ route('invoices.stats') }}',
            perPage:  15,
        });
    });

    async function submitImport() {
        const form = document.getElementById('import-form');
        const csrf = document.querySelector('meta[name=csrf-token]').content;
        const btn  = event.target;
        btn.disabled = true; btn.textContent = 'Import…';
        try {
            const res  = await fetch('{{ route('invoices.import') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
            });
            const json = await res.json();
            if (json.success) {
                Toast.success('Importation réussie', json.message);
                document.getElementById('import-modal').classList.remove('open');
                InvoiceTable.load();
            } else {
                Toast.error('Erreur', json.message);
            }
        } catch(e) { Toast.error('Erreur', e.message); }
        finally { btn.disabled = false; btn.textContent = '📥 Importer'; }
    }

    async function bulkAction(action) {
        const ids = [...document.querySelectorAll('.inv-row-check:checked')].map(c=>+c.value);
        if (!ids.length) return;
        if (!confirm(`Confirmer l'action sur ${ids.length} facture(s) ?`)) return;
        // TODO: bulk endpoint
        Toast.info('Bientôt disponible', 'Action en masse en cours d\'implémentation.');
    }
</script>
@endpush
