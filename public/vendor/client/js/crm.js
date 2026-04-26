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

  function closeConfirmModalOnSuccess() {
    const confirm = document.getElementById('confirmModal');
    if (!confirm || !confirm.classList.contains('open')) return;
    if (confirm.dataset.busy === '1') {
      confirm.dataset.closeOnSuccess = '1';
      return;
    }
    confirm.classList.remove('open');
    confirm.dataset.closeOnSuccess = '0';
    if (!document.querySelector('.modal-overlay.open')) {
      document.body.style.overflow = '';
    }
  }

  function getContainer() {
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      document.body.appendChild(container);
    }
    return container;
  }

  const icons = {
    success: '✓',
    error:   '✕',
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
      <button class="toast-close" aria-label="Fermer">×</button>
    `;

    getContainer().appendChild(toast);

    const close = () => {
      toast.classList.add('removing');
      toast.addEventListener('animationend', () => toast.remove(), { once: true });
    };

    toast.querySelector('.toast-close').addEventListener('click', close);
    if (duration > 0) setTimeout(close, duration);

    if (type === 'success') {
      closeConfirmModalOnSuccess();
    }

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
  function setConfirmBusy(overlayEl, btn, busy, loadingText = 'Traitement...') {
    if (!overlayEl || !btn) return;
    overlayEl.dataset.busy = busy ? '1' : '0';

    const closeButtons = overlayEl.querySelectorAll('[data-modal-close]');
    closeButtons.forEach((el) => {
      if (busy) {
        el.setAttribute('disabled', 'disabled');
      } else {
        el.removeAttribute('disabled');
      }
    });

    if (busy) {
      btn.dataset.originalText = btn.innerHTML;
      btn.disabled = true;
      btn.classList.add('loading');
      btn.setAttribute('aria-busy', 'true');
      btn.innerHTML = loadingText;
      return;
    }

    btn.disabled = false;
    btn.classList.remove('loading');
    btn.removeAttribute('aria-busy');
    btn.innerHTML = btn.dataset.originalText || btn.innerHTML;

    if (overlayEl.dataset.closeOnSuccess === '1') {
      overlayEl.classList.remove('open');
      overlayEl.dataset.closeOnSuccess = '0';
      if (!document.querySelector('.modal-overlay.open')) {
        document.body.style.overflow = '';
      }
    }
  }

  function open(overlayEl) {
    if (!overlayEl) return;
    overlayEl.classList.add('open');
    document.body.style.overflow = 'hidden';
    overlayEl.dispatchEvent(new CustomEvent('crm:modal-open'));
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

  function close(overlayEl, force = false) {
    if (!overlayEl) return;
    if (!force && overlayEl.id === 'confirmModal' && overlayEl.dataset.busy === '1') return;
    const wasOpen = overlayEl.classList.contains('open');
    overlayEl.classList.remove('open');
    overlayEl.dataset.busy = '0';
    overlayEl.dataset.closeOnSuccess = '0';
    document.body.style.overflow = '';
    if (wasOpen) {
      overlayEl.dispatchEvent(new CustomEvent('crm:modal-close'));
    }
  }

  function confirm({ title, message, confirmText = 'Confirmer', type = 'danger', onConfirm }) {
    const overlay = document.getElementById('confirmModal');
    if (!overlay) { console.warn('confirmModal element not found'); return; }

    overlay.querySelector('[data-confirm-title]').textContent  = title;
    overlay.querySelector('[data-confirm-text]').textContent   = message;
    const btn = overlay.querySelector('[data-confirm-ok]');
    btn.textContent = confirmText;
    btn.className = `btn btn-${type}`;
    overlay.dataset.busy = '0';
    overlay.dataset.closeOnSuccess = '0';

    // Remove previous listeners
    const newBtn = btn.cloneNode(true);
    btn.parentNode.replaceChild(newBtn, btn);
    newBtn.textContent = confirmText;
    newBtn.className = `btn btn-${type}`;

    newBtn.addEventListener('click', async () => {
      if (overlay.dataset.busy === '1') return;
      const loadingText = /supprim/i.test(confirmText) ? 'Suppression...' : 'Traitement...';
      setConfirmBusy(overlay, newBtn, true, loadingText);
      try {
        if (typeof onConfirm === 'function') {
          await onConfirm();
        }
      } catch (err) {
        Toast.error('Erreur', err?.message || 'Une erreur est survenue.');
      } finally {
        setConfirmBusy(overlay, newBtn, false);
      }
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
      btn.dataset.originalAriaLabel = btn.getAttribute('aria-label') || '';
      btn.disabled = true;
      btn.classList.add('loading');
      btn.setAttribute('aria-busy', 'true');

      const loadingText = btn.getAttribute('data-loading-text');
      if (loadingText && loadingText.trim() !== '') {
        btn.innerHTML = loadingText;
      }
    } else {
      btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
      if (btn.dataset.originalAriaLabel !== undefined) {
        const v = btn.dataset.originalAriaLabel;
        if (v) btn.setAttribute('aria-label', v);
        else btn.removeAttribute('aria-label');
      }
      btn.disabled = false;
      btn.classList.remove('loading');
      btn.removeAttribute('aria-busy');
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
   GLOBAL REQUEST LOADER (TOP BAR)
   ============================================================ */
const RequestProgress = (() => {
  let root = null;
  let bar = null;
  let pending = 0;
  let progress = 0;
  let tickTimer = null;
  let hideTimer = null;

  function ensureDom() {
    if (root && bar) return true;

    const main = document.querySelector('.crm-main') || document.body;
    if (!main) return false;

    root = document.getElementById('crmTopLoader');
    if (!root) {
      root = document.createElement('div');
      root.id = 'crmTopLoader';
      root.className = 'crm-top-loader';
      root.innerHTML = '<div class="crm-top-loader-bar"></div>';
      main.insertBefore(root, main.firstChild || null);
    }

    bar = root.querySelector('.crm-top-loader-bar');
    return !!bar;
  }

  function setProgress(value) {
    if (!ensureDom()) return;
    progress = Math.max(progress, Math.min(100, value));
    bar.style.width = `${progress}%`;
  }

  function startTick() {
    clearInterval(tickTimer);
    tickTimer = setInterval(() => {
      if (progress >= 90) return;
      setProgress(progress + (Math.random() * 8) + 2);
    }, 220);
  }

  function stopTick() {
    clearInterval(tickTimer);
    tickTimer = null;
  }

  function start() {
    if (!ensureDom()) return;
    pending += 1;
    if (pending > 1) return;

    clearTimeout(hideTimer);
    progress = 0;
    bar.style.width = '0%';
    root.classList.add('active');
    requestAnimationFrame(() => setProgress(18));
    startTick();
  }

  function done() {
    if (pending > 0) pending -= 1;
    if (pending > 0) return;

    stopTick();
    setProgress(100);

    clearTimeout(hideTimer);
    hideTimer = setTimeout(() => {
      if (!root || !bar) return;
      root.classList.remove('active');
      progress = 0;
      bar.style.width = '0%';
    }, 260);
  }

  function wrapFetch() {
    if (window.__CRM_FETCH_WRAPPED__ || typeof window.fetch !== 'function') return;
    const nativeFetch = window.fetch.bind(window);
    window.fetch = (...args) => {
      start();
      return nativeFetch(...args).finally(done);
    };
    window.__CRM_FETCH_WRAPPED__ = true;
  }

  return { init: ensureDom, start, done, wrapFetch };
})();

window.RequestProgress = RequestProgress;
RequestProgress.wrapFetch();

/* ============================================================
   HTTP / AJAX HELPER
   ============================================================ */
const Http = (() => {
  function loginUrl() {
    return window.CRM_AUTH_ROUTES?.login || '/login';
  }

  function redirectToLogin(message = 'Votre session a expire. Redirection vers la connexion.') {
    if (window.Toast) {
      window.Toast.warning('Session expiree', message, 1600);
    }

    window.setTimeout(() => {
      window.location.href = loginUrl();
    }, 180);
  }

  function isLoginRedirectResponse(response) {
    if (!response || !response.redirected || !response.url) {
      return false;
    }

    try {
      const redirectedUrl = new URL(response.url, window.location.origin);
      const loginPath = new URL(loginUrl(), window.location.origin).pathname.replace(/\/+$/, '');

      return redirectedUrl.pathname.replace(/\/+$/, '') === loginPath;
    } catch (e) {
      return false;
    }
  }

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
    if (isLoginRedirectResponse(response)) {
      redirectToLogin();

      return {
        ok: false,
        status: 401,
        data: {
          success: false,
          message: 'Votre session a expire. Redirection vers la connexion.',
          redirect: loginUrl(),
        },
      };
    }

    const data = await response.json().catch(() => ({}));

    if (response.status === 401 || response.status === 419) {
      redirectToLogin(data?.message || 'Votre session a expire. Redirection vers la connexion.');
    }

    return { ok: response.ok, status: response.status, data };
  }

  const get  = (url, params = {}) => {
    const qs = new URLSearchParams(params).toString();
    return request(qs ? `${url}?${qs}` : url, { method: 'GET' });
  };
  const post   = (url, body) => request(url, { method: 'POST',   body: body instanceof FormData ? body : JSON.stringify(body) });
  const put    = (url, body) => request(url, { method: 'PUT',    body: JSON.stringify(body) });
  const del    = (url)       => request(url, { method: 'DELETE' });

  window.CrmAuth = Object.assign(window.CrmAuth || {}, {
    loginUrl,
    redirectToLogin,
    isLoginRedirectResponse,
  });

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

    if (!ok) { Toast.error('Erreur', 'Impossible de charger les données.'); return; }

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
            <h3>Aucun client trouvé</h3>
            <p>Modifiez vos filtres ou créez votre premier client.</p>
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
        <td style="color:var(--c-ink-40)">${c.phone || '—'}</td>
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
    if (info) info.textContent = `Affichage de ${from || 0} à ${to || 0} sur ${total || 0} clients`;

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
      message:     `Vous allez supprimer "${name}". Cette action est irréversible.`,
      confirmText: 'Supprimer',
      type:        'danger',
      onConfirm:   async () => {
        const { ok, data } = await Http.delete(`/clients/${id}`);
        if (ok) {
          Toast.success('Supprimé !', data.message || 'Client supprimé avec succès.');
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
    message:     'Cette action est irréversible.',
    confirmText: 'Supprimer',
    type:        'danger',
    onConfirm:   async () => {
      const { ok, data } = await Http.post(window.CRM_ROUTES?.bulkDelete, { ids });
      if (ok) {
        Toast.success('Succès', data.message);
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
    Toast.success('Succès', data.message);
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

    // Always submit multipart FormData (supports files on create/update).
    // Also skip empty file inputs to keep optional uploads truly optional.
    const payload = new FormData();
    formData.forEach((value, key) => {
      if (value instanceof File && value.size === 0 && !value.name) {
        return;
      }
      payload.append(key, value);
    });
    if (method !== 'POST' && !payload.get('_method')) {
      payload.set('_method', method);
    }

    const res = await Http.post(url, payload);

    if (btn) CrmForm.setLoading(btn, false);

    if (res.ok) {
      Toast.success('Succès !', res.data.message || 'Opération réussie.');
      const automationFlow = !options.skipAutomation
        && window.AutomationSuggestions
        && res.data?.automation?.should_prompt
        ? window.AutomationSuggestions.open(res.data.automation, {
            redirectUrl: res.data.redirect || null,
          })
        : null;

      let onSuccessResult;
      if (options.onSuccess) onSuccessResult = await options.onSuccess(res.data);

      if (res.data.redirect && !options.noRedirect && onSuccessResult !== false) {
        if (automationFlow && typeof automationFlow.finally === 'function') {
          automationFlow.finally(() => {
            window.location.href = res.data.redirect;
          });
        } else {
          setTimeout(() => window.location.href = res.data.redirect, 900);
        }
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
   AUTOMATION SUGGESTIONS
   ============================================================ */
const AutomationSuggestions = (() => {
  let booted = false;
  let state = {
    items: [],
    redirectUrl: null,
    resolver: null,
    title: '',
    subtitle: '',
  };

  function getModal() {
    return document.getElementById('automationSuggestionsModal');
  }

  function getEls() {
    const modal = getModal();
    if (!modal) return {};

    return {
      modal,
      title: modal.querySelector('[data-automation-title]'),
      subtitle: modal.querySelector('[data-automation-subtitle]'),
      list: modal.querySelector('[data-automation-list]'),
      empty: modal.querySelector('[data-automation-empty]'),
      counter: modal.querySelector('[data-automation-count]'),
      acceptAll: modal.querySelector('[data-automation-bulk="accept"]'),
      rejectAll: modal.querySelector('[data-automation-bulk="reject"]'),
      close: modal.querySelector('[data-automation-close]'),
    };
  }

  function esc(value) {
    const node = document.createElement('div');
    node.textContent = value == null ? '' : String(value);
    return node.innerHTML;
  }

  function routes() {
    return window.CRM_AUTOMATION_ROUTES || {};
  }

  function pendingItems() {
    return state.items.filter((item) => item.status === 'pending' && item.is_actionable);
  }

  function updateItems(updatedItems = []) {
    const updates = new Map((updatedItems || []).map((item) => [Number(item.id), item]));
    state.items = state.items.map((item) => updates.get(Number(item.id)) || item);
  }

  function render() {
    const { title, subtitle, list, empty, counter, acceptAll, rejectAll, close } = getEls();
    if (!list) return;

    if (title) title.textContent = state.title || 'Suggestions intelligentes';
    if (subtitle) subtitle.textContent = state.subtitle || 'Le CRM vous propose la suite la plus utile.';

    const pendingCount = pendingItems().length;
    if (counter) {
      counter.textContent = pendingCount > 0
        ? `${pendingCount} suggestion(s) en attente`
        : 'Toutes les suggestions ont ete traitees';
    }

    if (acceptAll) acceptAll.disabled = pendingCount === 0;
    if (rejectAll) rejectAll.disabled = pendingCount === 0;
    if (close) {
      close.innerHTML = pendingCount === 0
        ? '<i class="fas fa-check"></i> Continuer'
        : '<i class="fas fa-arrow-right"></i> Continuer';
    }

    if (!state.items.length) {
      list.innerHTML = '';
      if (empty) empty.style.display = 'block';
      return;
    }

    if (empty) empty.style.display = 'none';

    list.innerHTML = state.items.map((item) => {
      const actionable = item.status === 'pending' && item.is_actionable;
      const targetUrl = item.integration?.target_url ? esc(item.integration.target_url) : '';
      const openLink = targetUrl
        ? `<a class="btn btn-secondary btn-sm" href="${targetUrl}"><i class="fas fa-up-right-from-square"></i> Ouvrir</a>`
        : '';

      return `
        <article class="automation-card is-${esc(item.status)}" data-automation-id="${item.id}">
          <div class="automation-card-head">
            <div class="automation-card-icon" style="background:${esc(item.theme?.background || 'rgba(37,99,235,.12)')};color:${esc(item.theme?.color || '#2563eb')}">
              <i class="${esc(item.theme?.icon || 'fas fa-wand-magic-sparkles')}"></i>
            </div>
            <div class="automation-card-copy">
              <div class="automation-card-title-row">
                <h4>${esc(item.label)}</h4>
                <span class="automation-status-pill is-${esc(item.status)}">${esc(item.status_label || item.status)}</span>
              </div>
              <div class="automation-card-meta">
                <span><i class="fas fa-bolt"></i> ${esc(item.integration?.label || 'Automation')}</span>
                <span><i class="fas fa-signal"></i> ${esc(item.confidence_label || 'Pertinent')} (${esc(item.confidence_percent || 0)}%)</span>
                ${item.expires_human ? `<span><i class="fas fa-clock"></i> Expire ${esc(item.expires_human)}</span>` : ''}
              </div>
            </div>
          </div>
          <div class="automation-card-actions">
            ${openLink}
            ${actionable ? `<button type="button" class="btn btn-secondary btn-sm" data-automation-action="reject" data-id="${item.id}">${esc(item.secondary_label || 'Ignorer')}</button>` : ''}
            ${actionable ? `<button type="button" class="btn btn-primary btn-sm" data-automation-action="accept" data-id="${item.id}">${esc(item.primary_label || 'Accepter')}</button>` : ''}
          </div>
        </article>
      `;
    }).join('');
  }

  async function processSingle(id, action, button) {
    const endpointTemplate = action === 'accept' ? routes().accept : routes().reject;
    if (!endpointTemplate) {
      Toast.error('Automation', 'Routes automation indisponibles.');
      return;
    }

    if (button) CrmForm.setLoading(button, true);
    const response = await Http.post(endpointTemplate.replace('__ID__', id), {});
    if (button) CrmForm.setLoading(button, false);

    if (!response.ok) {
      Toast.error('Automation', response.data?.message || 'Operation automation impossible.');
      return;
    }

    updateItems(response.data?.data?.suggestions || []);
    render();
    Toast.success('Automation', response.data?.message || 'Suggestion mise a jour.');

    const eventData = response.data?.data?.event || {};
    const targetUrl = eventData?.target_url || eventData?.response?.target_url || null;
    if (action === 'accept' && eventData?.status === 'completed' && targetUrl) {
      const modal = getModal();
      if (modal) {
        Modal.close(modal);
      }
      window.setTimeout(() => {
        window.location.href = targetUrl;
      }, 220);
    }
  }

  async function processBulk(action, button) {
    const ids = pendingItems().map((item) => Number(item.id));
    if (!ids.length) return;

    const endpoint = action === 'accept' ? routes().bulkAccept : routes().bulkReject;
    if (!endpoint) {
      Toast.error('Automation', 'Routes automation indisponibles.');
      return;
    }

    if (button) CrmForm.setLoading(button, true);
    const response = await Http.post(endpoint, { ids });
    if (button) CrmForm.setLoading(button, false);

    if (!response.ok) {
      Toast.error('Automation', response.data?.message || 'Traitement groupe impossible.');
      return;
    }

    updateItems(response.data?.data?.suggestions || []);
    render();
    Toast.success('Automation', response.data?.message || 'Suggestions mises a jour.');

    const errorCount = Array.isArray(response.data?.data?.errors) ? response.data.data.errors.length : 0;
    if (errorCount > 0) {
      Toast.warning('Automation', `${errorCount} suggestion(s) n ont pas pu etre traitees.`);
    }
  }

  function init() {
    if (booted) return;
    const { modal } = getEls();
    if (!modal) return;

    modal.addEventListener('click', async (event) => {
      const singleAction = event.target.closest('[data-automation-action]');
      if (singleAction) {
        event.preventDefault();
        await processSingle(singleAction.dataset.id, singleAction.dataset.automationAction, singleAction);
        return;
      }

      const bulkAction = event.target.closest('[data-automation-bulk]');
      if (bulkAction) {
        event.preventDefault();
        await processBulk(bulkAction.dataset.automationBulk, bulkAction);
        return;
      }

      if (event.target.closest('[data-automation-close]')) {
        event.preventDefault();
        Modal.close(modal);
      }
    });

    modal.addEventListener('crm:modal-close', () => {
      const resolver = state.resolver;
      const redirectUrl = state.redirectUrl;
      state = { items: [], redirectUrl: null, resolver: null, title: '', subtitle: '' };
      if (typeof resolver === 'function') {
        resolver({ redirectUrl });
      }
    });

    booted = true;
  }

  function open(payload, options = {}) {
    init();
    const { modal } = getEls();
    if (!modal || !payload || !payload.should_prompt || !Array.isArray(payload.suggestions) || !payload.suggestions.length) {
      return null;
    }

    state = {
      items: payload.suggestions.slice(),
      redirectUrl: options.redirectUrl || null,
      resolver: null,
      title: payload.title || 'Suggestions intelligentes',
      subtitle: payload.subtitle || 'Le CRM vous propose les prochaines actions utiles.',
    };

    render();
    Modal.open(modal);

    return new Promise((resolve) => {
      state.resolver = resolve;
    });
  }

  return { open, render };
})();

window.AutomationSuggestions = AutomationSuggestions;

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
    chip.innerHTML = `${val}<button type="button" aria-label="Retirer">×</button>`;
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
  RequestProgress.init();

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
  const sidebar = document.querySelector('.crm-sidebar');
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebarBackdrop = document.getElementById('sidebarBackdrop');
  const closeSidebar = () => sidebar?.classList.remove('open');

  sidebarToggle?.addEventListener('click', () => {
    sidebar?.classList.toggle('open');
  });

  sidebarBackdrop?.addEventListener('click', closeSidebar);

  document.querySelectorAll('.sidebar-nav a').forEach(link => {
    link.addEventListener('click', () => {
      if (window.innerWidth <= 1024) {
        closeSidebar();
      }
    });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeSidebar();
    }
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 1024) {
      closeSidebar();
    }
  });

  const sidebarNav = document.querySelector('.sidebar-nav');
  if (sidebarNav) {
    let scrollHideTimer = null;
    sidebarNav.addEventListener('scroll', () => {
      sidebarNav.classList.add('is-scrolling');
      clearTimeout(scrollHideTimer);
      scrollHideTimer = setTimeout(() => {
        sidebarNav.classList.remove('is-scrolling');
      }, 700);
    }, { passive: true });
  }
});

}

