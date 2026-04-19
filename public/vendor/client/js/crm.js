if (!window.__CRM_CORE_LOADED__) {
  window.__CRM_CORE_LOADED__ = true;

/**
 * CRM SaaS â€” Core JavaScript
 * Toast notifications, Modals, Table manager, Form helpers, AJAX utils
 */

/* ============================================================
   TOAST SYSTEM
   ============================================================ */
const Toast = (() => {
  let container = null;

  function getContainer() {
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      document.body.appendChild(container);
    }
    return container;
  }

  const icons = {
    success: 'âœ“',
    error:   'âœ•',
    info:    'i',
    warning: '!',
  };

  function show(type, title, message = '', duration = 4500) {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
      <div class="toast-icon">${icons[type] || 'i'}</div>
      <div class="toast-body">
        <p class="toast-title">${title}</p>
        ${message ? `<p class="toast-message">${message}</p>` : ''}
      </div>
      <button class="toast-close" aria-label="Fermer">Ã—</button>
    `;

    getContainer().appendChild(toast);

    const close = () => {
      toast.classList.add('removing');
      toast.addEventListener('animationend', () => toast.remove(), { once: true });
    };

    toast.querySelector('.toast-close').addEventListener('click', close);
    if (duration > 0) setTimeout(close, duration);

    return { close };
  }

  return {
    success: (title, msg, d) => show('success', title, msg, d),
    error:   (title, msg, d) => show('error',   title, msg, d),
    info:    (title, msg, d) => show('info',    title, msg, d),
    warning: (title, msg, d) => show('warning', title, msg, d),
  };
})();

window.Toast = Toast;

/* ============================================================
   MODAL SYSTEM
   ============================================================ */
const Modal = (() => {
  function open(overlayEl) {
    if (!overlayEl) return;
    overlayEl.classList.add('open');
    document.body.style.overflow = 'hidden';
    // Close on backdrop click
    overlayEl.addEventListener('click', (e) => {
      if (e.target === overlayEl) close(overlayEl);
    }, { once: true });
    // Close on Escape
    const escHandler = (e) => {
      if (e.key === 'Escape') { close(overlayEl); document.removeEventListener('keydown', escHandler); }
    };
    document.addEventListener('keydown', escHandler);
  }

  function close(overlayEl) {
    if (!overlayEl) return;
    overlayEl.classList.remove('open');
    document.body.style.overflow = '';
  }

  function confirm({ title, message, confirmText = 'Confirmer', type = 'danger', onConfirm }) {
    const overlay = document.getElementById('confirmModal');
    if (!overlay) { console.warn('confirmModal element not found'); return; }

    overlay.querySelector('[data-confirm-title]').textContent  = title;
    overlay.querySelector('[data-confirm-text]').textContent   = message;
    const btn = overlay.querySelector('[data-confirm-ok]');
    btn.textContent = confirmText;
    btn.className = `btn btn-${type}`;

    // Remove previous listeners
    const newBtn = btn.cloneNode(true);
    btn.parentNode.replaceChild(newBtn, btn);
    newBtn.textContent = confirmText;
    newBtn.className = `btn btn-${type}`;

    newBtn.addEventListener('click', () => {
      close(overlay);
      if (typeof onConfirm === 'function') onConfirm();
    });

    open(overlay);
  }

  // Wire up all [data-modal-open] triggers
  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-modal-open]');
    if (trigger) {
      const target = document.getElementById(trigger.dataset.modalOpen);
      open(target);
    }
    const closeBtn = e.target.closest('[data-modal-close]');
    if (closeBtn) {
      const overlay = closeBtn.closest('.modal-overlay');
      close(overlay);
    }
  });

  return { open, close, confirm };
})();

window.Modal = Modal;

/* ============================================================
   FORM HELPERS
   ============================================================ */
const Form = (() => {
  function clearErrors(form) {
    form.querySelectorAll('.form-error').forEach(el => el.remove());
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
  }

  function showErrors(form, errors) {
    clearErrors(form);
    Object.entries(errors).forEach(([field, messages]) => {
      const input = form.querySelector(`[name="${field}"]`);
      if (!input) return;
      input.classList.add('is-invalid');
      const err = document.createElement('span');
      err.className = 'form-error';
      err.textContent = Array.isArray(messages) ? messages[0] : messages;
      input.parentNode.appendChild(err);
    });
    // Scroll to first error
    const first = form.querySelector('.is-invalid');
    if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function setLoading(btn, loading) {
    if (loading) {
      btn.dataset.originalText = btn.innerHTML;
      btn.disabled = true;
      btn.classList.add('loading');
    } else {
      btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
      btn.disabled = false;
      btn.classList.remove('loading');
    }
  }

  function serialize(form) {
    const data = new FormData(form);
    // handle checkboxes that aren't checked
    return data;
  }

  return { clearErrors, showErrors, setLoading, serialize };
})();

window.CrmForm = Form;

/* ============================================================
   HTTP / AJAX HELPER
   ============================================================ */
const Http = (() => {
  function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
  }

  async function request(url, options = {}) {
    const defaults = {
      headers: {
        'X-CSRF-TOKEN': getCsrf(),
        'X-Requested-With': 'XMLHttpRequest',
      },
    };

    // Merge headers
    const headers = { ...defaults.headers, ...(options.headers || {}) };

    // If body is FormData, don't set Content-Type (browser handles boundary)
    if (!(options.body instanceof FormData)) {
      headers['Content-Type'] = 'application/json';
      headers['Accept'] = 'application/json';
    }

    const response = await fetch(url, { ...options, headers });
    const data = await response.json().catch(() => ({}));
    return { ok: response.ok, status: response.status, data };
  }

  const get  = (url, params = {}) => {
    const qs = new URLSearchParams(params).toString();
    return request(qs ? `${url}?${qs}` : url, { method: 'GET' });
  };
  const post   = (url, body) => request(url, { method: 'POST',   body: body instanceof FormData ? body : JSON.stringify(body) });
  const put    = (url, body) => request(url, { method: 'PUT',    body: JSON.stringify(body) });
  const del    = (url)       => request(url, { method: 'DELETE' });

  return { get, post, put, delete: del };
})();

window.Http = Http;

/* ============================================================
   TABLE MANAGER
   ============================================================ */
class CrmTable {
  constructor(options) {
    this.options = Object.assign({
      tableId:      'clientsTable',
      tbodyId:      'clientsTableBody',
      dataUrl:      null,
      statsUrl:     null,
      perPage:      15,
      renderRow:    null,
      renderEmpty:  null,
    }, options);

    this.state = { page: 1, search: '', filters: {}, sort: '', dir: 'asc', total: 0, loading: false };
    this.selectedIds = new Set();
    this.debounceTimer = null;

    this._bindEvents();
    this.load();
    if (this.options.statsUrl) this.loadStats();
  }

  _bindEvents() {
    // Search
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
      searchInput.addEventListener('input', () => {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
          this.state.search = searchInput.value.trim();
          this.state.page = 1;
          this.load();
        }, 350);
      });
    }

    // Filter selects
    document.querySelectorAll('[data-filter]').forEach(sel => {
      sel.addEventListener('change', () => {
        this.state.filters[sel.dataset.filter] = sel.value;
        this.state.page = 1;
        this.load();
      });
    });

    // Reset filters
    document.getElementById('resetFilters')?.addEventListener('click', () => {
      this.state.search = '';
      this.state.filters = {};
      document.getElementById('searchInput') && (document.getElementById('searchInput').value = '');
      document.querySelectorAll('[data-filter]').forEach(s => s.value = '');
      this.state.page = 1;
      this.load();
    });

    // Select all
    document.getElementById('selectAll')?.addEventListener('change', (e) => {
      document.querySelectorAll('.row-check').forEach(cb => {
        cb.checked = e.target.checked;
        const id = parseInt(cb.dataset.id);
        e.target.checked ? this.selectedIds.add(id) : this.selectedIds.delete(id);
      });
      this._updateBulkBar();
    });

    // Tbody: delegate row checkbox
    const tbody = document.getElementById(this.options.tbodyId);
    if (tbody) {
      tbody.addEventListener('change', (e) => {
        if (e.target.classList.contains('row-check')) {
          const id = parseInt(e.target.dataset.id);
          e.target.checked ? this.selectedIds.add(id) : this.selectedIds.delete(id);
          this._updateBulkBar();
        }
      });
    }

    // Sortable headers
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

    const params = {
      page:     this.state.page,
      per_page: this.options.perPage,
      search:   this.state.search,
      sort_by:  this.state.sort,
      sort_dir: this.state.dir,
      ...this.state.filters,
    };

    const { ok, data } = await Http.get(this.options.dataUrl, params);
    this.state.loading = false;

    if (!ok) { Toast.error('Erreur', 'Impossible de charger les donnÃ©es.'); return; }

    this.state.total = data.total || 0;
    this._renderRows(data.data || []);
    this._renderPagination(data);
    this._updateCount(data.total);
  }

  async loadStats() {
    const { ok, data } = await Http.get(this.options.statsUrl);
    if (!ok || !data.data) return;
    const stats = data.data;
    this._setStat('totalClients',  stats.total);
    this._setStat('activeClients', stats.active);
    this._setStat('pendingClients',stats.pending);
    this._setStat('totalRevenue',  this._formatCurrency(stats.revenue_total));
  }

  _setStat(id, val) {
    const el = document.getElementById(id);
    if (el) {
      el.textContent = val;
      el.classList.add('stat-animate');
    }
  }

  _formatCurrency(n) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(n || 0);
  }

  _showSkeletons(count = 5) {
    const tbody = document.getElementById(this.options.tbodyId);
    if (!tbody) return;
    tbody.innerHTML = Array.from({ length: count }, () => `
      <tr>
        ${Array.from({ length: 8 }, () => `<td><div class="skeleton" style="height:14px;border-radius:4px;"></div></td>`).join('')}
      </tr>
    `).join('');
  }

  _renderRows(rows) {
    const tbody = document.getElementById(this.options.tbodyId);
    if (!tbody) return;

    if (!rows.length) {
      tbody.innerHTML = `
        <tr><td colspan="8">
          <div class="table-empty">
            <div class="table-empty-icon"><i class="fas fa-users"></i></div>
            <h3>Aucun client trouvÃ©</h3>
            <p>Modifiez vos filtres ou crÃ©ez votre premier client.</p>
            <a href="${window.CRM_ROUTES?.create || '#'}" class="btn btn-primary">
              <i class="fas fa-plus"></i> Nouveau client
            </a>
          </div>
        </td></tr>
      `;
      return;
    }

    tbody.innerHTML = rows.map(client => this._renderRow(client)).join('');

    // Re-check selected
    tbody.querySelectorAll('.row-check').forEach(cb => {
      cb.checked = this.selectedIds.has(parseInt(cb.dataset.id));
    });
  }

  _renderRow(c) {
    if (typeof this.options.renderRow === 'function') return this.options.renderRow(c);

    const avatarColors = ['#2563eb','#7c3aed','#0891b2','#059669','#d97706','#dc2626'];
    const color = avatarColors[(c.company_name?.charCodeAt(0) || 0) % avatarColors.length];
    const initials = (c.company_name || '??').substring(0, 2).toUpperCase();
    const statusBadge = `<span class="badge badge-${c.status}"><span class="badge-dot" style="background:currentColor"></span>${this._statusLabel(c.status)}</span>`;
    const typeBadge   = `<span class="badge badge-${c.type}">${this._typeLabel(c.type)}</span>`;
    const revenue = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(c.revenue || 0);

    return `
      <tr data-id="${c.id}" class="${this.selectedIds.has(c.id) ? 'selected' : ''}">
        <td style="width:40px;">
          <input type="checkbox" class="row-check" data-id="${c.id}" ${this.selectedIds.has(c.id) ? 'checked' : ''}>
        </td>
        <td>
          <div class="client-cell">
            <div class="client-avatar" style="background:${color}">${initials}</div>
            <div>
              <div class="client-name">${this._esc(c.company_name)}</div>
              <div class="client-sub">${this._esc(c.contact_name || c.email || '')}</div>
            </div>
          </div>
        </td>
        <td>${typeBadge}</td>
        <td style="color:var(--c-ink-60)">${this._esc(c.email)}</td>
        <td style="color:var(--c-ink-40)">${c.phone || 'â€”'}</td>
        <td>${statusBadge}</td>
        <td style="font-weight:500">${revenue}</td>
        <td>
          <div class="row-actions">
            <a href="/clients/${c.id}" class="btn-icon" title="Voir"><i class="fas fa-eye"></i></a>
            <a href="/clients/${c.id}/edit" class="btn-icon" title="Modifier"><i class="fas fa-pen"></i></a>
            <button class="btn-icon danger" onclick="CrmTable.deleteClient(${c.id},'${this._esc(c.company_name)}')" title="Supprimer">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </td>
      </tr>
    `;
  }

  _statusLabel(s) { return { actif:'Actif', inactif:'Inactif', en_attente:'En attente', suspendu:'Suspendu' }[s] || s; }
  _typeLabel(t)   { return { entreprise:'Entreprise', particulier:'Particulier', startup:'Startup', association:'Association', public:'Public' }[t] || t; }
  _esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

  _renderPagination(data) {
    const wrap = document.getElementById('paginationControls');
    const info = document.getElementById('paginationInfo');
    if (!wrap) return;

    const { current_page, last_page, from, to, total } = data;
    if (info) info.textContent = `Affichage de ${from || 0} Ã  ${to || 0} sur ${total || 0} clients`;

    const pages = [];
    for (let i = Math.max(1, current_page - 2); i <= Math.min(last_page, current_page + 2); i++) pages.push(i);

    wrap.innerHTML = `
      <button class="page-btn" ${current_page <= 1 ? 'disabled' : ''} onclick="window._crmTable?.goTo(${current_page - 1})">
        <i class="fas fa-chevron-left"></i>
      </button>
      ${pages.map(p => `<button class="page-btn ${p === current_page ? 'active' : ''}" onclick="window._crmTable?.goTo(${p})">${p}</button>`).join('')}
      <button class="page-btn" ${current_page >= last_page ? 'disabled' : ''} onclick="window._crmTable?.goTo(${current_page + 1})">
        <i class="fas fa-chevron-right"></i>
      </button>
    `;
  }

  goTo(page) {
    this.state.page = page;
    this.load();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  _updateCount(total) {
    const el = document.querySelector('.table-count');
    if (el) el.textContent = `${total || 0} client${(total || 0) !== 1 ? 's' : ''}`;
  }

  _updateBulkBar() {
    const bar   = document.getElementById('bulkBar');
    const count = document.getElementById('selectedCount');
    if (!bar) return;
    const n = this.selectedIds.size;
    bar.classList.toggle('visible', n > 0);
    if (count) count.textContent = n;
    document.getElementById('selectAll') && (document.getElementById('selectAll').indeterminate = n > 0);
  }

  getSelectedIds() { return [...this.selectedIds]; }

  /* Static helper called from inline onclick */
  static deleteClient(id, name) {
    Modal.confirm({
      title:       'Supprimer ce client ?',
      message:     `Vous allez supprimer "${name}". Cette action est irrÃ©versible.`,
      confirmText: 'Supprimer',
      type:        'danger',
      onConfirm:   async () => {
        const { ok, data } = await Http.delete(`/clients/${id}`);
        if (ok) {
          Toast.success('SupprimÃ© !', data.message || 'Client supprimÃ© avec succÃ¨s.');
          window._crmTable?.load();
          window._crmTable?.loadStats();
        } else {
          Toast.error('Erreur', data.message || 'Impossible de supprimer ce client.');
        }
      },
    });
  }
}

window.CrmTable = CrmTable;

/* ============================================================
   BULK OPERATIONS (wired up in index page)
   ============================================================ */
async function bulkDelete() {
  const ids = window._crmTable?.getSelectedIds();
  if (!ids?.length) return;
  Modal.confirm({
    title:       `Supprimer ${ids.length} client(s) ?`,
    message:     'Cette action est irrÃ©versible.',
    confirmText: 'Supprimer',
    type:        'danger',
    onConfirm:   async () => {
      const { ok, data } = await Http.post(window.CRM_ROUTES?.bulkDelete, { ids });
      if (ok) {
        Toast.success('SuccÃ¨s', data.message);
        window._crmTable?.load();
        window._crmTable?.loadStats();
        window._crmTable?.selectedIds.clear();
        window._crmTable?._updateBulkBar();
      } else {
        Toast.error('Erreur', data.message);
      }
    },
  });
}

async function bulkStatus(status) {
  const ids = window._crmTable?.getSelectedIds();
  if (!ids?.length) return;
  const { ok, data } = await Http.post(window.CRM_ROUTES?.bulkStatus, { ids, status });
  if (ok) {
    Toast.success('SuccÃ¨s', data.message);
    window._crmTable?.load();
    window._crmTable?.selectedIds.clear();
    window._crmTable?._updateBulkBar();
  } else {
    Toast.error('Erreur', data.message);
  }
}

/* ============================================================
   AJAX FORM SUBMISSION
   ============================================================ */
function ajaxForm(formId, options = {}) {
  const form = document.getElementById(formId);
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('[type=submit]');

    CrmForm.clearErrors(form);
    if (btn) CrmForm.setLoading(btn, true);

    const formData = new FormData(form);
    const method = (formData.get('_method') || form.method || 'POST').toUpperCase();
    const url    = form.action;

    let res;
    if (method === 'POST') {
      res = await Http.post(url, formData);
    } else {
      // For PUT/PATCH, we need to handle FormData â†’ JSON
      const body = {};
      formData.forEach((v, k) => { if (k !== '_method' && k !== '_token') body[k] = v; });
      res = await Http.put(url, body);
    }

    if (btn) CrmForm.setLoading(btn, false);

    if (res.ok) {
      Toast.success('SuccÃ¨s !', res.data.message || 'OpÃ©ration rÃ©ussie.');
      if (options.onSuccess) options.onSuccess(res.data);
      if (res.data.redirect && !options.noRedirect) {
        setTimeout(() => window.location.href = res.data.redirect, 900);
      }
    } else if (res.status === 422) {
      CrmForm.showErrors(form, res.data.errors || {});
      Toast.error('Validation', res.data.message || 'Veuillez corriger les erreurs.');
    } else {
      Toast.error('Erreur', res.data.message || 'Une erreur est survenue.');
    }
  });
}

window.ajaxForm = ajaxForm;

/* ============================================================
   TAGS INPUT
   ============================================================ */
function initTagsInput(inputId, hiddenName) {
  const container = document.getElementById(inputId + '_wrap');
  if (!container) return;
  const textInput = container.querySelector('.tags-input');
  const tags = new Set();

  function addTag(val) {
    val = val.trim().toLowerCase();
    if (!val || tags.has(val)) return;
    tags.add(val);
    const chip = document.createElement('span');
    chip.className = 'tag-chip';
    chip.innerHTML = `${val}<button type="button" aria-label="Retirer">Ã—</button>`;
    chip.querySelector('button').addEventListener('click', () => { tags.delete(val); chip.remove(); syncHidden(); });
    container.insertBefore(chip, textInput);
    syncHidden();
  }

  function syncHidden() {
    container.querySelectorAll(`input[name="${hiddenName}[]"]`).forEach(i => i.remove());
    tags.forEach(t => {
      const inp = document.createElement('input');
      inp.type = 'hidden'; inp.name = `${hiddenName}[]`; inp.value = t;
      container.appendChild(inp);
    });
  }

  textInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ',') {
      e.preventDefault();
      addTag(textInput.value);
      textInput.value = '';
    }
  });
  container.addEventListener('click', () => textInput.focus());
}

/* ============================================================
   DROPDOWN TOGGLE
   ============================================================ */
document.addEventListener('click', (e) => {
  if (e.target.closest('[data-dropdown-toggle]')) {
    const target = e.target.closest('[data-dropdown-toggle]').closest('.dropdown');
    target?.classList.toggle('open');
    return;
  }
  document.querySelectorAll('.dropdown.open').forEach(d => {
    if (!d.contains(e.target)) d.classList.remove('open');
  });
});

/* ============================================================
   AUTO-INIT on DOMContentLoaded
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
  // Mark current nav link as active
  const currentPath = window.location.pathname;
  document.querySelectorAll('.sidebar-nav a').forEach(link => {
    if (link.getAttribute('href') === currentPath || currentPath.startsWith(link.getAttribute('href') + '/')) {
      link.classList.add('active');
    }
  });

  // Init tags inputs
  document.querySelectorAll('[data-tags-input]').forEach(el => {
    initTagsInput(el.id, el.dataset.tagsInput);
  });

  // Mobile sidebar toggle
  document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    document.querySelector('.crm-sidebar')?.classList.toggle('open');
  });
});

}

