@extends('layouts.global')

@section('title', 'Équipe')

@section('breadcrumb')
  <span>CRM</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Équipe</span>
@endsection

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Gestion de l'équipe</h1>
    <p>Gérez les membres, rôles et accès de votre organisation</p>
  </div>
  <div class="page-header-actions">
    {{-- Export --}}
    <div class="dropdown">
      <button class="btn btn-secondary" data-dropdown-toggle>
        <i class="fas fa-arrow-down-to-line"></i> Exporter
        <i class="fas fa-chevron-down" style="font-size:10px;margin-left:2px;"></i>
      </button>
      <div class="dropdown-menu">
        <a href="{{ route('users.export.csv') }}"   class="dropdown-item"><i class="fas fa-file-csv"></i>   CSV</a>
        <a href="{{ route('users.export.excel') }}" class="dropdown-item"><i class="fas fa-file-excel"></i> Excel</a>
      </div>
    </div>
    {{-- Invite new member --}}
    <a href="{{ route('users.create') }}" class="btn btn-primary">
      <i class="fas fa-user-plus"></i> Inviter un membre
    </a>
  </div>
</div>

<div class="users-top-tabs" role="tablist" aria-label="Navigation équipe">
  <a href="{{ route('users.index') }}" class="users-top-tab is-active">
    <i class="fas fa-users"></i>
    <span>Utilisateurs</span>
  </a>
  <a href="{{ route('rbac.roles.index') }}" class="users-top-tab">
    <i class="fas fa-shield-halved"></i>
    <span>Roles</span>
  </a>
  <a href="{{ route('rbac.permissions.index') }}" class="users-top-tab">
    <i class="fas fa-key"></i>
    <span>Permissions</span>
  </a>
  <a href="{{ route('users.invitations') }}" class="users-top-tab">
    <i class="fas fa-envelope-open-text"></i>
    <span>Invitations</span>
  </a>
</div>

{{-- Stats KPI --}}
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-users"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiTotal">—</div>
      <div class="stat-label">Total membres</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-user-check"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiActive">—</div>
      <div class="stat-label">Actifs</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-info-lt);color:var(--c-info)"><i class="fas fa-envelope"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiInvited">—</div>
      <div class="stat-label">Invitations envoyées</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-danger-lt);color:var(--c-danger)"><i class="fas fa-user-slash"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiSuspended">—</div>
      <div class="stat-label">Suspendus</div>
    </div>
  </div>
</div>

{{-- Table --}}
<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Membres de l'équipe</span>
    <span class="table-count" id="usersCount">—</span>
    <div class="table-spacer"></div>

    <div class="table-search">
      <i class="fas fa-search"></i>
      <input type="text" id="searchInput" placeholder="Rechercher un membre…" autocomplete="off">
    </div>

    <select class="filter-select" data-filter="role_in_tenant">
      <option value="">Tous les rôles</option>
      @foreach($roles as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>

    <select class="filter-select" data-filter="status">
      <option value="">Tous les statuts</option>
      @foreach($statuses as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>

    <button class="btn btn-ghost btn-sm" id="resetFilters" title="Réinitialiser">
      <i class="fas fa-rotate-left"></i>
    </button>
  </div>

  {{-- Bulk bar --}}
  <div class="bulk-bar" id="bulkBar">
    <span><strong id="selectedCount">0</strong> membre(s) sélectionné(s)</span>
    <div class="bulk-bar-actions">
      <button class="btn btn-sm btn-secondary" onclick="bulkUserStatus('active')">
        <i class="fas fa-check-circle"></i> Activer
      </button>
      <button class="btn btn-sm btn-secondary" onclick="bulkUserStatus('suspended')">
        <i class="fas fa-ban"></i> Suspendre
      </button>
      <button class="btn btn-sm btn-danger" onclick="bulkUserDelete()">
        <i class="fas fa-trash"></i> Supprimer
      </button>
    </div>
  </div>

  <table class="crm-table" id="usersTable">
    <thead>
      <tr>
        <th style="width:40px"><input type="checkbox" id="selectAll"></th>
        <th data-sort="name" class="sortable">Membre <i class="fas fa-sort" style="font-size:10px;opacity:.4"></i></th>
        <th data-sort="role_in_tenant" class="sortable">Rôle</th>
        <th>Département</th>
        <th data-sort="status" class="sortable">Statut</th>
        <th data-sort="last_login_at" class="sortable">Dernière connexion</th>
        <th style="text-align:right;padding-right:20px">Actions</th>
      </tr>
    </thead>
    <tbody id="usersTableBody">
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

@push('styles')
<style>
.users-top-tabs{
  display:flex;
  flex-wrap:wrap;
  gap:10px;
  margin:0 0 16px;
}
.users-top-tab{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:10px 14px;
  border-radius:12px;
  border:1px solid var(--c-line);
  color:var(--c-ink-60);
  background:var(--c-surface);
  font-weight:600;
  text-decoration:none;
  transition:all .2s ease;
}
.users-top-tab:hover{
  border-color:var(--c-accent);
  color:var(--c-accent);
  transform:translateY(-1px);
}
.users-top-tab.is-active{
  border-color:var(--c-accent);
  background:var(--c-accent-lt);
  color:var(--c-accent);
}
</style>
@endpush

@push('scripts')
<script>
window.USER_ROUTES = {
  data:       '{{ route("users.data") }}',
  stats:      '{{ route("users.stats") }}',
  bulkDelete: '{{ route("users.bulk.delete") }}',
  bulkStatus: '{{ route("users.bulk.status") }}',
};

const ROLE_LABELS   = @json(config('user.tenant_roles'));
const STATUS_LABELS = @json(config('user.user_statuses'));
const ROLE_COLORS   = {
  owner:   '#7c3aed', admin: '#2563eb', manager: '#0891b2',
  user:    '#059669', viewer: '#64748b'
};
const STATUS_COLORS = {
  active:'success', inactive:'danger', invited:'info', suspended:'secondary'
};

document.addEventListener('DOMContentLoaded', () => {
  window._userTable = new UserTable({
    tbodyId:  'usersTableBody',
    dataUrl:  window.USER_ROUTES.data,
    statsUrl: window.USER_ROUTES.stats,
    countEl:  'usersCount',
  });
});

async function bulkUserDelete() {
  const ids = window._userTable?.getSelectedIds();
  if (!ids?.length) return;
  Modal.confirm({
    title: `Supprimer ${ids.length} membre(s) ?`,
    message: 'Cette action est irréversible. Les propriétaires ne seront pas supprimés.',
    confirmText: 'Supprimer',
    type: 'danger',
    onConfirm: async () => {
      const { ok, data } = await Http.post(window.USER_ROUTES.bulkDelete, { ids });
      if (ok) { Toast.success('Succès', data.message); window._userTable?.load(); window._userTable?.loadStats(); }
      else Toast.error('Erreur', data.message);
    }
  });
}

async function bulkUserStatus(status) {
  const ids = window._userTable?.getSelectedIds();
  if (!ids?.length) return;
  const { ok, data } = await Http.post(window.USER_ROUTES.bulkStatus, { ids, status });
  if (ok) { Toast.success('Succès', data.message); window._userTable?.load(); window._userTable?.loadStats(); }
  else Toast.error('Erreur', data.message);
}

/* ── UserTable ─────────────────────────────────────────────────────────── */
class UserTable {
  constructor(opts) {
    this.opts = Object.assign({ perPage: 15 }, opts);
    this.state = { page: 1, search: '', filters: {}, sort: '', dir: 'asc', loading: false };
    this.selectedIds = new Set();
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
    document.querySelectorAll('[data-filter]').forEach(el => {
      el.addEventListener('change', () => {
        this.state.filters[el.dataset.filter] = el.value;
        this.state.page = 1; this.load();
      });
    });
    document.getElementById('resetFilters')?.addEventListener('click', () => {
      this.state.search = ''; this.state.filters = {};
      document.getElementById('searchInput') && (document.getElementById('searchInput').value = '');
      document.querySelectorAll('[data-filter]').forEach(s => s.value = '');
      this.state.page = 1; this.load();
    });
    document.getElementById('selectAll')?.addEventListener('change', (e) => {
      document.querySelectorAll('.row-check').forEach(cb => {
        cb.checked = e.target.checked;
        e.target.checked ? this.selectedIds.add(+cb.dataset.id) : this.selectedIds.delete(+cb.dataset.id);
      });
      this._updateBulkBar();
    });
    document.getElementById(this.opts.tbodyId)?.addEventListener('change', (e) => {
      if (e.target.classList.contains('row-check')) {
        const id = +e.target.dataset.id;
        e.target.checked ? this.selectedIds.add(id) : this.selectedIds.delete(id);
        this._updateBulkBar();
      }
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
    this._showSkeletons();
    const params = { page: this.state.page, per_page: this.opts.perPage, search: this.state.search, sort_by: this.state.sort, sort_dir: this.state.dir, ...this.state.filters };
    const { ok, data } = await Http.get(this.opts.dataUrl, params);
    this.state.loading = false;
    if (!ok) { Toast.error('Erreur', 'Impossible de charger les données.'); return; }
    this._renderRows(data.data || []);
    this._renderPagination(data);
    const countEl = document.getElementById(this.opts.countEl);
    if (countEl) countEl.textContent = `${data.total || 0} membre(s)`;
  }

  async loadStats() {
    const { ok, data } = await Http.get(this.opts.statsUrl);
    if (!ok || !data.data) return;
    const s = data.data;
    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    set('kpiTotal',     s.total     || 0);
    set('kpiActive',    s.active    || 0);
    set('kpiInvited',   s.invited   || 0);
    set('kpiSuspended', s.suspended || 0);
  }

  _showSkeletons(count = 5) {
    const tbody = document.getElementById(this.opts.tbodyId);
    if (!tbody) return;
    tbody.innerHTML = Array.from({ length: count }, () =>
      `<tr>${Array.from({ length: 7 }, () => `<td><div class="skeleton" style="height:14px;border-radius:4px;"></div></td>`).join('')}</tr>`
    ).join('');
  }

  _renderRows(rows) {
    const tbody = document.getElementById(this.opts.tbodyId);
    if (!tbody) return;
    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="7"><div class="table-empty"><div class="table-empty-icon"><i class="fas fa-users"></i></div><h3>Aucun membre trouvé</h3><p>Invitez votre premier collaborateur.</p><a href="{{ route('users.create') }}" class="btn btn-primary"><i class="fas fa-user-plus"></i> Inviter un membre</a></div></td></tr>`;
      return;
    }
    tbody.innerHTML = rows.map(u => this._renderRow(u)).join('');
  }

  _renderRow(u) {
    const roleColor   = ROLE_COLORS[u.role_in_tenant]  || '#64748b';
    const roleLabel   = ROLE_LABELS[u.role_in_tenant]  || u.role_in_tenant;
    const statusCls   = STATUS_COLORS[u.status]         || 'secondary';
    const statusLabel = STATUS_LABELS[u.status]         || u.status;
    const initials    = (u.name || 'U').substring(0,2).toUpperCase();
    const avatarColors = ['#2563eb','#7c3aed','#0891b2','#059669','#d97706','#dc2626'];
    const bgColor     = avatarColors[(u.name?.charCodeAt(0)||0) % avatarColors.length];
    const avatar      = u.avatar
      ? `<img src="/storage/${u.avatar}" style="width:38px;height:38px;border-radius:var(--r-sm);object-fit:cover;">`
      : `<div class="client-avatar" style="background:${bgColor};width:38px;height:38px;font-size:13px;">${initials}</div>`;
    const isOwner     = u.is_tenant_owner;
    const isSelf      = u.id === window._currentUserId;
    const lastLogin   = u.last_login_at
      ? new Date(u.last_login_at).toLocaleDateString('fr-FR', { day:'2-digit', month:'2-digit', year:'numeric' })
      : '<span style="color:var(--c-ink-20);">Jamais</span>';

    return `
    <tr data-id="${u.id}" class="${this.selectedIds.has(u.id) ? 'selected' : ''}">
      <td style="width:40px">
        ${!isOwner && !isSelf ? `<input type="checkbox" class="row-check" data-id="${u.id}" ${this.selectedIds.has(u.id)?'checked':''}>` : ''}
      </td>
      <td>
        <div class="client-cell">
          ${avatar}
          <div>
            <div class="client-name">${this._esc(u.name)}${isOwner ? ' <span style="font-size:10px;background:#fef3c7;color:#92400e;padding:2px 7px;border-radius:99px;margin-left:6px;font-weight:600;">OWNER</span>' : ''}</div>
            <div class="client-sub">${this._esc(u.email)}</div>
          </div>
        </div>
      </td>
      <td>
        <span style="background:${roleColor}18;color:${roleColor};border:1px solid ${roleColor}30;padding:3px 10px;border-radius:99px;font-size:11.5px;font-weight:600;">
          ${roleLabel}
        </span>
      </td>
      <td style="font-size:13px;color:var(--c-ink-60);">
        ${u.department ? `<div>${this._esc(u.department)}</div>` : ''}
        ${u.job_title  ? `<div style="font-size:11.5px;color:var(--c-ink-40);">${this._esc(u.job_title)}</div>` : ''}
        ${!u.department && !u.job_title ? '<span style="color:var(--c-ink-20);">—</span>' : ''}
      </td>
      <td>
        <span class="badge badge-${statusCls}">
          <span class="badge-dot" style="background:currentColor"></span>
          ${statusLabel}
        </span>
      </td>
      <td style="font-size:13px;color:var(--c-ink-60);">${lastLogin}</td>
      <td>
        <div class="row-actions" style="justify-content:flex-end;padding-right:4px;">
          <a href="/users/${u.id}" class="btn-icon" title="Voir"><i class="fas fa-eye"></i></a>
          <a href="/users/${u.id}/edit" class="btn-icon" title="Modifier"><i class="fas fa-pen"></i></a>
          ${!isOwner && !isSelf ? `
          <button class="btn-icon" title="${u.status === 'active' ? 'Suspendre' : 'Activer'}"
            onclick="UserTable.toggleStatus(${u.id}, '${u.status}', '${this._esc(u.name)}')">
            <i class="fas fa-${u.status === 'active' ? 'ban' : 'check-circle'}"></i>
          </button>
          <button class="btn-icon danger" onclick="UserTable.deleteUser(${u.id}, '${this._esc(u.name)}')" title="Supprimer">
            <i class="fas fa-trash"></i>
          </button>` : ''}
        </div>
      </td>
    </tr>`;
  }

  _renderPagination(data) {
    const wrap = document.getElementById('paginationControls');
    const info = document.getElementById('paginationInfo');
    if (!wrap) return;
    const { current_page, last_page, from, to, total } = data;
    if (info) info.textContent = `Affichage de ${from||0} à ${to||0} sur ${total||0} membres`;
    const pages = [];
    for (let i = Math.max(1, current_page-2); i <= Math.min(last_page||1, current_page+2); i++) pages.push(i);
    wrap.innerHTML = `
      <button class="page-btn" ${current_page<=1?'disabled':''} onclick="window._userTable?.goTo(${current_page-1})"><i class="fas fa-chevron-left"></i></button>
      ${pages.map(p => `<button class="page-btn ${p===current_page?'active':''}" onclick="window._userTable?.goTo(${p})">${p}</button>`).join('')}
      <button class="page-btn" ${current_page>=last_page?'disabled':''} onclick="window._userTable?.goTo(${current_page+1})"><i class="fas fa-chevron-right"></i></button>`;
  }

  goTo(page) { this.state.page = page; this.load(); window.scrollTo({ top: 0, behavior: 'smooth' }); }

  _updateBulkBar() {
    const bar = document.getElementById('bulkBar');
    if (!bar) return;
    const n = this.selectedIds.size;
    bar.classList.toggle('visible', n > 0);
    const cnt = document.getElementById('selectedCount');
    if (cnt) cnt.textContent = n;
  }

  getSelectedIds() { return [...this.selectedIds]; }
  _esc(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }

  /* Statics */
  static async toggleStatus(id, current, name) {
    const newStatus = current === 'active' ? 'suspended' : 'active';
    const action = newStatus === 'suspended' ? 'suspendre' : 'activer';
    Modal.confirm({
      title: `${newStatus === 'active' ? 'Activer' : 'Suspendre'} ${name} ?`,
      message: `Vous allez ${action} cet utilisateur.`,
      confirmText: newStatus === 'active' ? 'Activer' : 'Suspendre',
      type: newStatus === 'active' ? 'success' : 'danger',
      onConfirm: async () => {
        const url = newStatus === 'suspended' ? `/users/${id}/suspend` : `/users/${id}/activate`;
        const { ok, data } = await Http.post(url, {});
        if (ok) { Toast.success('Succès', data.message); window._userTable?.load(); window._userTable?.loadStats(); }
        else Toast.error('Erreur', data.message);
      }
    });
  }

  static async deleteUser(id, name) {
    Modal.confirm({
      title: `Supprimer ${name} ?`,
      message: 'Cette action est irréversible.',
      confirmText: 'Supprimer',
      type: 'danger',
      onConfirm: async () => {
        const { ok, data } = await Http.delete(`/users/${id}`);
        if (ok) { Toast.success('Supprimé', data.message); window._userTable?.load(); window._userTable?.loadStats(); }
        else Toast.error('Erreur', data.message);
      }
    });
  }
}
window.UserTable = UserTable;
window._currentUserId = {{ auth()->id() }};
</script>
@endpush
