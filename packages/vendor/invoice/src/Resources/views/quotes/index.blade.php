@extends('invoice::layouts.invoice')
@section('title', 'Devis')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">
            <span class="title-icon" style="background:var(--c-purple-lt);color:var(--c-purple)">📝</span>
            Devis
        </h1>
        <p class="page-subtitle">Gérez vos propositions commerciales</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('invoices.index') }}" class="btn btn-outline">📄 Factures</a>
        <a href="{{ route('invoices.quotes.create') }}" class="btn btn-primary">+ Nouveau devis</a>
    </div>
</div>

{{-- Filters --}}
<div class="filters-bar">
    <div class="filter-search">
        <span class="filter-search-icon">🔍</span>
        <input type="text" class="form-control" placeholder="Rechercher un devis…"
               data-inv-filter="search">
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
        <label>Du</label>
        <input type="date" class="form-control" data-inv-filter="date_from">
    </div>
    <div class="filter-group">
        <label>Au</label>
        <input type="date" class="form-control" data-inv-filter="date_to">
    </div>
    <button class="btn btn-outline btn-sm"
            onclick="document.querySelectorAll('[data-inv-filter]').forEach(e=>e.value='');QuoteTable.applyFilters()">
        ✕ Réinitialiser
    </button>
</div>

<div class="data-table-wrap">
    <div class="data-table-header">
        <span class="table-title">Devis</span>
    </div>
    <div class="table-responsive" style="position:relative">
        <div id="quote-table-loader" style="display:none;position:absolute;inset:0;z-index:5;align-items:center;justify-content:center;background:rgba(248,250,252,.7)">
            <div style="width:32px;height:32px;border:3px solid var(--c-ink-05);border-top-color:var(--c-purple);border-radius:50%;animation:spin .7s linear infinite"></div>
        </div>
        <table class="inv-table">
            <thead>
                <tr>
                    <th data-sort="number">Numéro</th>
                    <th data-sort="client_id">Client</th>
                    <th data-sort="status">Statut</th>
                    <th data-sort="issue_date">Émission</th>
                    <th data-sort="valid_until">Valide jusqu'au</th>
                    <th data-sort="total" style="text-align:right">Total TTC</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody id="quote-table-body">
                <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--c-ink-40)">Chargement…</td></tr>
            </tbody>
        </table>
    </div>
    <div id="quote-pagination" class="pagination-bar"></div>
</div>
@endsection

@push('scripts')
<script>
const INVOICE_CURRENCIES = @json($currencies);
const QuoteTable = (() => {
    let state = { page:1, perPage:15, sort:'issue_date', order:'desc', filters:{} };
    const dataUrl = '{{ route('invoices.quotes.data') }}';
    const tbody   = document.getElementById('quote-table-body');
    const pag     = document.getElementById('quote-pagination');

    function applyFilters() {
        const filters = {};
        document.querySelectorAll('[data-inv-filter]').forEach(el => { if(el.value) filters[el.dataset.invFilter]=el.value; });
        state.filters = filters; state.page = 1; load();
    }

    async function load() {
        const params = new URLSearchParams({ page:state.page, per_page:state.perPage, sort:state.sort, order:state.order, ...state.filters });
        try {
            const res  = await fetch(`${dataUrl}?${params}`,{headers:{'X-Requested-With':'XMLHttpRequest'}});
            const json = await res.json();
            renderRows(json.data);
            if(pag) pag.innerHTML = `<div class="pagination-info">Total : <strong>${json.total}</strong></div>`;
        } catch(e) { Toast.error('Erreur','Impossible de charger les devis.'); }
    }

    function renderRows(rows) {
        if(!rows||!rows.length) { tbody.innerHTML=`<tr><td colspan="7" style="text-align:center;padding:48px;color:var(--c-ink-40)">📝 Aucun devis trouvé</td></tr>`; return; }
        tbody.innerHTML = rows.map(q=>`
        <tr>
            <td><a href="/invoices/quotes/${q.id}" class="inv-number" style="color:var(--c-purple)">${q.number}</a></td>
            <td><div style="font-weight:500">${q.client?.company_name||'—'}</div></td>
            <td><span class="status-badge badge-${q.status}">${q.status_label}</span>
                ${q.is_converted?'<span class="status-badge badge-paid" style="margin-left:4px">✓ Converti</span>':''}</td>
            <td>${q.issue_date||'—'}</td>
            <td class="${q.is_expired?'amount-cell due':''}">${q.valid_until||'—'}</td>
            <td class="amount-cell">${CurrencyFmt.format(q.total,q.currency||'EUR')}</td>
            <td>
                <div style="display:flex;gap:4px;justify-content:flex-end">
                    <a href="/invoices/quotes/${q.id}" class="btn btn-outline btn-icon btn-sm" title="Voir">👁</a>
                    ${!['accepted','declined'].includes(q.status)?`<a href="/invoices/quotes/${q.id}/edit" class="btn btn-outline btn-icon btn-sm" title="Modifier">✏️</a>`:''}
                    <a href="/invoices/quotes/${q.id}/pdf" class="btn btn-outline btn-icon btn-sm" title="PDF">📄</a>
                    ${!q.is_converted&&q.status==='sent'?`<button onclick="convertQuoteToInvoice(${q.id})" class="btn btn-success btn-sm" title="Convertir en facture">→ FAC</button>`:''}
                </div>
            </td>
        </tr>`).join('');
    }

    document.querySelectorAll('[data-inv-filter]').forEach(el => {
        el.addEventListener('input',  debounce(applyFilters, 380));
        el.addEventListener('change', applyFilters);
    });
    load();
    return { applyFilters, load };
})();
</script>
@endpush
