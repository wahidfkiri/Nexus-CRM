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
    .global-search-item.is-active{background:var(--c-accent-xl);outline:1px solid var(--c-accent-lt)}
    .global-search-item small{display:block;color:var(--c-ink-40);font-size:12px}
    .global-search-meta{display:flex;align-items:center;justify-content:space-between;gap:10px}
    .global-search-badge{font-size:10px;padding:2px 7px;border-radius:999px;background:var(--c-accent-xl);color:var(--c-accent);font-weight:700;white-space:nowrap}
    .global-search-empty,.global-search-loading{padding:12px 10px;color:var(--c-ink-50);font-size:13px}
    .apps-drawer{height:100vh;max-height:100vh;border-radius:0;max-width:420px;margin-left:auto}
    .apps-drawer-list{display:flex;flex-direction:column;gap:10px}
    .apps-drawer-category{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--c-ink-40);font-weight:700;padding:8px 2px 0}
    .apps-drawer-item{display:flex;align-items:center;gap:10px;padding:11px 12px;border:1px solid var(--c-ink-05);border-radius:10px;text-decoration:none;color:var(--c-ink);transition:all .2s ease}
    .apps-drawer-item:hover{border-color:var(--c-accent);background:var(--c-accent-xl)}
    .apps-drawer-icon{width:34px;height:34px;border-radius:8px;background:var(--surface-1);display:flex;align-items:center;justify-content:center;color:var(--c-accent)}
    .apps-drawer-badge{margin-left:auto;font-size:10px;padding:2px 7px;border-radius:999px;background:var(--c-success-lt);color:var(--c-success);font-weight:700}
    .btn-icon-notif{position:relative}
    .header-notif-badge{
      position:absolute;
      top:-4px;
      right:-4px;
      min-width:18px;
      height:18px;
      padding:0 5px;
      border-radius:999px;
      display:none;
      align-items:center;
      justify-content:center;
      background:#ef4444;
      color:#fff;
      border:2px solid #fff;
      font-size:10px;
      font-weight:700;
      line-height:1;
      box-shadow:0 3px 10px rgba(239,68,68,.45);
    }
    .modal-overlay.modal-overlay-right{justify-content:flex-end}
    .modal-overlay.modal-overlay-right .modal{transform:translateX(22px)}
    .modal-overlay.modal-overlay-right.open .modal{transform:translateX(0)}
    .module-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:14px 20px 0;padding:10px 14px;background:var(--surface-0);border:1px solid var(--c-ink-05);border-radius:12px}
    .module-toolbar-title{font-size:13px;font-weight:700;color:var(--c-ink-60);text-transform:uppercase;letter-spacing:.04em}
    .module-toolbar-links{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
    .module-toolbar-links a{padding:7px 11px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;color:var(--c-ink-60);border:1px solid var(--c-ink-05)}
    .module-toolbar-links a.active,.module-toolbar-links a:hover{color:var(--c-accent);border-color:var(--c-accent-lt);background:var(--c-accent-xl)}
    .sidebar-nav-subsection{
      padding:8px 20px 4px;
      font-size:11px;
      color:rgba(255,255,255,.45);
      font-weight:700;
      letter-spacing:.03em;
      display:flex;
      align-items:center;
      gap:8px;
    }
    .sidebar-nav-subsection::before{
      content:'';
      width:14px;
      height:1px;
      background:rgba(255,255,255,.22);
    }
    .sidebar-app-link{position:relative}
    .sidebar-app-link .app-icon-badge{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      width:22px;
      height:22px;
      border-radius:7px;
      margin-right:1px;
      background:var(--app-bg, #334155);
      color:#fff;
      box-shadow:0 2px 10px rgba(2,6,23,.26);
    }
    .sidebar-app-link .app-icon-badge i{
      width:auto;
      font-size:11px;
    }
    .sidebar-market-link .nav-badge{background:rgba(37,99,235,.32);color:#dbeafe}
    .sidebar-brand-logo{
      width:42px;
      height:42px;
      border-radius:10px;
      object-fit:contain;
      background:#fff;
      padding:4px;
      box-shadow:0 6px 18px rgba(2,6,23,.28);
      flex:0 0 auto;
    }
    .sidebar-brand-fallback{
      width:42px;
      height:42px;
      border-radius:10px;
      display:none;
      align-items:center;
      justify-content:center;
      background:rgba(255,255,255,.1);
      color:#fff;
      flex:0 0 auto;
    }

    .ui-tooltip{
      position:fixed;
      z-index:1000;
      max-width:280px;
      padding:8px 10px;
      border-radius:10px;
      background:linear-gradient(180deg,#111827 0%,#0b1220 100%);
      color:#f8fafc;
      border:1px solid rgba(255,255,255,.1);
      font-size:12px;
      line-height:1.35;
      font-weight:600;
      box-shadow:0 14px 40px rgba(2,6,23,.45);
      pointer-events:none;
      opacity:0;
      transform:translateY(6px) scale(.98);
      transition:opacity .14s ease, transform .14s ease;
    }
    .ui-tooltip.show{
      opacity:1;
      transform:translateY(0) scale(1);
    }
    .ui-tooltip::after{
      content:'';
      position:absolute;
      left:50%;
      transform:translateX(-50%);
      bottom:-6px;
      border-width:6px 6px 0 6px;
      border-style:solid;
      border-color:#0b1220 transparent transparent transparent;
    }
    .crm-sidebar,.sidebar-footer,#userDropdown{overflow:visible}
    #userDropdown{
      position:relative;
      width:100%;
  }
    #userDropdown .sidebar-user{position:relative;padding-right:30px;align-items:flex-start}
    #userDropdown .sidebar-user .user-chevron{
      position:absolute;
      top:20px;
      right:8px;
      color:rgba(255,255,255,.35);
      font-size:11px;
      transition:transform .18s ease,color .18s ease;
    }
    #userDropdown.open .sidebar-user .user-chevron{
      transform:rotate(90deg);
      color:rgba(255,255,255,.75);
    }
    #userDropdown .dropdown-menu{
      left:calc(100% - 20px);
      right:auto;
      top: -115px;
      bottom:auto;
      min-width:220px;
      z-index:120;
      transform:translate(-8px,0);
    }
    #userDropdown.open .dropdown-menu{
      transform:translate(0,0);
    }
    @media (max-width: 992px){
      #userDropdown .dropdown-menu{
        left:auto;
        right:0;
        top:auto;
        bottom:calc(100% + 6px);
        transform:translateY(-6px);
      }
      #userDropdown.open .dropdown-menu{
        transform:translateY(0);
      }
    }
  </style>
  @stack('styles')
</head>
<body>
<div class="crm-layout">
  <aside class="crm-sidebar" id="sidebar">
    <div class="sidebar-brand">
      <img
        src="{{ asset('logo.png') }}"
        alt="{{ config('app.name', 'CRM') }} Logo"
        class="sidebar-brand-logo"
        loading="eager"
        decoding="async"
        onerror="this.style.display='none'; var fb=this.nextElementSibling; if(fb){fb.style.display='inline-flex';}"
      >
      <div class="sidebar-brand-fallback"><i class="fas fa-layer-group"></i></div>
      <div>
        <div class="sidebar-brand-name">Nexiste CRM</div>
        <div class="sidebar-brand-tag">SaaS Platform</div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="sidebar-nav-section">Principal</div>
      <a href="{{ url('/dashboard') }}" class="{{ request()->is('dashboard') ? 'active' : '' }}"><i class="fas fa-home"></i> Tableau de bord</a>

      <div class="sidebar-nav-section">Utilisateurs</div>
      <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') || request()->routeIs('rbac.*') ? 'active' : '' }}"><i class="fa fa-user-cog"></i> Utilisateurs</a>
      @if(Route::has('settings.global'))
        <div class="sidebar-nav-section">Configuration</div>
        <a href="{{ route('settings.global') }}" class="{{ request()->routeIs('settings.global*') ? 'active' : '' }}"><i class="fas fa-sliders"></i> Paramètres globaux</a>
      @endif

      <div class="sidebar-nav-section">Applications</div>
      <a href="{{ route('marketplace.index') }}" class="sidebar-market-link {{ request()->routeIs('marketplace.*') ? 'active' : '' }}" data-tooltip="Marketplace: installer de nouvelles applications">
        <i class="fa fa-store"></i> Marketplace <span class="nav-badge">Store</span>
      </a>
      @php
        $appRoutePatterns = [
          'clients' => 'clients.*',
          'stock' => 'stock.*',
          'invoice' => 'invoices.*',
          'projects' => 'projects.*',
          'notion-workspace' => 'notion-workspace.*',
          'google-drive' => 'google-drive.*',
          'gdrive' => 'google-drive.*',
          'google-calendar' => 'google-calendar.*',
          'google-sheets' => 'google-sheets.*',
          'google-docx' => 'google-docx.*',
          'google-gmail' => 'google-gmail.*',
          'google-meet' => 'google-meet.*',
        ];
      @endphp
      @if(($layoutInstalledAppsByCategory ?? collect())->count())
        @foreach(($layoutInstalledAppsByCategory ?? collect()) as $category)
          <div class="sidebar-nav-subsection">
            <i class="fas {{ $category->icon ?? 'fa-puzzle-piece' }}" style="color:{{ $category->color ?? '#64748b' }}"></i>
            {{ $category->label ?? 'Autre' }}
          </div>
          @foreach(($category->apps ?? collect()) as $installedApp)
            @php
              $pattern = $appRoutePatterns[$installedApp->slug] ?? null;
              $isActive = $pattern ? request()->routeIs($pattern) : false;
            @endphp
            <a href="{{ $installedApp->url }}" class="sidebar-app-link {{ $isActive ? 'active' : '' }}" data-tooltip="{{ $installedApp->name }}: ouvrir l'application">
              <span class="app-icon-badge" style="--app-bg: {{ $installedApp->icon_bg_color ?? '#334155' }};">
                <i class="fa {{ $installedApp->icon }}"></i>
              </span>
              {{ $installedApp->name }}
            </a>
          @endforeach
        @endforeach
      @endif
    </nav>

    <div class="sidebar-footer">
      <div class="dropdown" id="userDropdown" style="margin-top:4px;">
        <div class="sidebar-user" data-dropdown-toggle>
          <div class="sidebar-user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}</div>
          <div style="flex:1;min-width:0;">
            <div class="sidebar-user-name">{{ auth()->user()->name ?? 'Utilisateur' }}</div>
            <div class="sidebar-user-role">{{ auth()->user()->role_in_tenant ?? 'Membre' }}</div>
          </div>
          <i class="fas fa-chevron-right user-chevron"></i>
        </div>
        <div class="dropdown-menu">
          <a href="{{ route('profile-settings') }}" class="dropdown-item"><i class="fas fa-user"></i> Mon profil</a>
          @if(Route::has('settings.global'))
            <a href="{{ route('settings.global') }}" class="dropdown-item"><i class="fas fa-gear"></i> Paramètres globaux</a>
          @endif
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
        <input id="globalSearchInput" class="form-control" type="text" placeholder="Recherche globale: clients, users, factures, devis, projets, notion, apps Google..." autocomplete="off">
        <div id="globalSearchSuggestions" class="global-search-suggest"></div>
      </div>

      <div class="crm-header-actions">
        <button class="btn-icon" data-tooltip="Mes applications" data-modal-open="myAppsModal"><i class="fas fa-th-large"></i></button>
        <button class="btn-icon btn-icon-notif" id="globalNotifBtn" data-tooltip="Notifications">
          <i class="fas fa-bell"></i>
          <span class="header-notif-badge" id="globalNotifCount">0</span>
        </button>
      </div>
    </header>

    @php
      $moduleMenu = null;
      if (request()->routeIs('clients.*')) {
        $moduleMenu = 'layouts.partials.module-menu-clients';
      } elseif (request()->routeIs('stock.*')) {
        $moduleMenu = 'layouts.partials.module-menu-stock';
      } elseif (request()->routeIs('invoices.*')) {
        $moduleMenu = 'layouts.partials.module-menu-invoice';
      } elseif (request()->routeIs('projects.*')) {
        $moduleMenu = 'layouts.partials.module-menu-projects';
      } elseif (request()->routeIs('notion-workspace.*')) {
        $moduleMenu = 'layouts.partials.module-menu-notion-workspace';
      } elseif (request()->routeIs('users.*') || request()->routeIs('rbac.*')) {
        $moduleMenu = 'layouts.partials.module-menu-users';
      }
    @endphp

    @if($moduleMenu)
      @include($moduleMenu)
    @endif

    <main class="crm-content">@yield('content')</main>
  </div>
</div>

<div class="modal-overlay modal-overlay-right" id="myAppsModal">
  <div class="modal apps-drawer">
    <div class="modal-header">
      <div class="modal-header-icon"><i class="fas fa-th-large"></i></div>
      <div>
        <div class="modal-title">Mes applications</div>
        <div class="modal-subtitle">{{ $layoutInstalledAppsCount ?? 0 }} installée(s)</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div class="apps-drawer-list">
        <a href="{{ route('marketplace.index') }}" class="apps-drawer-item">
          <span class="apps-drawer-icon"><i class="fas fa-store"></i></span>
          <span>
            <strong>Marketplace</strong><br>
            <small style="color:var(--c-ink-40)">Découvrir des applications</small>
          </span>
        </a>

        @forelse(($layoutInstalledAppsByCategory ?? collect()) as $category)
          <div class="apps-drawer-category">
            <i class="fas {{ $category->icon ?? 'fa-puzzle-piece' }}" style="color:{{ $category->color ?? '#64748b' }};margin-right:6px;"></i>
            {{ $category->label ?? 'Autre' }}
          </div>
          @foreach(($category->apps ?? collect()) as $app)
            <a href="{{ $app->url }}" class="apps-drawer-item">
              <span class="apps-drawer-icon" style="background:{{ $app->icon_bg_color ?? '#334155' }};color:#fff;">
                <i class="fas {{ $app->icon }}"></i>
              </span>
              <span>
                <strong>{{ $app->name }}</strong><br>
                <small style="color:var(--c-ink-40)">Ouvrir le module</small>
              </span>
              @if($app->status === 'trial')
                <span class="apps-drawer-badge">Essai</span>
              @endif
            </a>
          @endforeach
        @empty
          <div style="text-align:center;padding:18px 10px;color:var(--c-ink-40);">
            Aucune application active pour ce tenant.
          </div>
        @endforelse
      </div>
    </div>
    <div class="modal-footer" style="justify-content:flex-start">
      <a href="{{ route('marketplace.my-apps') }}" class="btn btn-secondary"><i class="fas fa-th-list"></i> Gérer mes applications</a>
    </div>
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
<script src="{{ asset('vendor/client/js/secure-form.js') }}"></script>
<script src="{{ asset('vendor/invoice/js/invoice.js') }}"></script>
<script src="{{ asset('vendor/stock/js/stock.js') }}"></script>
@php
  $globalSearchInstalledAppsMeta = ($layoutInstalledApps ?? collect())
    ->map(function ($app) {
      return [
        'slug' => (string) ($app->slug ?? ''),
        'name' => (string) ($app->name ?? ''),
        'icon' => (string) ($app->icon ?? 'fa-puzzle-piece'),
        'url' => (string) ($app->url ?? ''),
      ];
    })
    ->values()
    ->all();

  $globalSearchQuickLinks = array_values(array_filter([
    ['label' => 'Tableau de bord', 'sub' => 'Vue generale', 'icon' => 'fa-home', 'url' => url('/dashboard'), 'keywords' => 'dashboard accueil principal'],
    Route::has('applications') ? ['label' => 'Applications', 'sub' => 'Mes applications CRM', 'icon' => 'fa-th-large', 'url' => route('applications'), 'keywords' => 'apps applications modules'] : null,
    Route::has('marketplace.index') ? ['label' => 'Marketplace', 'sub' => 'Installer de nouvelles applications', 'icon' => 'fa-store', 'url' => route('marketplace.index'), 'keywords' => 'marketplace store installer'] : null,
    Route::has('settings.global') ? ['label' => 'Parametres globaux', 'sub' => 'Configuration generale', 'icon' => 'fa-sliders', 'url' => route('settings.global'), 'keywords' => 'config parametres reglage'] : null,
  ]));
@endphp
<script>
(function () {
  const layoutInstalledApps = @json(($layoutInstalledApps ?? collect())->pluck('slug')->values()->all());
  const layoutInstalledAppsMeta = @json($globalSearchInstalledAppsMeta);
  const globalQuickLinks = @json($globalSearchQuickLinks);

  function initGlobalSearch() {
    const input = document.getElementById('globalSearchInput');
    const box = document.getElementById('globalSearchSuggestions');
    if (!input || !box || typeof Http === 'undefined') return;
    const hasApp = (...slugs) => slugs.some((slug) => layoutInstalledApps.includes(slug));
    const hasClients = hasApp('clients');
    const hasStock = hasApp('stock');
    const hasInvoice = hasApp('invoice');
    const hasProjects = hasApp('projects');
    const hasNotion = hasApp('notion-workspace');
    const hasGoogleDrive = hasApp('google-drive', 'gdrive');
    const hasGoogleSheets = hasApp('google-sheets');
    const hasGoogleDocx = hasApp('google-docx');
    const hasGoogleCalendar = hasApp('google-calendar');
    const hasGoogleGmail = hasApp('google-gmail');
    const hasGoogleMeet = hasApp('google-meet');

    let timer = null;
    let requestSeq = 0;
    let activeIndex = -1;
    const close = () => {
      box.style.display = 'none';
      box.innerHTML = '';
      activeIndex = -1;
    };

    const esc = (v) => {
      const d = document.createElement('div');
      d.textContent = v || '';
      return d.innerHTML;
    };
    const normalize = (v) => String(v || '').toLowerCase();
    const hasQuery = (haystack, needle) => normalize(haystack).includes(normalize(needle));
    const ensureUrl = (url) => {
      const raw = String(url || '').trim();
      if (!raw) return '#';
      if (raw.startsWith('/')) return raw;
      if (/^https?:\/\//i.test(raw)) return raw;
      return '#';
    };
    const safeGet = async (url, params = {}) => {
      try {
        const res = await Http.get(url, params);
        if (!res || !res.ok) return { ok: false, data: {} };
        return res;
      } catch (_) {
        return { ok: false, data: {} };
      }
    };
    const dedupeRows = (rows) => {
      const seen = new Set();
      return rows.filter((row) => {
        const key = `${row.url || ''}|${row.label || ''}|${row.sub || ''}`;
        if (seen.has(key)) return false;
        seen.add(key);
        return true;
      });
    };

    const renderGroup = (title, rows) => {
      if (!rows.length) return '';
      const links = rows.map((r) => `
        <a class="global-search-item" href="${esc(ensureUrl(r.url))}"${r.external ? ' target="_blank" rel="noopener"' : ''}>
          <i class="fa ${r.icon || 'fa-link'}" style="color:${esc(r.color || 'var(--c-accent)')};width:16px;"></i>
          <div style="min-width:0;flex:1;">
            <div class="global-search-meta">
              <span style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${esc(r.label)}</span>
              ${r.badge ? `<span class="global-search-badge">${esc(r.badge)}</span>` : ''}
            </div>
            <small>${esc(r.sub || '')}</small>
          </div>
        </a>
      `).join('');
      return `<div class="global-search-group">${esc(title)}</div>${links}`;
    };
    const renderLoading = () => {
      box.innerHTML = '<div class="global-search-loading">Recherche en cours...</div>';
      box.style.display = 'block';
    };
    const renderNoResults = () => {
      box.innerHTML = '<div class="global-search-empty">Aucun resultat. Essayez un autre mot-cle.</div>';
      box.style.display = 'block';
    };
    const updateKeyboardActive = () => {
      const items = [...box.querySelectorAll('.global-search-item')];
      items.forEach((item, idx) => item.classList.toggle('is-active', idx === activeIndex));
      if (activeIndex >= 0 && items[activeIndex]) {
        items[activeIndex].scrollIntoView({ block: 'nearest' });
      }
    };
    const collectShortcuts = (q) => {
      const rows = (globalQuickLinks || []).filter((row) => {
        if (!q) return true;
        return hasQuery(row.label, q) || hasQuery(row.sub, q) || hasQuery(row.keywords, q);
      }).map((row) => ({
        label: row.label,
        sub: row.sub,
        url: row.url,
        icon: row.icon || 'fa-link',
        badge: 'Raccourci',
      }));
      return rows.slice(0, 6);
    };
    const collectApps = (q) => {
      const rows = (layoutInstalledAppsMeta || []).filter((app) => {
        if (!app?.url) return false;
        if (!q) return true;
        return hasQuery(app.name, q) || hasQuery(app.slug, q);
      }).map((app) => ({
        label: app.name,
        sub: `Application (${app.slug})`,
        url: app.url,
        icon: app.icon || 'fa-puzzle-piece',
        badge: 'App',
      }));
      return rows.slice(0, 8);
    };
    const renderGroups = (groups) => {
      const html = groups
        .filter((group) => group.rows && group.rows.length > 0)
        .map((group) => renderGroup(group.title, dedupeRows(group.rows)))
        .join('');
      if (!html.trim()) return renderNoResults();
      box.innerHTML = html;
      box.style.display = 'block';
      activeIndex = -1;
    };
    const quickMode = (q = '') => {
      renderGroups([
        { title: 'Raccourcis', rows: collectShortcuts(q) },
        { title: 'Applications', rows: collectApps(q) },
      ]);
    };
    const safeNum = (v) => {
      const n = Number(v);
      return Number.isFinite(n) ? n : 0;
    };

    const runSearch = async (rawQuery) => {
      const q = String(rawQuery || '').trim();
      if (q.length < 2) {
        quickMode(q);
        return;
      }

      const currentReq = ++requestSeq;
      const allowDeep = q.length >= 3;
      renderLoading();

      const [
        clients,
        articles,
        orders,
        invoices,
        quotes,
        users,
        projects,
        notionPages,
        driveFiles,
        spreadsheets,
        documents,
        calendarEvents,
        gmailMessages,
        meetEvents,
      ] = await Promise.all([
        hasClients ? safeGet('/clients/data/search', { q }) : Promise.resolve({ ok: false, data: {} }),
        hasStock ? safeGet('/stock/articles/data/search', { q }) : Promise.resolve({ ok: false, data: {} }),
        hasStock ? safeGet('/stock/orders/data/search', { q }) : Promise.resolve({ ok: false, data: {} }),
        hasInvoice ? safeGet('/invoices/data/table', { search: q, per_page: 5 }) : Promise.resolve({ ok: false, data: {} }),
        hasInvoice ? safeGet('/invoices/quotes/data/table', { search: q, per_page: 5 }) : Promise.resolve({ ok: false, data: {} }),
        safeGet('/users/data/table', { search: q, per_page: 5 }),
        hasProjects ? safeGet('/extensions/projects/data/list', { search: q, per_page: 5 }) : Promise.resolve({ ok: false, data: {} }),
        hasNotion ? safeGet('/extensions/notion-workspace/data/tree', { search: q, scope: 'all' }) : Promise.resolve({ ok: false, data: {} }),
        hasGoogleDrive && allowDeep ? safeGet('/extensions/google-drive/data/search', { q }) : Promise.resolve({ ok: false, data: {} }),
        hasGoogleSheets && allowDeep ? safeGet('/extensions/google-sheets/data/spreadsheets', { search: q }) : Promise.resolve({ ok: false, data: {} }),
        hasGoogleDocx && allowDeep ? safeGet('/extensions/google-docx/data/documents', { search: q }) : Promise.resolve({ ok: false, data: {} }),
        hasGoogleCalendar && allowDeep ? safeGet('/extensions/google-calendar/data/events', { search: q, per_page: 5, include_holidays: 1 }) : Promise.resolve({ ok: false, data: {} }),
        hasGoogleGmail && allowDeep ? safeGet('/extensions/google-gmail/data/messages', { q, max_results: 5, label_id: 'ALL' }) : Promise.resolve({ ok: false, data: {} }),
        hasGoogleMeet && allowDeep ? safeGet('/extensions/google-meet/data/meetings', { search: q, per_page: 5 }) : Promise.resolve({ ok: false, data: {} }),
      ]);

      if (currentReq !== requestSeq) return;

      const clientRows = (clients?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.company_name || 'Client',
        sub: row.email || row.phone || '',
        url: `/clients/${row.id}`,
        icon: 'fa-users',
      }));
      const articleRows = (articles?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.name || 'Article',
        sub: `SKU: ${row.sku || '-'} | Stock: ${safeNum(row.stock_quantity)}`,
        url: `/stock/articles/${row.id}`,
        icon: 'fa-box',
      }));
      const orderRows = (orders?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.number || `Commande #${row.id}`,
        sub: `Statut: ${row.status || '-'}`,
        url: `/stock/orders/${row.id}`,
        icon: 'fa-clipboard-list',
      }));
      const invoiceRows = (invoices?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.number || `Facture #${row.id}`,
        sub: row.client?.company_name || row.reference || '',
        url: `/invoices/${row.id}`,
        icon: 'fa-file-invoice',
      }));
      const quoteRows = (quotes?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.number || `Devis #${row.id}`,
        sub: row.client?.company_name || row.reference || row.status || '',
        url: `/invoices/quotes/${row.id}`,
        icon: 'fa-file-signature',
      }));
      const userRows = (users?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.name || row.email || `Utilisateur #${row.id}`,
        sub: [row.email, row.role_in_tenant].filter(Boolean).join(' | '),
        url: `/users/${row.id}`,
        icon: 'fa-user',
      }));
      const projectRows = (projects?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.name || `Projet #${row.id}`,
        sub: [row.client_name, row.status, row.priority].filter(Boolean).join(' | '),
        url: `/extensions/projects/${row.id}`,
        icon: 'fa-diagram-project',
      }));
      const notionRows = (notionPages?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.title || `Page #${row.id}`,
        sub: [row.client_name, row.owner_name].filter(Boolean).join(' | '),
        url: '/extensions/notion-workspace',
        icon: row.icon || 'fa-book-open',
        badge: 'Notion',
      }));
      const driveRows = (driveFiles?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.name || 'Fichier Google Drive',
        sub: [row.mime_type, row.size_formatted].filter(Boolean).join(' | '),
        url: row.web_view_link || '/extensions/google-drive',
        icon: row.icon || 'fa-google-drive',
        color: row.color || '#4285F4',
        external: Boolean(row.web_view_link),
      }));
      const sheetsRows = (spreadsheets?.data?.data?.spreadsheets || []).slice(0, 5).map((row) => ({
        label: row.title || 'Google Sheet',
        sub: row.spreadsheet_id || '',
        url: row.spreadsheet_url || '/extensions/google-sheets',
        icon: 'fa-file-excel',
        color: '#0f9d58',
        external: Boolean(row.spreadsheet_url),
      }));
      const docsRows = (documents?.data?.data?.documents || []).slice(0, 5).map((row) => ({
        label: row.title || 'Google Doc',
        sub: row.document_id || '',
        url: row.document_url || '/extensions/google-docx',
        icon: 'fa-file-word',
        color: '#1a73e8',
        external: Boolean(row.document_url),
      }));
      const calendarRows = (calendarEvents?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.summary || 'Evenement',
        sub: [row.start_display, row.location].filter(Boolean).join(' | '),
        url: `/extensions/google-calendar?event_id=${encodeURIComponent(row.event_id || '')}`,
        icon: 'fa-calendar-days',
        color: '#4285F4',
        badge: 'Calendrier',
      }));
      const gmailRows = (gmailMessages?.data?.data?.messages || []).slice(0, 5).map((row) => ({
        label: row.subject || '(Sans objet)',
        sub: [row.from, row.snippet].filter(Boolean).join(' | '),
        url: `/extensions/google-gmail?message_id=${encodeURIComponent(row.message_id || '')}`,
        icon: 'fa-envelope',
        color: '#ea4335',
        badge: row.is_read ? 'Lu' : 'Non lu',
      }));
      const meetRows = (meetEvents?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.summary || 'Reunion Meet',
        sub: [row.start_display, row.organizer_email].filter(Boolean).join(' | '),
        url: `/extensions/google-meet?event_id=${encodeURIComponent(row.event_id || '')}`,
        icon: 'fa-video',
        color: '#34a853',
        badge: 'Meet',
      }));

      renderGroups([
        { title: 'Raccourcis', rows: collectShortcuts(q) },
        { title: 'Applications', rows: collectApps(q) },
        { title: 'Clients', rows: clientRows },
        { title: 'Utilisateurs', rows: userRows },
        { title: 'Articles', rows: articleRows },
        { title: 'Commandes', rows: orderRows },
        { title: 'Factures', rows: invoiceRows },
        { title: 'Devis', rows: quoteRows },
        { title: 'Projets', rows: projectRows },
        { title: 'Notion', rows: notionRows },
        { title: 'Google Drive', rows: driveRows },
        { title: 'Google Sheets', rows: sheetsRows },
        { title: 'Google Docs', rows: docsRows },
        { title: 'Google Calendar', rows: calendarRows },
        { title: 'Google Gmail', rows: gmailRows },
        { title: 'Google Meet', rows: meetRows },
      ]);
    };

    input.addEventListener('focus', () => {
      if (!input.value.trim()) quickMode('');
    });
    input.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(() => {
        runSearch(input.value);
      }, 280);
    });
    input.addEventListener('keydown', (event) => {
      const items = [...box.querySelectorAll('.global-search-item')];
      if (!items.length) return;

      if (event.key === 'ArrowDown') {
        event.preventDefault();
        activeIndex = Math.min(items.length - 1, activeIndex + 1);
        updateKeyboardActive();
      } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        activeIndex = Math.max(0, activeIndex - 1);
        updateKeyboardActive();
      } else if (event.key === 'Enter' && activeIndex >= 0) {
        event.preventDefault();
        const target = items[activeIndex];
        if (target) target.click();
      } else if (event.key === 'Escape') {
        close();
      }
    });
    box.addEventListener('mousemove', (event) => {
      const item = event.target.closest('.global-search-item');
      if (!item) return;
      const items = [...box.querySelectorAll('.global-search-item')];
      activeIndex = items.indexOf(item);
      updateKeyboardActive();
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

  function initProTooltips() {
    const tooltip = document.createElement('div');
    tooltip.className = 'ui-tooltip';
    document.body.appendChild(tooltip);

    let activeEl = null;

    const getText = (el) => {
      if (!el) return '';
      return (el.getAttribute('data-tooltip') || el.getAttribute('title') || '').trim();
    };

    const setPosition = (el, evt) => {
      if (!el || !tooltip.classList.contains('show')) return;
      const margin = 12;
      const rect = el.getBoundingClientRect();
      const tipRect = tooltip.getBoundingClientRect();
      const x = evt?.clientX ?? (rect.left + rect.width / 2);
      let left = x - (tipRect.width / 2);
      left = Math.max(margin, Math.min(left, window.innerWidth - tipRect.width - margin));
      const top = rect.top - tipRect.height - 10;
      tooltip.style.left = `${left}px`;
      tooltip.style.top = `${Math.max(margin, top)}px`;
    };

    const show = (el, evt) => {
      const text = getText(el);
      if (!text) return;
      activeEl = el;
      tooltip.textContent = text;
      tooltip.classList.add('show');
      setPosition(el, evt);
    };

    const hide = () => {
      tooltip.classList.remove('show');
      activeEl = null;
    };

    const bind = (el) => {
      if (!el || el.dataset.tooltipBound === '1') return;
      const text = getText(el);
      if (!text) return;

      if (el.hasAttribute('title')) {
        const legacy = el.getAttribute('title');
        if (legacy && !el.getAttribute('data-tooltip')) {
          el.setAttribute('data-tooltip', legacy);
        }
        el.removeAttribute('title');
      }
      if (!el.getAttribute('aria-label')) {
        el.setAttribute('aria-label', text);
      }

      el.dataset.tooltipBound = '1';
      el.addEventListener('mouseenter', (e) => show(el, e));
      el.addEventListener('mousemove', (e) => setPosition(el, e));
      el.addEventListener('mouseleave', hide);
      el.addEventListener('focus', (e) => show(el, e));
      el.addEventListener('blur', hide);
    };

    const scan = () => {
      document.querySelectorAll('[data-tooltip], [title]').forEach(bind);
    };

    scan();
    new MutationObserver(scan).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['title', 'data-tooltip'] });
    window.addEventListener('scroll', () => activeEl && setPosition(activeEl), true);
    window.addEventListener('resize', () => activeEl && setPosition(activeEl));
  }

  function initHeaderGmailNotifications() {
    const bellBadge = document.getElementById('globalNotifCount');
    if (!bellBadge || typeof Http === 'undefined') return;
    if (!layoutInstalledApps.includes('google-gmail')) return;
    const isGmailPage = window.location.pathname.startsWith('/extensions/google-gmail');

    let lastUnread = null;

    const setBadge = (value) => {
      const unread = Math.max(0, Number(value || 0));
      bellBadge.textContent = unread > 99 ? '99+' : String(unread);
      bellBadge.style.display = unread > 0 ? 'inline-flex' : 'none';
    };

    const syncFromApi = async (silentToast = true) => {
      const { ok, data } = await Http.get('/extensions/google-gmail/data/stats', { refresh: 1 });
      if (!ok || !data?.success || !data?.data?.connected) {
        if (lastUnread !== null) setBadge(0);
        lastUnread = 0;
        return;
      }

      const unread = Number(data.data.unread_total || 0);
      if (lastUnread !== null && unread > lastUnread && !silentToast && window.Toast) {
        const delta = unread - lastUnread;
        Toast.info('Nouveaux emails', `${delta} nouvel email recu.`);
      }

      lastUnread = unread;
      setBadge(unread);
    };

    window.addEventListener('crm:gmail-unread', (event) => {
      const unread = Number(event?.detail?.unread || 0);
      lastUnread = unread;
      setBadge(unread);
    });

    syncFromApi(true);
    if (!isGmailPage) {
      setInterval(() => {
        if (document.visibilityState === 'visible') {
          syncFromApi(false);
        }
      }, 90000);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    initGlobalSearch();
    initInvoiceStockBridge();
    initProTooltips();
    initHeaderGmailNotifications();
  });
})();
</script>
@stack('scripts')
</body>
</html>
