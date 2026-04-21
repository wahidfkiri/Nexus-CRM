@extends('layouts.global')

@section('title', 'Invitations')

@section('breadcrumb')
  <a href="{{ route('users.index') }}">Équipe</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Invitations</span>
@endsection

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Historique des invitations</h1>
    <p>Suivez les invitations envoyées à votre équipe</p>
  </div>
  <a href="{{ route('users.create') }}" class="btn btn-primary">
    <i class="fas fa-user-plus"></i> Nouvelle invitation
  </a>
</div>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Invitations</span>
    <span class="table-count" id="invCount">—</span>
    <div class="table-spacer"></div>

    <div class="table-search">
      <i class="fas fa-search"></i>
      <input type="text" id="searchInput" placeholder="Rechercher un email…" autocomplete="off">
    </div>

    <select class="filter-select" data-filter="status">
      <option value="">Tous les statuts</option>
      <option value="pending">En attente</option>
      <option value="accepted">Acceptée</option>
      <option value="expired">Expirée</option>
      <option value="revoked">Révoquée</option>
    </select>

    <button class="btn btn-ghost btn-sm" id="resetFilters">
      <i class="fas fa-rotate-left"></i>
    </button>
  </div>

  <table class="crm-table" id="invTable">
    <thead>
      <tr>
        <th>Email invité</th>
        <th>Rôle attribué</th>
        <th>Invité par</th>
        <th>Envoyée le</th>
        <th>Expire le</th>
        <th>Statut</th>
        <th style="text-align:right;padding-right:20px">Actions</th>
      </tr>
    </thead>
    <tbody id="invTableBody">
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
window.INV_ROUTES = {
  data: '{{ route("users.invitations.data") }}',
};
const ROLE_LABELS = @json(config('user.tenant_roles'));

class InvTable {
  constructor() {
    this.state = { page: 1, search: '', filters: {}, loading: false };
    this._debounce = null;
    this._bindEvents();
    this.load();
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
      document.getElementById('searchInput').value = '';
      document.querySelectorAll('[data-filter]').forEach(s => s.value = '');
      this.state.page = 1; this.load();
    });
  }

  async load() {
    if (this.state.loading) return;
    this.state.loading = true;
    const tbody = document.getElementById('invTableBody');
    if (tbody) tbody.innerHTML = `<tr>${Array.from({length:7},()=>`<td><div class="skeleton" style="height:14px;border-radius:4px;"></div></td>`).join('')}</tr>`.repeat(4);
    const params = { page: this.state.page, per_page: 15, search: this.state.search, ...this.state.filters };
    const { ok, data } = await Http.get(window.INV_ROUTES.data, params);
    this.state.loading = false;
    if (!ok) { Toast.error('Erreur', 'Impossible de charger les invitations.'); return; }
    this._renderRows(data.data || []);
    this._renderPagination(data);
    const countEl = document.getElementById('invCount');
    if (countEl) countEl.textContent = `${data.total || 0}`;
  }

  _renderRows(rows) {
    const tbody = document.getElementById('invTableBody');
    if (!tbody) return;
    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="7"><div class="table-empty"><div class="table-empty-icon"><i class="fas fa-envelope-open"></i></div><h3>Aucune invitation</h3><p>Invitez votre premier collaborateur.</p></div></td></tr>`;
      return;
    }
    const statusMap = { pending: ['info','En attente'], accepted: ['actif','Acceptée'], expired: ['warning','Expirée'], revoked: ['inactif','Révoquée'] };
    tbody.innerHTML = rows.map(inv => {
      const st = inv.is_accepted ? 'accepted' : inv.is_revoked ? 'revoked' : inv.is_expired ? 'expired' : 'pending';
      const [cls, lbl] = statusMap[st] || ['secondary', st];
      const role = ROLE_LABELS[inv.role_in_tenant] || inv.role_in_tenant;
      const sentAt  = inv.created_at ? new Date(inv.created_at).toLocaleDateString('fr-FR') : '—';
      const expAt   = inv.expires_at ? new Date(inv.expires_at).toLocaleDateString('fr-FR') : '—';
      const byName  = inv.invited_by?.name || '—';

      return `<tr>
        <td><div style="font-weight:var(--fw-medium);">${this._esc(inv.email)}</div>${inv.resend_count > 0 ? `<div style="font-size:11.5px;color:var(--c-ink-40);">Renvoyée ${inv.resend_count}×</div>` : ''}</td>
        <td><span class="badge badge-info">${role}</span></td>
        <td style="font-size:13px;color:var(--c-ink-60);">${byName}</td>
        <td style="font-size:13px;color:var(--c-ink-60);">${sentAt}</td>
        <td style="font-size:13px;color:var(--c-ink-60);">${expAt}</td>
        <td><span class="badge badge-${cls}">${lbl}</span></td>
        <td>
          <div class="row-actions" style="justify-content:flex-end;padding-right:4px;">
            ${st === 'pending' ? `
              <button class="btn-icon" title="Renvoyer" onclick="resendInv(${inv.id})"><i class="fas fa-paper-plane"></i></button>
              <button class="btn-icon danger" title="Révoquer" onclick="revokeInv(${inv.id})"><i class="fas fa-ban"></i></button>
            ` : ''}
          </div>
        </td>
      </tr>`;
    }).join('');
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
      <button class="page-btn" ${current_page<=1?'disabled':''} onclick="window._invTable?.goTo(${current_page-1})"><i class="fas fa-chevron-left"></i></button>
      ${pages.map(p=>`<button class="page-btn ${p===current_page?'active':''}" onclick="window._invTable?.goTo(${p})">${p}</button>`).join('')}
      <button class="page-btn" ${current_page>=last_page?'disabled':''} onclick="window._invTable?.goTo(${current_page+1})"><i class="fas fa-chevron-right"></i></button>`;
  }

  goTo(page) { this.state.page = page; this.load(); }
  _esc(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }
}

window._invTable = new InvTable();

async function resendInv(id) {
  const { ok, data } = await Http.post(`/users/invitations/${id}/resend`, {});
  if (ok) { Toast.success('Renvoyée !', data.message); window._invTable?.load(); }
  else Toast.error('Erreur', data.message);
}

async function revokeInv(id) {
  Modal.confirm({
    title: 'Révoquer cette invitation ?',
    message: 'Le lien d\'invitation ne sera plus valide.',
    confirmText: 'Révoquer',
    type: 'danger',
    onConfirm: async () => {
      const { ok, data } = await Http.delete(`/users/invitations/${id}`);
      if (ok) { Toast.success('Révoquée', data.message); window._invTable?.load(); }
      else Toast.error('Erreur', data.message);
    }
  });
}
</script>
@endpush