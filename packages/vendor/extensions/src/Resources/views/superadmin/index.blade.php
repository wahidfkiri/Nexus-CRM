@extends('layouts.global')

@section('title', 'Gestion des Extensions')

@section('breadcrumb')
  <span>Super Admin</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Extensions & Marketplace</span>
@endsection

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Extensions & Marketplace</h1>
    <p>Gérez le catalogue d'applications disponibles pour vos tenants</p>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('superadmin.extensions.activations.index') }}" class="btn btn-secondary">
      <i class="fas fa-plug"></i> Activations tenants
    </a>
    <a href="{{ route('superadmin.extensions.export.excel') }}" class="btn btn-secondary">
      <i class="fas fa-file-excel"></i> Exporter
    </a>
    <a href="{{ route('superadmin.extensions.create') }}" class="btn btn-primary">
      <i class="fas fa-plus"></i> Nouvelle extension
    </a>
  </div>
</div>

{{-- KPIs --}}
<div class="stats-grid" style="grid-template-columns:repeat(6,1fr);margin-bottom:24px;">
  <div class="stat-card">
    <div class="stat-icon" style="background:#f3e8ff;color:#7c3aed;"><i class="fas fa-puzzle-piece"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiTotal">—</div>
      <div class="stat-label">Total</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success);"><i class="fas fa-circle-check"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiActive">—</div>
      <div class="stat-label">Actives</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning);"><i class="fas fa-star"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiFeatured">—</div>
      <div class="stat-label">Vedette</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent);"><i class="fas fa-gift"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiFree">—</div>
      <div class="stat-label">Gratuites</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#dcfce7;color:#15803d;"><i class="fas fa-download"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiInstalls">—</div>
      <div class="stat-label">Activations</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#dcfce7;color:#15803d;"><i class="fas fa-euro-sign"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiRevenue">—</div>
      <div class="stat-label">Revenus</div>
    </div>
  </div>
</div>

{{-- Table --}}
<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Catalogue d'extensions</span>
    <span class="table-count" id="extCount">—</span>
    <div class="table-spacer"></div>

    <div class="table-search">
      <i class="fas fa-search"></i>
      <input type="text" id="searchInput" placeholder="Nom, description…" autocomplete="off">
    </div>

    <select class="filter-select" data-filter="category">
      <option value="">Toutes catégories</option>
      @foreach($categories as $key => $cat)
        <option value="{{ $key }}">{{ $cat['label'] }}</option>
      @endforeach
    </select>

    <select class="filter-select" data-filter="status">
      <option value="">Tous statuts</option>
      @foreach($statuses as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>

    <select class="filter-select" data-filter="pricing_type">
      <option value="">Tous prix</option>
      @foreach($pricingTypes as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>

    <button class="btn btn-ghost btn-sm" id="resetFilters">
      <i class="fas fa-rotate-left"></i>
    </button>
  </div>

  <table class="crm-table" id="extTable">
    <thead>
      <tr>
        <th style="width:48px"></th>
        <th data-sort="name" class="sortable">Extension <i class="fas fa-sort" style="font-size:10px;opacity:.4"></i></th>
        <th>Catégorie</th>
        <th>Tarification</th>
        <th style="text-align:center" data-sort="installs_count" class="sortable">Installs</th>
        <th style="text-align:center">Vedette</th>
        <th>Statut</th>
        <th style="text-align:right;padding-right:20px">Actions</th>
      </tr>
    </thead>
    <tbody id="extTableBody"></tbody>
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
window.EXT_ROUTES = {
  data:  '{{ route("superadmin.extensions.data") }}',
  stats: '{{ route("superadmin.extensions.stats") }}',
};
const STATUS_STYLES = {
  active:      { cls:'actif',    label:'Active' },
  inactive:    { cls:'inactif',  label:'Inactive' },
  deprecated:  { cls:'inactif',  label:'Dépréciée' },
  beta:        { cls:'info',     label:'Bêta' },
  coming_soon: { cls:'warning',  label:'Bientôt' },
};

class ExtTable {
  constructor() {
    this.state = { page:1, search:'', filters:{}, sort:'sort_order', dir:'asc', loading:false };
    this._deb  = null;
    this._bindEvents();
    this.load();
    this.loadStats();
  }

  _bindEvents() {
    document.getElementById('searchInput')?.addEventListener('input', () => {
      clearTimeout(this._deb);
      this._deb = setTimeout(() => {
        this.state.search = document.getElementById('searchInput').value.trim();
        this.state.page = 1; this.load();
      }, 350);
    });
    document.querySelectorAll('[data-filter]').forEach(el => {
      el.addEventListener('change', () => {
        this.state.filters[el.dataset.filter] = el.value;
        this.state.page = 1; this.load();
      });
    });
    document.getElementById('resetFilters')?.addEventListener('click', () => {
      this.state = { ...this.state, search:'', filters:{}, page:1 };
      document.getElementById('searchInput').value = '';
      document.querySelectorAll('[data-filter]').forEach(s => s.value = '');
      this.load();
    });
    document.querySelectorAll('[data-sort]').forEach(th => {
      th.addEventListener('click', () => {
        const col = th.dataset.sort;
        if (this.state.sort === col) this.state.dir = this.state.dir === 'asc' ? 'desc' : 'asc';
        else { this.state.sort = col; this.state.dir = 'asc'; }
        this.load();
      });
    });
  }

  async load() {
    if (this.state.loading) return;
    this.state.loading = true;
    const tbody = document.getElementById('extTableBody');
    if (tbody) tbody.innerHTML = Array.from({length:5},()=>`<tr>${Array.from({length:8},()=>`<td><div class="skeleton" style="height:14px;border-radius:4px;"></div></td>`).join('')}</tr>`).join('');

    const params = { page:this.state.page, per_page:20, search:this.state.search, sort:this.state.sort, dir:this.state.dir, ...this.state.filters };
    const { ok, data } = await Http.get(window.EXT_ROUTES.data, params);
    this.state.loading = false;
    if (!ok) { Toast.error('Erreur', 'Chargement impossible.'); return; }
    this._render(data.data || []);
    this._renderPagination(data);
    const cnt = document.getElementById('extCount');
    if (cnt) cnt.textContent = `${data.total || 0}`;
  }

  async loadStats() {
    const { ok, data } = await Http.get(window.EXT_ROUTES.stats);
    if (!ok || !data.data) return;
    const s = data.data;
    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    set('kpiTotal',    s.total || 0);
    set('kpiActive',   s.active || 0);
    set('kpiFeatured', s.featured || 0);
    set('kpiFree',     s.free || 0);
    set('kpiInstalls', s.total_installs || 0);
    set('kpiRevenue',  new Intl.NumberFormat('fr-FR',{style:'currency',currency:'EUR',maximumFractionDigits:0}).format(s.total_revenue || 0));
  }

  _render(rows) {
    const tbody = document.getElementById('extTableBody');
    if (!tbody) return;
    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="8"><div class="table-empty">
        <div class="table-empty-icon"><i class="fas fa-puzzle-piece"></i></div>
        <h3>Aucune extension</h3>
        <p>Créez votre première extension pour le marketplace.</p>
        <a href="{{ route('superadmin.extensions.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Créer une extension</a>
      </div></td></tr>`;
      return;
    }
    tbody.innerHTML = rows.map(e => this._row(e)).join('');
  }

  _row(e) {
    const color     = e.category_color || '#64748b';
    const iconHtml  = e.icon_url
      ? `<img src="${e.icon_url}" style="width:28px;height:28px;object-fit:contain;" alt="">`
      : `<i class="fas ${e.category_icon || 'fa-puzzle-piece'}" style="color:${color};font-size:18px;"></i>`;
    const st        = STATUS_STYLES[e.status] || { cls:'secondary', label: e.status };
    const priceHtml = e.is_free
      ? `<span style="background:#dcfce7;color:#15803d;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;">Gratuit</span>`
      : `<span style="background:var(--c-accent-lt);color:var(--c-accent);padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;">${this._esc(e.pricing_label)}</span>`;

    return `
    <tr data-id="${e.id}">
      <td style="width:48px;padding-left:16px;">
        <div style="width:38px;height:38px;border-radius:10px;background:${color}18;display:flex;align-items:center;justify-content:center;">${iconHtml}</div>
      </td>
      <td>
        <div style="font-weight:var(--fw-semi);color:var(--c-ink);display:flex;align-items:center;gap:7px;">
          ${this._esc(e.name)}
          ${e.is_official ? `<span style="background:#f3e8ff;color:#7c3aed;padding:2px 6px;border-radius:99px;font-size:9px;font-weight:700;"><i class="fas fa-certificate" style="font-size:8px;"></i> Officiel</span>` : ''}
          ${e.is_new ? `<span style="background:#dbeafe;color:#1d4ed8;padding:2px 6px;border-radius:99px;font-size:9px;font-weight:700;">New</span>` : ''}
        </div>
        <div style="font-size:11.5px;color:var(--c-ink-40);">v${this._esc(e.version || '1.0.0')} · ${this._esc(e.slug)}</div>
      </td>
      <td>
        <span style="background:${color}18;color:${color};padding:3px 10px;border-radius:99px;font-size:11.5px;font-weight:600;">
          <i class="fas ${e.category_icon}" style="font-size:10px;margin-right:4px;"></i>${this._esc(e.category_label)}
        </span>
      </td>
      <td>${priceHtml}</td>
      <td style="text-align:center;">
        <span style="font-weight:var(--fw-semi);color:var(--c-ink);font-size:14px;">${e.installs || 0}</span>
        <div style="font-size:11px;color:var(--c-ink-40);">${e.active_installs || 0} actifs</div>
      </td>
      <td style="text-align:center;">
        <button onclick="ExtTable.toggleFeatured(${e.id})" title="${e.is_featured ? 'Retirer vedette' : 'Mettre en vedette'}"
                style="background:none;border:none;cursor:pointer;font-size:18px;color:${e.is_featured ? '#f59e0b' : 'var(--c-ink-10)'};transition:color .2s;"
                id="featBtn-${e.id}">
          <i class="fas fa-star"></i>
        </button>
      </td>
      <td>
        <span class="badge badge-${st.cls}">${st.label}</span>
      </td>
      <td>
        <div class="row-actions" style="justify-content:flex-end;padding-right:4px;">
          <a href="/superadmin/extensions/${e.id}" class="btn-icon" title="Voir"><i class="fas fa-eye"></i></a>
          <a href="/superadmin/extensions/${e.id}/edit" class="btn-icon" title="Modifier"><i class="fas fa-pen"></i></a>
          <button class="btn-icon" onclick="ExtTable.toggleStatus(${e.id},'${e.status}')" title="Changer statut">
            <i class="fas fa-${e.status === 'active' ? 'pause' : 'play'}"></i>
          </button>
          <button class="btn-icon danger" onclick="ExtTable.deleteExt(${e.id},'${this._esc(e.name)}')" title="Supprimer">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </td>
    </tr>`;
  }

  _renderPagination(data) {
    const wrap = document.getElementById('paginationControls');
    const info = document.getElementById('paginationInfo');
    if (!wrap) return;
    const { current_page, last_page, from, to, total } = data;
    if (info) info.textContent = `${from||0}–${to||0} sur ${total||0}`;
    const pages = [];
    for (let i = Math.max(1,current_page-2); i <= Math.min(last_page||1,current_page+2); i++) pages.push(i);
    wrap.innerHTML = `
      <button class="page-btn" ${current_page<=1?'disabled':''} onclick="window._extTable?.goTo(${current_page-1})"><i class="fas fa-chevron-left"></i></button>
      ${pages.map(p=>`<button class="page-btn ${p===current_page?'active':''}" onclick="window._extTable?.goTo(${p})">${p}</button>`).join('')}
      <button class="page-btn" ${current_page>=last_page?'disabled':''} onclick="window._extTable?.goTo(${current_page+1})"><i class="fas fa-chevron-right"></i></button>`;
  }

  goTo(p) { this.state.page = p; this.load(); window.scrollTo({top:0,behavior:'smooth'}); }
  _esc(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }

  static async toggleFeatured(id) {
    const { ok, data } = await Http.post(`/superadmin/extensions/${id}/featured`, {});
    if (ok) {
      Toast.success('Mise à jour', data.message);
      const btn = document.getElementById(`featBtn-${id}`);
      if (btn) btn.style.color = data.value ? '#f59e0b' : 'var(--c-ink-10)';
    } else Toast.error('Erreur', data.message);
  }

  static async toggleStatus(id, current) {
    const { ok, data } = await Http.post(`/superadmin/extensions/${id}/status`, {});
    if (ok) { Toast.success('Statut mis à jour', data.message); window._extTable?.load(); }
    else Toast.error('Erreur', data.message);
  }

  static async deleteExt(id, name) {
    Modal.confirm({
      title: `Supprimer « ${name} » ?`,
      message: 'L\'extension sera retirée du marketplace. Les activations actives empêchent la suppression.',
      confirmText: 'Supprimer',
      type: 'danger',
      onConfirm: async () => {
        const { ok, data } = await Http.delete(`/superadmin/extensions/${id}`);
        if (ok) { Toast.success('Supprimée', data.message); window._extTable?.load(); window._extTable?.loadStats(); }
        else Toast.error('Erreur', data.message);
      }
    });
  }
}

window._extTable = new ExtTable();
</script>
@endpush