@extends('layouts.global')

@section('title', 'Rôles & Permissions')

@section('breadcrumb')
  <span>Administration</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Rôles & Permissions</span>
@endsection

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Rôles & Permissions</h1>
    <p>Définissez les rôles de votre équipe et leurs droits d'accès</p>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('rbac.permissions.index') }}" class="btn btn-secondary">
      <i class="fas fa-shield-halved"></i> Voir les permissions
    </a>
    <a href="{{ route('rbac.roles.create') }}" class="btn btn-primary">
      <i class="fas fa-plus"></i> Nouveau rôle
    </a>
  </div>
</div>

{{-- Stats KPI --}}
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);">
  <div class="stat-card">
    <div class="stat-icon" style="background:#f3e8ff;color:#7c3aed"><i class="fas fa-shield-halved"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiTotalRoles">—</div>
      <div class="stat-label">Total rôles</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-wand-magic-sparkles"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiCustomRoles">—</div>
      <div class="stat-label">Rôles personnalisés</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-key"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiTotalPerms">—</div>
      <div class="stat-label">Permissions disponibles</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-user-xmark"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiNoRole">—</div>
      <div class="stat-label">Membres sans rôle</div>
    </div>
  </div>
</div>

{{-- Table --}}
<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Rôles</span>
    <span class="table-count" id="rolesCount">—</span>
    <div class="table-spacer"></div>
    <div class="table-search">
      <i class="fas fa-search"></i>
      <input type="text" id="searchInput" placeholder="Rechercher un rôle…" autocomplete="off">
    </div>
    <button class="btn btn-ghost btn-sm" id="resetFilters"><i class="fas fa-rotate-left"></i></button>
  </div>

  <table class="crm-table" id="rolesTable">
    <thead>
      <tr>
        <th style="width:44px"></th>
        <th data-sort="label" class="sortable">Rôle <i class="fas fa-sort" style="font-size:10px;opacity:.4"></i></th>
        <th>Description</th>
        <th style="text-align:center">Permissions</th>
        <th style="text-align:center">Membres</th>
        <th>Type</th>
        <th style="text-align:right;padding-right:20px">Actions</th>
      </tr>
    </thead>
    <tbody id="rolesTableBody"></tbody>
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
window.RBAC_ROUTES = {
  data:  '{{ route("rbac.roles.data") }}',
  stats: '{{ route("rbac.roles.stats") }}',
};

document.addEventListener('DOMContentLoaded', () => {
  window._rolesTable = new RolesTable({
    tbodyId: 'rolesTableBody',
    dataUrl: window.RBAC_ROUTES.data,
    statsUrl: window.RBAC_ROUTES.stats,
  });
});

class RolesTable {
  constructor(opts) {
    this.opts = Object.assign({ perPage: 15 }, opts);
    this.state = { page: 1, search: '', loading: false };
    this._debounce = null;
    this._bindEvents();
    this.load();
    if (this.opts.statsUrl) this.loadStats();
  }

  _bindEvents() {
    document.getElementById('searchInput')?.addEventListener('input', () => {
      clearTimeout(this._debounce);
      this._debounce = setTimeout(() => {
        this.state.search = document.getElementById('searchInput').value.trim();
        this.state.page = 1; this.load();
      }, 350);
    });
    document.getElementById('resetFilters')?.addEventListener('click', () => {
      this.state.search = '';
      if (document.getElementById('searchInput')) document.getElementById('searchInput').value = '';
      this.state.page = 1; this.load();
    });
  }

  async load() {
    if (this.state.loading) return;
    this.state.loading = true;
    const tbody = document.getElementById(this.opts.tbodyId);
    if (tbody) tbody.innerHTML = `<tr>${Array.from({length:7},()=>`<td><div class="skeleton" style="height:14px;border-radius:4px;"></div></td>`).join('')}</tr>`.repeat(5);

    const params = { page: this.state.page, per_page: this.opts.perPage, search: this.state.search };
    const { ok, data } = await Http.get(this.opts.dataUrl, params);
    this.state.loading = false;
    if (!ok) { Toast.error('Erreur', 'Impossible de charger les rôles.'); return; }
    this._renderRows(data.data || []);
    this._renderPagination(data);
    const cnt = document.getElementById('rolesCount');
    if (cnt) cnt.textContent = `${data.total || 0}`;
  }

  async loadStats() {
    const { ok, data } = await Http.get(this.opts.statsUrl);
    if (!ok || !data.data) return;
    const s = data.data;
    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    set('kpiTotalRoles',  s.total_roles       || 0);
    set('kpiCustomRoles', s.custom_roles      || 0);
    set('kpiTotalPerms',  s.total_permissions || 0);
    set('kpiNoRole',      s.users_without_role|| 0);
  }

  _renderRows(rows) {
    const tbody = document.getElementById(this.opts.tbodyId);
    if (!tbody) return;
    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="7"><div class="table-empty"><div class="table-empty-icon"><i class="fas fa-shield-halved"></i></div><h3>Aucun rôle</h3><p>Créez votre premier rôle personnalisé.</p><a href="{{ route('rbac.roles.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Nouveau rôle</a></div></td></tr>`;
      return;
    }
    tbody.innerHTML = rows.map(r => this._renderRow(r)).join('');
  }

  _renderRow(r) {
    const color     = r.color || '#64748b';
    const label     = this._esc(r.label || r.name);
    const isSystem  = r.is_system;
    const typeBadge = isSystem
      ? `<span style="background:#f3e8ff;color:#7c3aed;border:1px solid #e9d5ff;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:600;"><i class="fas fa-lock" style="font-size:10px;margin-right:4px;"></i>Système</span>`
      : `<span style="background:var(--c-accent-lt);color:var(--c-accent);border:1px solid var(--c-accent-lt);padding:3px 10px;border-radius:99px;font-size:11px;font-weight:600;"><i class="fas fa-pen" style="font-size:10px;margin-right:4px;"></i>Personnalisé</span>`;

    return `
    <tr data-id="${r.id}">
      <td style="width:44px;padding:12px 8px 12px 16px;">
        <div style="width:14px;height:14px;border-radius:50%;background:${color};flex-shrink:0;box-shadow:0 0 0 3px ${color}22;"></div>
      </td>
      <td>
        <div style="display:flex;align-items:center;gap:10px;">
          <div>
            <div style="font-weight:var(--fw-semi);color:var(--c-ink);">
              ${label}
              ${!r.is_active ? '<span style="font-size:10px;background:var(--c-danger-lt);color:var(--c-danger);padding:2px 7px;border-radius:99px;margin-left:6px;">Inactif</span>' : ''}
            </div>
            <div style="font-size:11.5px;color:var(--c-ink-40);font-family:monospace;">${this._esc(r.name)}</div>
          </div>
        </div>
      </td>
      <td style="font-size:13px;color:var(--c-ink-60);max-width:220px;">
        <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${this._esc(r.description || '—')}</div>
      </td>
      <td style="text-align:center;">
        <span style="background:var(--c-success-lt);color:var(--c-success);padding:3px 12px;border-radius:99px;font-size:12px;font-weight:600;">
          <i class="fas fa-key" style="font-size:10px;margin-right:4px;"></i>${r.permissions_count}
        </span>
      </td>
      <td style="text-align:center;">
        <span style="background:var(--c-accent-lt);color:var(--c-accent);padding:3px 12px;border-radius:99px;font-size:12px;font-weight:600;">
          <i class="fas fa-users" style="font-size:10px;margin-right:4px;"></i>${r.users_count}
        </span>
      </td>
      <td>${typeBadge}</td>
      <td>
        <div class="row-actions" style="justify-content:flex-end;padding-right:4px;">
          <a href="/rbac/roles/${r.id}" class="btn-icon" title="Voir"><i class="fas fa-eye"></i></a>
          ${!isSystem ? `
            <a href="/rbac/roles/${r.id}/edit" class="btn-icon" title="Modifier"><i class="fas fa-pen"></i></a>
            <button class="btn-icon danger" onclick="RolesTable.deleteRole(${r.id},'${label}')" title="Supprimer"><i class="fas fa-trash"></i></button>
          ` : `<span style="padding:0 4px;color:var(--c-ink-20);font-size:12px;"><i class="fas fa-lock"></i></span>`}
        </div>
      </td>
    </tr>`;
  }

  _renderPagination(data) {
    const wrap = document.getElementById('paginationControls');
    const info = document.getElementById('paginationInfo');
    if (!wrap) return;
    const { current_page, last_page, from, to, total } = data;
    if (info) info.textContent = `Affichage de ${from||0} à ${to||0} sur ${total||0} rôles`;
    const pages = [];
    for (let i = Math.max(1,current_page-2); i <= Math.min(last_page||1,current_page+2); i++) pages.push(i);
    wrap.innerHTML = `
      <button class="page-btn" ${current_page<=1?'disabled':''} onclick="window._rolesTable?.goTo(${current_page-1})"><i class="fas fa-chevron-left"></i></button>
      ${pages.map(p=>`<button class="page-btn ${p===current_page?'active':''}" onclick="window._rolesTable?.goTo(${p})">${p}</button>`).join('')}
      <button class="page-btn" ${current_page>=last_page?'disabled':''} onclick="window._rolesTable?.goTo(${current_page+1})"><i class="fas fa-chevron-right"></i></button>`;
  }

  goTo(page) { this.state.page = page; this.load(); window.scrollTo({top:0,behavior:'smooth'}); }
  _esc(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }

  static async deleteRole(id, label) {
    Modal.confirm({
      title: `Supprimer le rôle « ${label} » ?`,
      message: 'Ce rôle sera retiré de tous les membres qui le possèdent.',
      confirmText: 'Supprimer',
      type: 'danger',
      onConfirm: async () => {
        const { ok, data } = await Http.delete(`/rbac/roles/${id}`);
        if (ok) { Toast.success('Supprimé', data.message); window._rolesTable?.load(); window._rolesTable?.loadStats(); }
        else Toast.error('Erreur', data.message);
      }
    });
  }
}
window.RolesTable = RolesTable;
</script>
@endpush