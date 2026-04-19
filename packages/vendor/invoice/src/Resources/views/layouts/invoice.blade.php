<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Facturation') — {{ config('app.name') }}</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('vendor/client/css/crm.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/invoice/css/invoice.css') }}">

  @stack('styles')
</head>
<body>

<div class="crm-layout">

  <!-- ============================================================ SIDEBAR -->
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
      <a href="{{ url('/dashboard') }}" class="{{ request()->is('dashboard') ? 'active' : '' }}">
        <i class="fas fa-grid-2"></i> Dashboard
      </a>

      <div class="sidebar-nav-section">CRM</div>
      <a href="{{ route('clients.index') }}" class="{{ request()->routeIs('clients.*') ? 'active' : '' }}">
        <i class="fas fa-users"></i> Clients
      </a>

      <div class="sidebar-nav-section">Facturation</div>
      <a href="{{ route('invoices.index') }}" class="{{ request()->routeIs('invoices.index') || (request()->routeIs('invoices.*') && !request()->routeIs('invoices.quotes.*') && !request()->routeIs('invoices.reports.*') && !request()->routeIs('invoices.settings.*') && !request()->routeIs('invoices.payments.*')) ? 'active' : '' }}">
        <i class="fas fa-file-invoice"></i> Factures
        @php $overdueCount = \Vendor\Invoice\Models\Invoice::overdue()->count(); @endphp
        @if($overdueCount > 0)
          <span class="nav-badge">{{ $overdueCount }}</span>
        @endif
      </a>
      <a href="{{ route('invoices.quotes.index') }}" class="{{ request()->routeIs('invoices.quotes.*') ? 'active' : '' }}">
        <i class="fas fa-file-signature"></i> Devis
        @php $pendingQuotes = \Vendor\Invoice\Models\Quote::byStatus('sent')->count(); @endphp
        @if($pendingQuotes > 0)
          <span class="nav-badge">{{ $pendingQuotes }}</span>
        @endif
      </a>
      <a href="{{ route('invoices.payments.index') }}" class="{{ request()->routeIs('invoices.payments.*') ? 'active' : '' }}">
        <i class="fas fa-credit-card"></i> Paiements
      </a>
      <a href="{{ route('invoices.reports.index') }}" class="{{ request()->routeIs('invoices.reports.*') ? 'active' : '' }}">
        <i class="fas fa-chart-line"></i> Rapports
      </a>

      <div class="sidebar-nav-section">Configuration</div>
      <a href="{{ route('invoices.settings.index') }}" class="{{ request()->routeIs('invoices.settings.*') ? 'active' : '' }}">
        <i class="fas fa-sliders"></i> Paramètres facturation
      </a>
      <a href="#" class="{{ request()->is('settings*') ? 'active' : '' }}">
        <i class="fas fa-gear"></i> Paramètres généraux
      </a>

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
          <div class="dropdown-divider"></div>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="dropdown-item danger" style="width:100%;border:none;background:none;cursor:pointer;text-align:left;">
              <i class="fas fa-right-from-bracket"></i> Déconnexion
            </button>
          </form>
        </div>
      </div>
    </div>
  </aside>

  <!-- ============================================================ MAIN -->
  <div class="crm-main">

    <header class="crm-header">
      <button id="sidebarToggle" class="btn-icon" style="display:none">
        <i class="fas fa-bars"></i>
      </button>

      <div class="crm-header-breadcrumb">
        @yield('breadcrumb')
      </div>

      <div class="crm-header-spacer"></div>

      <div class="crm-header-actions">
        <button class="btn-icon" title="Notifications">
          <i class="fas fa-bell"></i>
        </button>
        <div class="dropdown">
          <button class="btn-icon" data-dropdown-toggle title="Créer">
            <i class="fas fa-plus"></i>
          </button>
          <div class="dropdown-menu">
            <a href="{{ route('invoices.create') }}" class="dropdown-item">
              <i class="fas fa-file-invoice"></i> Nouvelle facture
            </a>
            <a href="{{ route('invoices.quotes.create') }}" class="dropdown-item">
              <i class="fas fa-file-signature"></i> Nouveau devis
            </a>
            <a href="{{ route('clients.create') }}" class="dropdown-item">
              <i class="fas fa-user-plus"></i> Nouveau client
            </a>
          </div>
        </div>
      </div>
    </header>

    <main class="crm-content">
      @yield('content')
    </main>

  </div>
</div>

<!-- Global Confirm Modal -->
<div class="modal-overlay" id="confirmModal">
  <div class="modal modal-sm">
    <div class="modal-body" style="text-align:center;padding:36px 28px;">
      <div class="modal-confirm-icon danger">
        <i class="fas fa-exclamation-triangle"></i>
      </div>
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
@stack('scripts')

</body>
</html>