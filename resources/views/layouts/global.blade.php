<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'CRM') - {{ config('app.name') }}</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('vendor/client/css/crm.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/invoice/css/invoice.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/stock/css/stock.css') }}">
  <style>
    .global-search-wrap{position:relative;min-width:320px;max-width:520px;width:42vw}
    .global-search-wrap input{padding-left:36px}
    .global-search-wrap .fa-search{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--c-ink-30);font-size:13px}
    .global-search-suggest{position:absolute;top:calc(100% + 8px);left:0;right:0;background:#fff;border:1px solid var(--c-ink-05);border-radius:12px;box-shadow:0 16px 40px rgba(15,23,42,.12);padding:8px;display:none;z-index:90;max-height:380px;overflow:auto}
    .global-search-group{padding:6px 8px 4px;font-size:11px;color:var(--c-ink-40);text-transform:uppercase;letter-spacing:.04em;font-weight:700}
    .global-search-item{display:flex;align-items:center;gap:10px;padding:10px 10px;border-radius:8px;text-decoration:none;color:var(--c-ink)}
    .global-search-item:hover{background:var(--c-accent-xl)}
    .global-search-item small{display:block;color:var(--c-ink-40);font-size:12px}
  </style>
  @stack('styles')
</head>
<body>
<div class="crm-layout">
  <aside class="crm-sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="sidebar-brand-icon"><i class="fas fa-chart-network"></i></div>
      <div>
        <div class="sidebar-brand-name">CRM Pro</div>
        <div class="sidebar-brand-tag">SaaS Platform</div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="sidebar-nav-section">Principal</div>
      <a href="{{ url('/dashboard') }}" class="{{ request()->is('dashboard') ? 'active' : '' }}"><i class="fas fa-grid-2"></i> Dashboard</a>

      <div class="sidebar-nav-section">CRM</div>
      <a href="{{ route('clients.index') }}" class="{{ request()->routeIs('clients.*') ? 'active' : '' }}"><i class="fas fa-users"></i> Clients</a>

      <div class="sidebar-nav-section">Stock</div>
      <a href="{{ route('stock.articles.index') }}" class="{{ request()->routeIs('stock.articles.*') ? 'active' : '' }}"><i class="fas fa-boxes-stacked"></i> Articles</a>
      <a href="{{ route('stock.suppliers.index') }}" class="{{ request()->routeIs('stock.suppliers.*') ? 'active' : '' }}"><i class="fas fa-truck-field"></i> Fournisseurs</a>
      <a href="{{ route('stock.orders.index') }}" class="{{ request()->routeIs('stock.orders.*') ? 'active' : '' }}"><i class="fas fa-clipboard-list"></i> Commandes</a>

      <div class="sidebar-nav-section">Facturation</div>
      <a href="{{ route('invoices.index') }}" class="{{ request()->routeIs('invoices.index') || (request()->routeIs('invoices.*') && !request()->routeIs('invoices.quotes.*') && !request()->routeIs('invoices.reports.*') && !request()->routeIs('invoices.settings.*') && !request()->routeIs('invoices.payments.*')) ? 'active' : '' }}"><i class="fas fa-file-invoice"></i> Factures</a>
      <a href="{{ route('invoices.quotes.index') }}" class="{{ request()->routeIs('invoices.quotes.*') ? 'active' : '' }}"><i class="fas fa-file-signature"></i> Devis</a>
      <a href="{{ route('invoices.payments.index') }}" class="{{ request()->routeIs('invoices.payments.*') ? 'active' : '' }}"><i class="fas fa-credit-card"></i> Paiements</a>
      <a href="{{ route('invoices.reports.index') }}" class="{{ request()->routeIs('invoices.reports.*') ? 'active' : '' }}"><i class="fas fa-chart-line"></i> Rapports</a>

      <div class="sidebar-nav-section">Extensions</div>
        <a href="{{ route('marketplace.index') }}" class="{{ request()->routeIs('marketplace.index.*') ? 'active' : '' }}"><i class="fa fa-cubes"></i> Applications</a>
        <a href="{{ route('google-drive.index') }}" class="{{ request()->routeIs('google-drive.*') ? 'active' : '' }}"><i class="fa fa-google-drive"></i> Google Drive</a>
        <a href="{{ route('google-calendar.index') }}" class="{{ request()->routeIs('google-calendar.*') ? 'active' : '' }}"><i class="fa fa-calendar-days"></i> Google Calendar</a>

         <div class="sidebar-nav-section">Utilisateurs</div>
        <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.index.*') ? 'active' : '' }}"><i class="fa fa-user-cog"></i> Utilisateurs</a>


      <div class="sidebar-nav-section">Configuration</div>
      <a href="{{ route('invoices.settings.index') }}" class="{{ request()->routeIs('invoices.settings.*') ? 'active' : '' }}"><i class="fas fa-sliders"></i> Parametres facturation</a>
    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-user" onclick="document.getElementById('userDropdown').classList.toggle('open')">
        <div class="sidebar-user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}</div>
        <div style="flex:1;min-width:0;">
          <div class="sidebar-user-name">{{ auth()->user()->name ?? 'Utilisateur' }}</div>
          <div class="sidebar-user-role">{{ auth()->user()->role_in_tenant ?? 'Membre' }}</div>
        </div>
        <i class="fas fa-chevron-right" style="color:rgba(255,255,255,.3);font-size:11px;"></i>
      </div>
      <div class="dropdown" id="userDropdown" style="margin-top:4px;">
        <div class="dropdown-menu" style="bottom:calc(100% + 6px);top:auto;">
          <a href="#" class="dropdown-item"><i class="fas fa-user"></i> Mon profil</a>
          <a href="#" class="dropdown-item"><i class="fas fa-gear"></i> Parametres</a>
          <div class="dropdown-divider"></div>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="dropdown-item danger" style="width:100%;border:none;background:none;cursor:pointer;text-align:left;">
              <i class="fas fa-right-from-bracket"></i> Deconnexion
            </button>
          </form>
        </div>
      </div>
    </div>
  </aside>

  <div class="crm-main">
    <header class="crm-header">
      <button id="sidebarToggle" class="btn-icon" style="display:none"><i class="fas fa-bars"></i></button>
      <div class="crm-header-breadcrumb">@yield('breadcrumb')</div>
      <div class="crm-header-spacer"></div>

      <div class="global-search-wrap">
        <i class="fas fa-search"></i>
        <input id="globalSearchInput" class="form-control" type="text" placeholder="Recherche globale: clients, factures, articles, commandes..." autocomplete="off">
        <div id="globalSearchSuggestions" class="global-search-suggest"></div>
      </div>

      <div class="crm-header-actions">
        <button class="btn-icon" title="Notifications"><i class="fas fa-bell"></i></button>
      </div>
    </header>

    <main class="crm-content">@yield('content')</main>
  </div>
</div>

<div class="modal-overlay" id="confirmModal">
  <div class="modal modal-sm">
    <div class="modal-body" style="text-align:center;padding:36px 28px;">
      <div class="modal-confirm-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
      <h3 class="modal-confirm-title" data-confirm-title>Confirmer l'action</h3>
      <p class="modal-confirm-text" data-confirm-text style="margin-bottom:24px;"></p>
      <div style="display:flex;gap:10px;justify-content:center;">
        <button class="btn btn-secondary" data-modal-close>Annuler</button>
        <button class="btn btn-danger" data-confirm-ok>Confirmer</button>
      </div>
    </div>
  </div>
</div>

<script src="{{ asset('vendor/client/js/crm.js') }}"></script>
<script src="{{ asset('vendor/invoice/js/invoice.js') }}"></script>
<script src="{{ asset('vendor/stock/js/stock.js') }}"></script>
<script>
(function () {
  function decodeMojibake(value) {
    if (typeof value !== 'string') return value;
    if (!/[Ãâ€]/.test(value)) return value;
    try {
      return decodeURIComponent(escape(value));
    } catch (e) {
      return value
        .replaceAll('Ã©', 'e').replaceAll('Ã¨', 'e').replaceAll('Ãª', 'e').replaceAll('Ã«', 'e')
        .replaceAll('Ã ', 'a').replaceAll('Ã¢', 'a').replaceAll('Ã§', 'c')
        .replaceAll('Ã´', 'o').replaceAll('Ã¹', 'u').replaceAll('Ã»', 'u').replaceAll('Ã®', 'i')
        .replaceAll('â€”', '-').replaceAll('â€“', '-').replaceAll('â€™', "'");
    }
  }

  function normalizeNodeText(root) {
    const walker = document.createTreeWalker(root || document.body, NodeFilter.SHOW_TEXT, {
      acceptNode(node) {
        const p = node.parentElement;
        if (!p) return NodeFilter.FILTER_REJECT;
        if (['SCRIPT', 'STYLE', 'NOSCRIPT', 'TEXTAREA'].includes(p.tagName)) return NodeFilter.FILTER_REJECT;
        return NodeFilter.FILTER_ACCEPT;
      }
    });

    const toPatch = [];
    while (walker.nextNode()) {
      const n = walker.currentNode;
      if (/[Ãâ€]/.test(n.nodeValue || '')) toPatch.push(n);
    }
    toPatch.forEach((n) => {
      const fixed = decodeMojibake(n.nodeValue || '');
      if (fixed !== n.nodeValue) n.nodeValue = fixed;
    });
  }

  function initGlobalSearch() {
    const input = document.getElementById('globalSearchInput');
    const box = document.getElementById('globalSearchSuggestions');
    if (!input || !box || typeof Http === 'undefined') return;

    let timer = null;
    const close = () => { box.style.display = 'none'; box.innerHTML = ''; };

    const esc = (v) => {
      const d = document.createElement('div');
      d.textContent = v || '';
      return d.innerHTML;
    };

    const renderGroup = (title, rows) => {
      if (!rows.length) return '';
      const links = rows.map((r) => `
        <a class="global-search-item" href="${r.url}">
          <i class="fas ${r.icon || 'fa-link'}" style="color:var(--c-accent);width:16px;"></i>
          <div><div>${esc(r.label)}</div><small>${esc(r.sub || '')}</small></div>
        </a>
      `).join('');
      return `<div class="global-search-group">${esc(title)}</div>${links}`;
    };

    input.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(async () => {
        const q = input.value.trim();
        if (q.length < 2) return close();

        const [clients, articles, orders, invoices] = await Promise.all([
          Http.get('/clients/data/search', { q }),
          Http.get('/stock/articles/data/search', { q }),
          Http.get('/stock/orders/data/search', { q }),
          Http.get('/invoices/data/table', { search: q, per_page: 5 })
        ]);

        const clientRows = (clients.ok ? (clients.data.data || []) : []).slice(0, 5).map((c) => ({
          label: c.company_name,
          sub: c.email || c.phone || '',
          url: `/clients/${c.id}`,
          icon: 'fa-users'
        }));

        const articleRows = (articles.ok ? (articles.data.data || []) : []).slice(0, 5).map((a) => ({
          label: a.name,
          sub: `SKU: ${a.sku || '-'} | Stock: ${a.stock_quantity ?? 0}`,
          url: `/stock/articles/${a.id}`,
          icon: 'fa-box'
        }));

        const orderRows = (orders.ok ? (orders.data.data || []) : []).slice(0, 5).map((o) => ({
          label: o.number,
          sub: `Statut: ${o.status || '-'}`,
          url: `/stock/orders/${o.id}`,
          icon: 'fa-clipboard-list'
        }));

        const invoiceRows = (invoices.ok ? (invoices.data.data || []) : []).slice(0, 5).map((i) => ({
          label: i.number,
          sub: i.client?.company_name || i.reference || '',
          url: `/invoices/${i.id}`,
          icon: 'fa-file-invoice'
        }));

        box.innerHTML =
          renderGroup('Clients', clientRows) +
          renderGroup('Articles', articleRows) +
          renderGroup('Commandes', orderRows) +
          renderGroup('Factures', invoiceRows);

        if (!box.innerHTML.trim()) box.innerHTML = '<div class="global-search-group">Aucun resultat</div>';
        box.style.display = 'block';
        normalizeNodeText(box);
      }, 260);
    });

    document.addEventListener('click', (e) => {
      if (!e.target.closest('.global-search-wrap')) close();
    });
  }

  function initInvoiceStockBridge() {
    const form = document.getElementById('invoiceForm') || document.getElementById('quoteForm');
    const tbody = document.getElementById('lineItemsBody');
    if (!form || !tbody || typeof Http === 'undefined') return;

    if (!form.querySelector('input[name="stock_order_id"]')) {
      const hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = 'stock_order_id';
      hidden.id = 'stock_order_id';
      form.appendChild(hidden);
    }

    if (document.getElementById('stockSourceType')) return;

    const block = document.createElement('div');
    block.className = 'form-section';
    block.innerHTML = `
      <h3 class="form-section-title"><i class="fas fa-warehouse"></i> Source stock (optionnel)</h3>
      <div class="row">
        <div class="col-4"><div class="form-group"><label class="form-label">Type</label><select id="stockSourceType" class="form-control"><option value="">Aucune</option><option value="article">Article</option><option value="order">Commande fournisseur</option></select></div></div>
        <div class="col-8"><div class="form-group"><label class="form-label">Recherche</label><input type="text" id="stockSourceSearch" class="form-control" placeholder="Tapez pour rechercher..."><div id="stockSourceSuggestions" class="client-suggestions" style="display:none;"></div></div></div>
      </div>`;

    const target = form.querySelector('.form-section');
    if (target && target.parentNode) target.parentNode.insertBefore(block, target.nextSibling);

    const searchInput = document.getElementById('stockSourceSearch');
    const typeInput = document.getElementById('stockSourceType');
    const suggestions = document.getElementById('stockSourceSuggestions');
    let timer = null;

    const esc = (v) => { const d = document.createElement('div'); d.textContent = v || ''; return d.innerHTML; };

    const appendLine = (line) => {
      if (window.InvLineItems?.addLine) window.InvLineItems.addLine();
      const last = tbody.querySelector('tr:last-child');
      if (!last) return;
      const descInput = last.querySelector('[name*="[description]"]');
      const refInput = last.querySelector('[name*="[reference]"]');
      const qtyInput = last.querySelector('[name*="[quantity]"]');
      const unitInput = last.querySelector('[name*="[unit]"]');
      const priceInput = last.querySelector('[name*="[unit_price]"]');
      const hiddenArticle = document.createElement('input');
      hiddenArticle.type = 'hidden';
      hiddenArticle.name = (descInput?.name || '').replace('[description]', '[article_id]');
      hiddenArticle.value = line.article_id || '';

      if (descInput) descInput.value = line.description || '';
      if (refInput) refInput.value = line.reference || '';
      if (qtyInput) qtyInput.value = line.quantity || 1;
      if (unitInput) unitInput.value = line.unit || '';
      if (priceInput) priceInput.value = line.unit_price || 0;
      if (descInput && hiddenArticle.name) descInput.closest('td')?.appendChild(hiddenArticle);
      if (window.InvLineItems?.recalc) window.InvLineItems.recalc();
    };

    const renderSuggestions = (items, onPick) => {
      if (!items.length) { suggestions.style.display = 'none'; return; }
      suggestions.innerHTML = items.map((item) => `<div class="client-suggestion-item" data-id="${item.id}"><div style="font-weight:600;font-size:13px;">${esc(item.label)}</div><div style="font-size:12px;color:var(--c-ink-40);">${esc(item.sub || '')}</div></div>`).join('');
      suggestions.style.display = 'block';
      suggestions.querySelectorAll('.client-suggestion-item').forEach((el, idx) => el.addEventListener('click', () => onPick(items[idx])));
    };

    searchInput?.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(async () => {
        const q = searchInput.value.trim();
        const type = typeInput.value;
        if (q.length < 2 || !type) { suggestions.style.display = 'none'; return; }

        if (type === 'article') {
          const { ok, data } = await Http.get('/stock/articles/data/search', { q });
          if (!ok || !data.data) return;
          const rows = data.data.map((a) => ({ id: a.id, label: `${a.name}${a.sku ? ' (' + a.sku + ')' : ''}`, sub: `Stock: ${a.stock_quantity ?? 0}`, payload: a }));
          renderSuggestions(rows, (choice) => {
            appendLine({ article_id: choice.payload.id, description: choice.payload.name, reference: choice.payload.sku || '', quantity: 1, unit: choice.payload.unit || 'piece', unit_price: choice.payload.sale_price || 0 });
            searchInput.value = choice.label;
            suggestions.style.display = 'none';
          });
        }

        if (type === 'order') {
          const { ok, data } = await Http.get('/stock/orders/data/search', { q });
          if (!ok || !data.data) return;
          const rows = data.data.map((o) => ({ id: o.id, label: o.number, sub: `Statut: ${o.status}` }));
          renderSuggestions(rows, async (choice) => {
            const detail = await Http.get(`/stock/orders/data/${choice.id}`);
            if (!detail.ok || !detail.data?.data) return;
            const order = detail.data.data;
            document.getElementById('stock_order_id').value = order.id;
            if (tbody.children.length === 1 && !tbody.querySelector('[name*="[description]"]')?.value) tbody.innerHTML = '';
            (order.items || []).forEach((it) => appendLine({ article_id: it.article_id || '', description: it.name, reference: it.article?.sku || '', quantity: it.quantity, unit: it.unit, unit_price: it.unit_price }));
            searchInput.value = choice.label;
            suggestions.style.display = 'none';
          });
        }
      }, 250);
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    normalizeNodeText(document.body);
    initGlobalSearch();
    initInvoiceStockBridge();

    const observer = new MutationObserver((mutations) => {
      for (const mutation of mutations) {
        for (const node of mutation.addedNodes) {
          if (node.nodeType === 1) normalizeNodeText(node);
          if (node.nodeType === 3) normalizeNodeText(node.parentElement || document.body);
        }
      }
    });
    observer.observe(document.body, { childList: true, subtree: true });
  });
})();
</script>
@stack('scripts')
</body>
</html>
