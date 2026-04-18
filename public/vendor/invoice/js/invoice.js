/**
 * CRM SaaS — Module Facturation
 * AJAX table, dynamic line items, calculs temps réel, currency, toast, modals
 */

'use strict';

/* ============================================================
   TOAST (inline si crm.js non chargé)
   ============================================================ */
const Toast = window.Toast ?? (() => {
  function show(type, title, msg = '', dur = 4500) {
    let c = document.querySelector('.toast-container');
    if (!c) { c = document.createElement('div'); c.className = 'toast-container'; document.body.appendChild(c); }
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<div class="toast-icon">${{success:'✓',error:'✕',warning:'!',info:'i'}[type]||'i'}</div>
      <div class="toast-body"><p class="toast-title">${title}</p>${msg?`<p class="toast-message">${msg}</p>`:''}</div>
      <button class="toast-close">×</button>`;
    c.appendChild(t);
    const close = () => { t.classList.add('removing'); t.addEventListener('animationend', ()=>t.remove(),{once:true}); };
    t.querySelector('.toast-close').onclick = close;
    if (dur > 0) setTimeout(close, dur);
  }
  return {
    success: (t,m,d) => show('success',t,m,d),
    error:   (t,m,d) => show('error',t,m,d),
    warning: (t,m,d) => show('warning',t,m,d),
    info:    (t,m,d) => show('info',t,m,d),
  };
})();

window.Toast = Toast;

/* ============================================================
   CURRENCY FORMATTER
   ============================================================ */
const CurrencyFmt = (() => {
  const currencies = window.INVOICE_CURRENCIES ?? {};

  function format(amount, code) {
    const cfg = currencies[code] || { symbol: code, position: 'after', decimals: 2, thousands: ' ', decimal_sep: ',' };
    const num  = parseFloat(amount || 0);
    const fixed = num.toFixed(cfg.decimals);
    const [int, dec] = fixed.split('.');
    const intFmt = int.replace(/\B(?=(\d{3})+(?!\d))/g, cfg.thousands);
    const formatted = dec !== undefined ? `${intFmt}${cfg.decimal_sep}${dec}` : intFmt;
    return cfg.position === 'before'
      ? `${cfg.symbol}${formatted}`
      : `${formatted} ${cfg.symbol}`;
  }

  return { format };
})();

window.CurrencyFmt = CurrencyFmt;

/* ============================================================
   INVOICE AJAX TABLE (index)
   ============================================================ */
const InvoiceTable = (() => {
  let state = {
    page: 1, perPage: 15, total: 0,
    sort: 'issue_date', order: 'desc',
    filters: {},
    loading: false,
    selected: new Set(),
  };

  let tbody, paginationBar, statsBar;
  let dataUrl = '/invoices/data/table';
  let statsUrl = '/invoices/data/stats';

  function init(opts = {}) {
    if (opts.dataUrl)  dataUrl  = opts.dataUrl;
    if (opts.statsUrl) statsUrl = opts.statsUrl;
    if (opts.perPage)  state.perPage = opts.perPage;

    tbody        = document.getElementById('inv-table-body');
    paginationBar = document.getElementById('inv-pagination');
    statsBar      = document.getElementById('inv-stats-bar');

    if (!tbody) return;

    // Filtres
    document.querySelectorAll('[data-inv-filter]').forEach(el => {
      el.addEventListener('input',  debounce(applyFilters, 380));
      el.addEventListener('change', applyFilters);
    });

    // Sort
    document.querySelectorAll('[data-sort]').forEach(th => {
      th.style.cursor = 'pointer';
      th.addEventListener('click', () => {
        const col = th.dataset.sort;
        if (state.sort === col) {
          state.order = state.order === 'asc' ? 'desc' : 'asc';
        } else {
          state.sort = col; state.order = 'asc';
        }
        document.querySelectorAll('[data-sort]').forEach(t => t.classList.remove('sort-asc','sort-desc'));
        th.classList.add(`sort-${state.order}`);
        load();
      });
    });

    // Select all
    const selectAll = document.getElementById('inv-select-all');
    if (selectAll) {
      selectAll.addEventListener('change', () => {
        document.querySelectorAll('.inv-row-check').forEach(cb => {
          cb.checked = selectAll.checked;
          selectAll.checked ? state.selected.add(+cb.value) : state.selected.delete(+cb.value);
        });
        updateBulkBar();
      });
    }

    load();
    loadStats();
  }

  function applyFilters() {
    const filters = {};
    document.querySelectorAll('[data-inv-filter]').forEach(el => {
      if (el.value) filters[el.dataset.invFilter] = el.value;
    });
    state.filters = filters;
    state.page = 1;
    load();
  }

  async function load() {
    if (state.loading) return;
    state.loading = true;
    setLoading(true);

    const params = new URLSearchParams({
      page:     state.page,
      per_page: state.perPage,
      sort:     state.sort,
      order:    state.order,
      ...state.filters,
    });

    try {
      const res  = await fetch(`${dataUrl}?${params}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const json = await res.json();
      renderRows(json.data);
      renderPagination(json);
    } catch (e) {
      Toast.error('Erreur', 'Impossible de charger les données.');
      console.error(e);
    } finally {
      state.loading = false;
      setLoading(false);
    }
  }

  function renderRows(rows) {
    if (!tbody) return;
    if (!rows || rows.length === 0) {
      tbody.innerHTML = `<tr><td colspan="9" class="empty-state">
        <div style="text-align:center;padding:48px 20px;color:var(--c-ink-40)">
          <div style="font-size:36px;margin-bottom:12px">📄</div>
          <p style="font-weight:600;color:var(--c-ink-60)">Aucune facture trouvée</p>
          <p style="font-size:12px;margin-top:4px">Modifiez vos filtres ou créez une nouvelle facture</p>
        </div>
      </td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map(inv => {
      const currency = inv.currency || 'EUR';
      return `
      <tr data-id="${inv.id}" class="${state.selected.has(inv.id) ? 'selected' : ''}">
        <td><input type="checkbox" class="inv-row-check" value="${inv.id}" ${state.selected.has(inv.id)?'checked':''}></td>
        <td>
          <a href="/invoices/${inv.id}" class="inv-number">${inv.number}</a>
          ${inv.reference ? `<div style="font-size:11px;color:var(--c-ink-40)">${inv.reference}</div>` : ''}
        </td>
        <td>
          <div style="font-weight:500">${inv.client?.company_name || '—'}</div>
          <div style="font-size:11px;color:var(--c-ink-40)">${inv.client?.email || ''}</div>
        </td>
        <td><span class="status-badge badge-${inv.status}">${inv.status_label}</span></td>
        <td>${inv.issue_date ? formatDate(inv.issue_date) : '—'}</td>
        <td class="${inv.is_overdue ? 'amount-cell due' : ''}">${inv.due_date ? formatDate(inv.due_date) : '—'}</td>
        <td>
          <span class="currency-badge">${currency}</span>
        </td>
        <td class="amount-cell">${CurrencyFmt.format(inv.total, currency)}</td>
        <td class="amount-cell ${+inv.amount_due > 0 ? 'due' : 'paid'}">
          ${+inv.amount_due > 0 ? CurrencyFmt.format(inv.amount_due, currency) : '✓ Soldé'}
        </td>
        <td>
          <div style="display:flex;gap:4px;justify-content:flex-end">
            <a href="/invoices/${inv.id}" class="btn btn-outline btn-icon btn-sm" title="Voir">👁</a>
            <a href="/invoices/${inv.id}/edit" class="btn btn-outline btn-icon btn-sm" title="Modifier">✏️</a>
            <a href="/invoices/${inv.id}/pdf" class="btn btn-outline btn-icon btn-sm" title="PDF">📄</a>
            <button onclick="InvoiceTable.confirmDelete(${inv.id})" class="btn btn-outline btn-icon btn-sm" style="color:var(--c-danger)" title="Supprimer">🗑</button>
          </div>
        </td>
      </tr>`;
    }).join('');

    // Row check handlers
    tbody.querySelectorAll('.inv-row-check').forEach(cb => {
      cb.addEventListener('change', () => {
        cb.checked ? state.selected.add(+cb.value) : state.selected.delete(+cb.value);
        cb.closest('tr').classList.toggle('selected', cb.checked);
        updateBulkBar();
      });
    });
  }

  function renderPagination(json) {
    state.total = json.total;
    if (!paginationBar) return;

    const { current_page, last_page, from, to, total } = json;
    paginationBar.innerHTML = `
      <div class="pagination-info">
        Affichage de <strong>${from||0}</strong> à <strong>${to||0}</strong> sur <strong>${total}</strong> factures
      </div>
      <div class="pagination-controls">
        <button class="page-btn" onclick="InvoiceTable.goPage(1)"      ${current_page===1?'disabled':''}>«</button>
        <button class="page-btn" onclick="InvoiceTable.goPage(${current_page-1})" ${current_page===1?'disabled':''}>‹</button>
        ${getPagesRange(current_page, last_page).map(p =>
          p === '...'
            ? `<span class="page-btn" style="cursor:default;opacity:.5">…</span>`
            : `<button class="page-btn ${p===current_page?'active':''}" onclick="InvoiceTable.goPage(${p})">${p}</button>`
        ).join('')}
        <button class="page-btn" onclick="InvoiceTable.goPage(${current_page+1})" ${current_page===last_page?'disabled':''}>›</button>
        <button class="page-btn" onclick="InvoiceTable.goPage(${last_page})"      ${current_page===last_page?'disabled':''}>»</button>
      </div>`;
  }

  function getPagesRange(current, last) {
    if (last <= 7) return Array.from({length: last}, (_,i) => i+1);
    if (current <= 4) return [1,2,3,4,5,'...',last];
    if (current >= last-3) return [1,'...',last-4,last-3,last-2,last-1,last];
    return [1,'...',current-1,current,current+1,'...',last];
  }

  async function loadStats() {
    if (!statsBar) return;
    try {
      const res  = await fetch(statsUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const json = await res.json();
      if (json.success) renderStats(json.data);
    } catch(e) {}
  }

  function renderStats(data) {
    if (!statsBar) return;
    const inv = data.invoices;
    const cur = window.DEFAULT_CURRENCY || 'EUR';
    statsBar.innerHTML = `
      <div class="stat-card revenue">
        <div class="stat-icon">💰</div>
        <div class="stat-body">
          <div class="stat-value">${CurrencyFmt.format(inv.paid_total, cur)}</div>
          <div class="stat-label">CA encaissé</div>
        </div>
      </div>
      <div class="stat-card overdue">
        <div class="stat-icon">⏰</div>
        <div class="stat-body">
          <div class="stat-value">${CurrencyFmt.format(inv.due_total, cur)}</div>
          <div class="stat-label">À encaisser</div>
        </div>
      </div>
      <div class="stat-card paid">
        <div class="stat-icon">✓</div>
        <div class="stat-body">
          <div class="stat-value">${inv.paid}</div>
          <div class="stat-label">Payées</div>
        </div>
      </div>
      <div class="stat-card overdue">
        <div class="stat-icon">⚠</div>
        <div class="stat-body">
          <div class="stat-value">${inv.overdue}</div>
          <div class="stat-label">En retard</div>
        </div>
      </div>
      <div class="stat-card pending">
        <div class="stat-icon">📤</div>
        <div class="stat-body">
          <div class="stat-value">${inv.sent}</div>
          <div class="stat-label">Envoyées</div>
        </div>
      </div>
    `;
  }

  async function confirmDelete(id) {
    if (!confirm('Confirmer la suppression de cette facture ?')) return;
    const csrf = document.querySelector('meta[name=csrf-token]')?.content;
    try {
      const res = await fetch(`/invoices/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
      });
      const json = await res.json();
      if (json.success) { Toast.success('Supprimée', json.message); load(); }
      else Toast.error('Erreur', json.message);
    } catch(e) { Toast.error('Erreur', e.message); }
  }

  function updateBulkBar() {
    const bar = document.getElementById('inv-bulk-bar');
    if (!bar) return;
    bar.style.display = state.selected.size > 0 ? 'flex' : 'none';
    const cnt = bar.querySelector('.bulk-count');
    if (cnt) cnt.textContent = `${state.selected.size} sélectionné(s)`;
  }

  function setLoading(on) {
    const loader = document.getElementById('inv-table-loader');
    if (loader) loader.style.display = on ? 'flex' : 'none';
    if (tbody) tbody.style.opacity = on ? '.4' : '1';
  }

  return { init, load, goPage: (p) => { state.page = p; load(); }, confirmDelete, applyFilters };
})();

window.InvoiceTable = InvoiceTable;

/* ============================================================
   LINE ITEMS BUILDER (create / edit)
   ============================================================ */
const LineItems = (() => {
  let items = [];
  let currency = 'EUR';
  let globalDiscountType = 'none';
  let globalDiscountValue = 0;
  let taxRate = 0;
  let withholdingRate = 0;
  let container;
  let counter = 0;

  function init(opts = {}) {
    currency            = opts.currency         || 'EUR';
    taxRate             = parseFloat(opts.taxRate           || 0);
    withholdingRate     = parseFloat(opts.withholdingRate   || 0);
    globalDiscountType  = opts.discountType      || 'none';
    globalDiscountValue = parseFloat(opts.discountValue || 0);
    container           = document.getElementById('line-items-body');

    if (!container) return;

    // Restore items from existing data (edit mode)
    if (opts.items && Array.isArray(opts.items)) {
      opts.items.forEach(addItemFromData);
    } else {
      addItem(); // au moins une ligne vide
    }

    // Currency change
    const currencySelect = document.getElementById('currency');
    if (currencySelect) {
      currencySelect.addEventListener('change', () => {
        currency = currencySelect.value;
        recalcAll();
        fetchExchangeRate(currency);
      });
    }

    // Global discount
    document.getElementById('discount_type')?.addEventListener('change', e => {
      globalDiscountType = e.target.value;
      recalcAll();
    });
    document.getElementById('discount_value')?.addEventListener('input', e => {
      globalDiscountValue = parseFloat(e.target.value) || 0;
      recalcAll();
    });

    // Tax rate
    document.getElementById('tax_rate')?.addEventListener('input', e => {
      taxRate = parseFloat(e.target.value) || 0;
      recalcAll();
    });

    // Withholding
    document.getElementById('withholding_tax_rate')?.addEventListener('input', e => {
      withholdingRate = parseFloat(e.target.value) || 0;
      recalcAll();
    });

    // Sortable (drag-and-drop basic)
    enableSortable();
  }

  function addItemFromData(data) {
    const id = ++counter;
    items.push({ id, ...data });
    renderItem(id, data);
  }

  function addItem() {
    const id = ++counter;
    const data = { description: '', quantity: 1, unit: '', unit_price: 0, discount_type: 'none', discount_value: 0, tax_rate: taxRate };
    items.push({ id, ...data });
    renderItem(id, data);
  }

  function renderItem(id, data = {}) {
    const tr = document.createElement('tr');
    tr.id = `item-row-${id}`;
    tr.dataset.itemId = id;
    tr.innerHTML = `
      <td><span class="drag-handle" title="Réordonner">⠿</span></td>
      <td class="item-desc">
        <input type="text" name="items[${id}][description]" value="${esc(data.description||'')}"
          class="form-control" placeholder="Description du produit ou service…" required>
        <input type="text" name="items[${id}][reference]" value="${esc(data.reference||'')}"
          class="form-control" style="margin-top:4px;font-size:12px" placeholder="Réf. (optionnel)">
        <input type="hidden" name="items[${id}][unit]" value="${esc(data.unit||'')}">
      </td>
      <td class="item-qty">
        <input type="number" name="items[${id}][quantity]" value="${data.quantity||1}"
          class="form-control item-qty-input" min="0.0001" step="any" required>
        <input type="text" placeholder="unité" class="form-control" style="margin-top:4px;font-size:12px"
          value="${esc(data.unit||'')}" oninput="document.querySelector('[name=\\'items[${id}][unit]\\']').value=this.value">
      </td>
      <td class="item-price">
        <input type="number" name="items[${id}][unit_price]" value="${data.unit_price||0}"
          class="form-control item-price-input" min="0" step="any" required>
      </td>
      <td class="item-disc">
        <select name="items[${id}][discount_type]" class="form-select item-disc-type">
          <option value="none"    ${(data.discount_type||'none')==='none'   ?'selected':''}>Aucune</option>
          <option value="percent" ${data.discount_type==='percent'?'selected':''}>%</option>
          <option value="fixed"   ${data.discount_type==='fixed'  ?'selected':''}>Fixe</option>
        </select>
        <input type="number" name="items[${id}][discount_value]" value="${data.discount_value||0}"
          class="form-control item-disc-val" style="margin-top:4px" min="0" step="any">
      </td>
      <td class="item-tax">
        <input type="number" name="items[${id}][tax_rate]" value="${data.tax_rate??taxRate}"
          class="form-control item-tax-input" min="0" max="100" step="any">
        <span style="font-size:11px;color:var(--c-ink-40);margin-top:2px;display:block">%</span>
      </td>
      <td class="item-total">
        <span class="item-total-val" id="item-total-${id}">—</span>
      </td>
      <td class="item-actions">
        <button type="button" class="btn btn-outline btn-icon btn-sm" style="color:var(--c-danger)"
          onclick="LineItems.removeItem(${id})" title="Supprimer cette ligne">✕</button>
      </td>
    `;
    container.appendChild(tr);

    // Bind recalc on change
    ['item-qty-input','item-price-input','item-disc-type','item-disc-val','item-tax-input'].forEach(cls => {
      tr.querySelector(`.${cls}`)?.addEventListener('input', () => recalcItem(id));
      tr.querySelector(`.${cls}`)?.addEventListener('change', () => recalcItem(id));
    });

    recalcItem(id);
  }

  function removeItem(id) {
    if (items.length <= 1) {
      Toast.warning('Attention', 'Au moins une ligne est obligatoire.');
      return;
    }
    items = items.filter(i => i.id !== id);
    document.getElementById(`item-row-${id}`)?.remove();
    recalcAll();
  }

  function recalcItem(id) {
    const tr  = document.getElementById(`item-row-${id}`);
    if (!tr) return;

    const qty       = parseFloat(tr.querySelector('.item-qty-input')?.value   || 0);
    const price     = parseFloat(tr.querySelector('.item-price-input')?.value  || 0);
    const discType  = tr.querySelector('.item-disc-type')?.value || 'none';
    const discVal   = parseFloat(tr.querySelector('.item-disc-val')?.value     || 0);
    const taxR      = parseFloat(tr.querySelector('.item-tax-input')?.value    || 0);

    const lineTotal = qty * price;
    const discAmt   = discType === 'percent' ? lineTotal * (discVal/100)
                    : discType === 'fixed'   ? discVal : 0;
    const afterDisc = lineTotal - discAmt;
    const taxAmt    = afterDisc * (taxR / 100);
    const total     = afterDisc + taxAmt;

    const el = document.getElementById(`item-total-${id}`);
    if (el) el.textContent = CurrencyFmt.format(total, currency);

    recalcTotals();
  }

  function recalcAll() {
    items.forEach(i => recalcItem(i.id));
  }

  function recalcTotals() {
    // Somme de toutes les lignes (HT après remise ligne)
    let subtotal = 0;
    items.forEach(i => {
      const tr  = document.getElementById(`item-row-${i.id}`);
      if (!tr) return;
      const qty      = parseFloat(tr.querySelector('.item-qty-input')?.value   || 0);
      const price    = parseFloat(tr.querySelector('.item-price-input')?.value  || 0);
      const discType = tr.querySelector('.item-disc-type')?.value || 'none';
      const discVal  = parseFloat(tr.querySelector('.item-disc-val')?.value     || 0);
      const taxR     = parseFloat(tr.querySelector('.item-tax-input')?.value    || 0);

      const lineTotal = qty * price;
      const discAmt   = discType === 'percent' ? lineTotal*(discVal/100) : discType==='fixed' ? discVal : 0;
      subtotal += lineTotal - discAmt;
    });

    // Remise globale
    const globalDisc = globalDiscountType === 'percent' ? subtotal*(globalDiscountValue/100)
                     : globalDiscountType === 'fixed'   ? globalDiscountValue : 0;
    const afterGlobalDisc = subtotal - globalDisc;

    // TVA
    const taxAmt = afterGlobalDisc * (taxRate / 100);
    // Retenue à la source
    const withAmt = afterGlobalDisc * (withholdingRate / 100);
    // Total TTC
    const total   = afterGlobalDisc + taxAmt;

    // Update DOM
    setText('total-subtotal',  CurrencyFmt.format(subtotal,        currency));
    setText('total-discount',  CurrencyFmt.format(globalDisc,      currency));
    setText('total-tax',       CurrencyFmt.format(taxAmt,          currency));
    setText('total-withholding', CurrencyFmt.format(withAmt,       currency));
    setText('total-grand',     CurrencyFmt.format(total,           currency));

    // Withholding info visibility
    const wInfo = document.getElementById('withholding-info');
    if (wInfo) wInfo.style.display = withholdingRate > 0 ? 'flex' : 'none';

    // Net à payer (total - retenue)
    const netAPayer = total - withAmt;
    setText('total-net', CurrencyFmt.format(netAPayer, currency));
  }

  async function fetchExchangeRate(to) {
    const from = window.BASE_CURRENCY || 'EUR';
    if (from === to) { setText('exchange-rate-display', ''); return; }
    try {
      const res  = await fetch(`/invoices/currencies/rate?from=${from}&to=${to}`);
      const json = await res.json();
      if (json.success) {
        const rateEl = document.getElementById('exchange-rate-display');
        if (rateEl) rateEl.textContent = `1 ${from} = ${json.data.rate} ${to}`;
        const rateInput = document.getElementById('exchange_rate');
        if (rateInput) rateInput.value = json.data.rate;
      }
    } catch(e) {}
  }

  function enableSortable() {
    // Simple drag-and-drop via HTML5 API
    if (!container) return;
    let dragging = null;

    container.addEventListener('dragstart', e => {
      dragging = e.target.closest('tr');
      if (dragging) { dragging.style.opacity = '.4'; e.dataTransfer.effectAllowed = 'move'; }
    });
    container.addEventListener('dragend', () => {
      if (dragging) { dragging.style.opacity = ''; dragging = null; }
    });
    container.addEventListener('dragover', e => {
      e.preventDefault();
      const tr = e.target.closest('tr');
      if (tr && tr !== dragging) {
        const rect = tr.getBoundingClientRect();
        const next = (e.clientY - rect.top) > rect.height / 2;
        container.insertBefore(dragging, next ? tr.nextSibling : tr);
      }
    });

    // Make rows draggable
    new MutationObserver(() => {
      container.querySelectorAll('tr').forEach(tr => {
        tr.setAttribute('draggable', 'true');
      });
    }).observe(container, { childList: true });
    container.querySelectorAll('tr').forEach(tr => tr.setAttribute('draggable','true'));
  }

  function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  }
  function esc(s) { return String(s).replace(/"/g,'&quot;').replace(/</g,'&lt;'); }

  return { init, addItem, removeItem, recalcAll };
})();

window.LineItems = LineItems;

/* ============================================================
   CLIENT AUTOCOMPLETE
   ============================================================ */
const ClientSearch = (() => {
  function init(inputId, hiddenId, opts = {}) {
    const input  = document.getElementById(inputId);
    const hidden = document.getElementById(hiddenId);
    if (!input) return;

    let suggestions;

    input.addEventListener('input', debounce(async () => {
      const q = input.value.trim();
      if (q.length < 2) { clearSuggestions(); return; }
      try {
        const res  = await fetch(`/clients/data/search?q=${encodeURIComponent(q)}`);
        const json = await res.json();
        renderSuggestions(json.data || []);
      } catch(e) {}
    }, 300));

    document.addEventListener('click', e => {
      if (!input.contains(e.target)) clearSuggestions();
    });

    function renderSuggestions(clients) {
      clearSuggestions();
      if (!clients.length) return;

      suggestions = document.createElement('div');
      suggestions.className = 'client-suggestions';
      clients.forEach(c => {
        const item = document.createElement('div');
        item.className = 'client-suggestion-item';
        item.innerHTML = `
          <div class="client-avatar-sm">${(c.company_name||'?').slice(0,2).toUpperCase()}</div>
          <div>
            <div class="client-suggestion-name">${c.company_name}</div>
            <div class="client-suggestion-email">${c.email||''}</div>
          </div>`;
        item.addEventListener('click', () => {
          input.value  = c.company_name;
          if (hidden) hidden.value = c.id;
          if (opts.onSelect) opts.onSelect(c);
          clearSuggestions();
        });
        suggestions.appendChild(item);
      });

      const wrap = input.closest('.client-select-wrap') || input.parentElement;
      wrap.appendChild(suggestions);
    }

    function clearSuggestions() {
      suggestions?.remove();
      suggestions = null;
    }
  }

  return { init };
})();

window.ClientSearch = ClientSearch;

/* ============================================================
   PAYMENT MODAL
   ============================================================ */
const PaymentModal = (() => {
  let overlay, form, invoiceId;

  function init(opts = {}) {
    overlay   = document.getElementById('payment-modal');
    form      = document.getElementById('payment-form');
    invoiceId = opts.invoiceId;

    if (!overlay || !form) return;

    document.querySelectorAll('[data-open-payment]').forEach(btn => {
      btn.addEventListener('click', () => open());
    });

    overlay.querySelector('.modal-close')?.addEventListener('click', close);
    overlay.addEventListener('click', e => { if (e.target === overlay) close(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });

    form.addEventListener('submit', async e => {
      e.preventDefault();
      await submit();
    });
  }

  function open() {
    if (!overlay) return;
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
    // Set today's date
    const dateInput = form.querySelector('[name=payment_date]');
    if (dateInput && !dateInput.value) dateInput.value = new Date().toISOString().split('T')[0];
  }

  function close() {
    overlay?.classList.remove('open');
    document.body.style.overflow = '';
  }

  async function submit() {
    const csrf = document.querySelector('meta[name=csrf-token]')?.content;
    const data = new FormData(form);
    const btn  = form.querySelector('[type=submit]');

    setLoading(btn, true);
    clearErrors();

    try {
      const res  = await fetch(`/invoices/${invoiceId}/payments`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
        body: data,
      });
      const json = await res.json();

      if (json.success) {
        Toast.success('Paiement enregistré', json.message);
        close();
        form.reset();
        setTimeout(() => window.location.reload(), 1200);
      } else {
        if (json.errors) showErrors(json.errors);
        else Toast.error('Erreur', json.message);
      }
    } catch(e) {
      Toast.error('Erreur', e.message);
    } finally {
      setLoading(btn, false);
    }
  }

  function showErrors(errors) {
    Object.entries(errors).forEach(([field, messages]) => {
      const input = form.querySelector(`[name=${field}]`);
      if (input) {
        input.classList.add('is-invalid');
        const err = document.createElement('div');
        err.className = 'field-error'; err.textContent = messages[0];
        input.parentElement.appendChild(err);
      }
    });
  }

  function clearErrors() {
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.field-error').forEach(el => el.remove());
  }

  function setLoading(btn, on) {
    if (!btn) return;
    btn.disabled = on;
    btn.innerHTML = on
      ? '<span class="spinner"></span> Enregistrement…'
      : '💳 Enregistrer le paiement';
  }

  return { init, open, close };
})();

window.PaymentModal = PaymentModal;

/* ============================================================
   INVOICE FORM (create / edit)
   ============================================================ */
const InvoiceForm = (() => {
  function init(opts = {}) {
    const form = document.getElementById('invoice-form') || document.getElementById('quote-form');
    if (!form) return;

    form.addEventListener('submit', async e => {
      e.preventDefault();
      await submit(form, opts.redirectOnSuccess);
    });

    // Client autocomplete
    ClientSearch.init('client-search-input', 'client_id', {
      onSelect: c => {
        // Pre-fill withholding if country matches
        const wCountries = window.WITHHOLDING_COUNTRIES || [];
        if (c.country && wCountries.includes(c.country.toUpperCase())) {
          const wInput = document.getElementById('withholding_tax_rate');
          if (wInput && !wInput.value) wInput.value = window.DEFAULT_WITHHOLDING_RATE || 0;
          LineItems.recalcAll();
        }
      }
    });

    // Date due auto-calc
    const issueDate    = document.getElementById('issue_date');
    const paymentTerms = document.getElementById('payment_terms');
    const dueDate      = document.getElementById('due_date');

    function updateDueDate() {
      if (!issueDate?.value || !paymentTerms?.value || !dueDate) return;
      const d = new Date(issueDate.value);
      d.setDate(d.getDate() + parseInt(paymentTerms.value));
      dueDate.value = d.toISOString().split('T')[0];
    }

    issueDate?.addEventListener('change',    updateDueDate);
    paymentTerms?.addEventListener('change', updateDueDate);

    // Line items
    LineItems.init({
      currency:        opts.currency        || 'EUR',
      taxRate:         opts.taxRate         || 0,
      withholdingRate: opts.withholdingRate || 0,
      discountType:    opts.discountType    || 'none',
      discountValue:   opts.discountValue   || 0,
      items:           opts.items           || [],
    });

    document.getElementById('add-line-btn')?.addEventListener('click', () => LineItems.addItem());
  }

  async function submit(form, redirectUrl) {
    const csrf   = document.querySelector('meta[name=csrf-token]')?.content;
    const method = form.querySelector('[name=_method]')?.value || 'POST';
    const btn    = form.querySelector('[type=submit]');

    clearErrors(form);
    setLoading(btn, true);

    try {
      const res  = await fetch(form.action, {
        method: method === 'POST' ? 'POST' : 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form),
      });
      const json = await res.json();

      if (json.success) {
        Toast.success('Succès', json.message);
        setTimeout(() => { window.location.href = json.redirect || redirectUrl || '/invoices'; }, 900);
      } else {
        if (json.errors) {
          showErrors(form, json.errors);
          Toast.error('Erreur de validation', 'Veuillez corriger les champs en rouge.');
        } else {
          Toast.error('Erreur', json.message);
        }
      }
    } catch(e) {
      Toast.error('Erreur', e.message);
    } finally {
      setLoading(btn, false);
    }
  }

  function showErrors(form, errors) {
    Object.entries(errors).forEach(([field, messages]) => {
      const name  = field.replace(/\./g,'[').replace(/(\w)(\[)/g,'$1]$2') + (field.includes('.')? ']' : '');
      const input = form.querySelector(`[name="${field}"]`) || form.querySelector(`[name="${name}"]`);
      if (input) {
        input.classList.add('is-invalid');
        const err = document.createElement('div');
        err.className = 'field-error';
        err.textContent = Array.isArray(messages) ? messages[0] : messages;
        input.parentElement.appendChild(err);
        input.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  }

  function clearErrors(form) {
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.field-error').forEach(el => el.remove());
  }

  function setLoading(btn, on) {
    if (!btn) return;
    btn.disabled = on;
    if (on) btn.dataset.origText = btn.innerHTML;
    btn.innerHTML = on ? '<span class="spinner"></span> Enregistrement…' : (btn.dataset.origText || 'Enregistrer');
  }

  return { init };
})();

window.InvoiceForm = InvoiceForm;

/* ============================================================
   QUOTE CONVERT
   ============================================================ */
async function convertQuoteToInvoice(quoteId) {
  if (!confirm('Convertir ce devis en facture ?')) return;
  const csrf = document.querySelector('meta[name=csrf-token]')?.content;
  try {
    const res  = await fetch(`/invoices/quotes/${quoteId}/convert`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
    });
    const json = await res.json();
    if (json.success) { Toast.success('Converti !', json.message); setTimeout(() => window.location.href = json.redirect, 1200); }
    else Toast.error('Erreur', json.message);
  } catch(e) { Toast.error('Erreur', e.message); }
}

/* ============================================================
   HELPERS
   ============================================================ */
function debounce(fn, ms) {
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

function formatDate(str) {
  if (!str) return '—';
  const d = new Date(str);
  return d.toLocaleDateString('fr-FR', { day:'2-digit', month:'2-digit', year:'numeric' });
}

/* ============================================================
   DROPDOWN BUTTONS
   ============================================================ */
document.addEventListener('click', e => {
  const toggle = e.target.closest('[data-dropdown-toggle]');
  if (toggle) {
    const targetId = toggle.dataset.dropdownToggle;
    const menu     = document.getElementById(targetId);
    if (menu) {
      menu.classList.toggle('open');
      e.stopPropagation();
    }
    return;
  }
  // Close all dropdowns
  document.querySelectorAll('.btn-dropdown.open').forEach(m => m.classList.remove('open'));
});
