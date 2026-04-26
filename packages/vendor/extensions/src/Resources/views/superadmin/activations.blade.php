@extends('layouts.global')

@section('title', 'Activations Extensions')

@section('breadcrumb')
  <a href="{{ route('superadmin.extensions.index') }}">Extensions</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Activations tenants</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <h1>Activations des extensions</h1>
    <p>Suivi global des installations par tenant</p>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('superadmin.extensions.index') }}" class="btn btn-secondary">
      <i class="fas fa-arrow-left"></i> Retour catalogue
    </a>
  </div>
</div>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Liste des activations</span>
    <span class="table-count" id="activationCount">0</span>
    <div class="table-spacer"></div>
    <select class="filter-select" id="statusFilter">
      <option value="">Tous statuts</option>
      @foreach($activationStatuses as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>
    <button class="btn btn-ghost btn-sm" id="resetFilters" title="Réinitialiser">
      <i class="fas fa-rotate-left"></i>
    </button>
  </div>

  <table class="crm-table">
    <thead>
      <tr>
        <th>Extension</th>
        <th>Tenant</th>
        <th>Statut</th>
        <th>Activée le</th>
        <th>Prix</th>
        <th>Activée par</th>
        <th style="text-align:right;padding-right:20px;">Actions</th>
      </tr>
    </thead>
    <tbody id="activationTableBody"></tbody>
  </table>

  <div class="table-pagination">
    <span class="pagination-info" id="paginationInfo"></span>
    <div class="pagination-spacer"></div>
    <div class="pagination-pages" id="paginationControls"></div>
  </div>
</div>

<div class="modal-overlay" id="suspendModal">
  <div class="modal modal-sm">
    <div class="modal-header">
      <div class="modal-title">Suspendre l'activation</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Raison <span class="required">*</span></label>
        <textarea id="suspendReason" class="form-control" rows="3" placeholder="Indiquez la raison de suspension..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Annuler</button>
      <button class="btn btn-danger" id="confirmSuspend" data-loading-text="Suspension...">Suspendre</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
window.EXT_ACTIVATIONS_ROUTES = {
  data: '{{ route('superadmin.extensions.activations.data') }}',
  suspendBase: '{{ url('/superadmin/extensions/activations') }}',
};

class ExtensionActivationsPage {
  constructor() {
    this.state = {
      page: 1,
      per_page: 20,
      status: '',
      total: 0,
    };
    this.pendingSuspendId = null;

    this.bindEvents();
    this.load();
  }

  bindEvents() {
    document.getElementById('statusFilter')?.addEventListener('change', (event) => {
      this.state.status = String(event.target.value || '');
      this.state.page = 1;
      this.load();
    });

    document.getElementById('resetFilters')?.addEventListener('click', () => {
      this.state.status = '';
      this.state.page = 1;
      const status = document.getElementById('statusFilter');
      if (status) status.value = '';
      this.load();
    });

    document.getElementById('confirmSuspend')?.addEventListener('click', async () => {
      await this.suspendCurrentActivation();
    });
  }

  async load() {
    const tbody = document.getElementById('activationTableBody');
    if (tbody) {
      tbody.innerHTML = Array.from({ length: 6 }).map(() =>
        `<tr>${Array.from({ length: 7 }).map(() => '<td><div class="skeleton" style="height:12px;border-radius:4px;"></div></td>').join('')}</tr>`
      ).join('');
    }

    const { ok, data } = await Http.get(window.EXT_ACTIVATIONS_ROUTES.data, {
      page: this.state.page,
      per_page: this.state.per_page,
      status: this.state.status,
    });

    if (!ok) {
      Toast.error('Erreur', data?.message || 'Impossible de charger les activations.');
      return;
    }

    const rows = Array.isArray(data.data) ? data.data : [];
    this.state.total = Number(data.total || 0);

    this.renderRows(rows);
    this.renderPagination(data);

    const count = document.getElementById('activationCount');
    if (count) count.textContent = String(this.state.total);
  }

  renderRows(rows) {
    const tbody = document.getElementById('activationTableBody');
    if (!tbody) return;

    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="7"><div class="table-empty">
        <div class="table-empty-icon"><i class="fas fa-plug-circle-xmark"></i></div>
        <h3>Aucune activation</h3>
        <p>Aucune donnée trouvée pour ce filtre.</p>
      </div></td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map((row) => {
      const status = this.statusBadge(row.status);
      const extensionName = this.esc(row.extension?.name || '-');
      const tenantName = this.esc(row.tenant?.name || '-');
      const activatedBy = this.esc(row.activated_by_user?.name || '-');
      const activatedAt = row.activated_at ? this.formatDate(row.activated_at) : '-';
      const price = Number(row.price_paid || 0) > 0
        ? `${Number(row.price_paid).toFixed(2)} ${this.esc(row.currency || 'EUR')}`
        : '<span style="color:var(--c-ink-40);">Gratuit</span>';

      const actions = row.status === 'suspended'
        ? `<button class="btn-icon" title="Restaurer" data-action="restore" data-id="${Number(row.id)}"><i class="fas fa-check-circle"></i></button>`
        : `<button class="btn-icon danger" title="Suspendre" data-action="suspend" data-id="${Number(row.id)}"><i class="fas fa-ban"></i></button>`;

      return `<tr>
        <td style="font-weight:var(--fw-semi);">${extensionName}</td>
        <td>${tenantName}</td>
        <td>${status}</td>
        <td>${activatedAt}</td>
        <td>${price}</td>
        <td>${activatedBy}</td>
        <td><div class="row-actions" style="justify-content:flex-end;padding-right:4px;">${actions}</div></td>
      </tr>`;
    }).join('');

    tbody.querySelectorAll('[data-action="suspend"]').forEach((button) => {
      button.addEventListener('click', () => this.openSuspendModal(Number(button.dataset.id || 0)));
    });
    tbody.querySelectorAll('[data-action="restore"]').forEach((button) => {
      button.addEventListener('click', () => this.restoreActivation(Number(button.dataset.id || 0)));
    });
  }

  renderPagination(data) {
    const info = document.getElementById('paginationInfo');
    const pages = document.getElementById('paginationControls');
    if (!info || !pages) return;

    const current = Number(data.current_page || 1);
    const last = Number(data.last_page || 1);
    const perPage = Number(data.per_page || this.state.per_page);
    const total = Number(data.total || 0);
    const from = total === 0 ? 0 : ((current - 1) * perPage) + 1;
    const to = Math.min(current * perPage, total);

    info.textContent = total ? `Affichage ${from}-${to} sur ${total}` : 'Aucun résultat';

    pages.innerHTML = '';
    if (last <= 1) return;

    const addButton = (label, target, disabled, active = false) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = `pagination-btn ${active ? 'active' : ''}`;
      btn.textContent = label;
      btn.disabled = disabled;
      btn.addEventListener('click', () => {
        if (this.state.page === target) return;
        this.state.page = target;
        this.load();
      });
      pages.appendChild(btn);
    };

    addButton('‹', Math.max(1, current - 1), current <= 1);
    for (let page = 1; page <= last; page += 1) {
      if (page === 1 || page === last || Math.abs(page - current) <= 1) {
        addButton(String(page), page, false, page === current);
      }
    }
    addButton('›', Math.min(last, current + 1), current >= last);
  }

  openSuspendModal(id) {
    if (!id) return;
    this.pendingSuspendId = id;
    const reason = document.getElementById('suspendReason');
    if (reason) reason.value = '';
    Modal.open(document.getElementById('suspendModal'));
  }

  async suspendCurrentActivation() {
    if (!this.pendingSuspendId) return;
    const button = document.getElementById('confirmSuspend');
    const reasonInput = document.getElementById('suspendReason');
    const reason = String(reasonInput?.value || '').trim();
    if (!reason) {
      Toast.warning('Requis', 'Veuillez saisir une raison.');
      return;
    }

    if (button) CrmForm.setLoading(button, true);
    const { ok, data } = await Http.post(`${window.EXT_ACTIVATIONS_ROUTES.suspendBase}/${this.pendingSuspendId}/suspend`, { reason });
    if (button) CrmForm.setLoading(button, false);

    if (!ok) {
      Toast.error('Erreur', data?.message || 'Suspension impossible.');
      return;
    }

    Modal.close(document.getElementById('suspendModal'));
    Toast.success('Succès', data.message || 'Activation suspendue.');
    this.load();
  }

  async restoreActivation(id) {
    if (!id) return;
    const { ok, data } = await Http.post(`${window.EXT_ACTIVATIONS_ROUTES.suspendBase}/${id}/restore`, {});
    if (!ok) {
      Toast.error('Erreur', data?.message || 'Restauration impossible.');
      return;
    }
    Toast.success('Succès', data.message || 'Activation restaurée.');
    this.load();
  }

  statusBadge(status) {
    const s = String(status || '').toLowerCase();
    if (s === 'active') return '<span class="badge badge-actif">Active</span>';
    if (s === 'trial') return '<span class="badge badge-info">Essai</span>';
    if (s === 'suspended') return '<span class="badge badge-inactif">Suspendue</span>';
    if (s === 'inactive') return '<span class="badge badge-inactif">Inactive</span>';
    if (s === 'pending') return '<span class="badge badge-warning">En attente</span>';
    return `<span class="badge badge-secondary">${this.esc(status || '-')}</span>`;
  }

  formatDate(value) {
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '-';
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
  }

  esc(value) {
    const div = document.createElement('div');
    div.textContent = String(value ?? '');
    return div.innerHTML;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  new ExtensionActivationsPage();
});
</script>
@endpush

