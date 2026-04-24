@extends('layouts.global')

@section('title', 'Articles stock')

@section('breadcrumb')
  <span>Stock</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Articles</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <h1>Articles</h1>
    <p>Gestion du catalogue et des niveaux de stock</p>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('stock.articles.export.excel') }}" class="btn btn-secondary"><i class="fas fa-file-excel"></i> Export Excel</a>
    <a href="{{ route('stock.articles.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Nouvel article</a>
  </div>
</div>
@if(!empty($marketplaceSuggestions))
  <div class="module-app-suggestions">
    @foreach($marketplaceSuggestions as $suggestion)
      <article class="module-app-suggestion-card">
        <div class="module-app-suggestion-icon">
          <i class="{{ $suggestion['icon'] ?? 'fas fa-puzzle-piece' }}"></i>
        </div>
        <div class="module-app-suggestion-body">
          <h3>{{ $suggestion['name'] ?? 'Application' }}</h3>
          <p>{{ $suggestion['description'] ?? '' }}</p>
        </div>
        <a href="{{ $suggestion['url'] ?? route('marketplace.index') }}" class="btn btn-secondary btn-sm">
          <i class="fas fa-store"></i> Installer
        </a>
      </article>
    @endforeach
  </div>
@endif

<div class="stock-grid">
  <div class="stock-card"><h4>Articles</h4><div id="kpiArticles" class="v">-</div></div>
  <div class="stock-card"><h4>Rupture imminente</h4><div id="kpiLowStock" class="v stock-kpi-low">-</div></div>
  <div class="stock-card"><h4>Fournisseurs</h4><div id="kpiSuppliers" class="v">-</div></div>
  <div class="stock-card"><h4>Commandes</h4><div id="kpiOrders" class="v">-</div></div>
</div>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Catalogue</span>
    <div class="table-spacer"></div>
    <div class="table-search"><i class="fas fa-search"></i><input type="text" id="searchInput" placeholder="Nom, SKU..."></div>
    <select class="filter-select" data-filter="status">
      <option value="">Tous statuts</option>
      @foreach($statuses as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>
  </div>
  <table class="crm-table">
    <thead>
      <tr>
        <th>SKU</th><th>Nom</th><th>Fournisseur</th><th>Stock</th><th>Prix vente</th><th>Statut</th><th></th>
      </tr>
    </thead>
    <tbody id="articlesTableBody"></tbody>
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
  document.addEventListener('DOMContentLoaded', () => {
    Stock.loadStats('{{ route('stock.stats') }}');
    window._stockArticlesTable = new CrmTable({
      tbodyId: 'articlesTableBody',
      dataUrl: '{{ route('stock.articles.data') }}',
      renderRow: (a) => `
        <tr>
          <td>${a.sku ?? '—'}</td>
          <td><a href="/stock/articles/${a.id}" style="color:var(--c-accent);font-weight:600;text-decoration:none;">${a.name}</a></td>
          <td>${a.supplier?.name ?? '—'}</td>
          <td>${a.stock_quantity}</td>
          <td>${a.sale_price}</td>
          <td><span class="badge badge-${a.status === 'active' ? 'paid' : 'cancelled'}">${a.status}</span></td>
          <td><a class="btn-icon" href="/stock/articles/${a.id}/edit"><i class="fas fa-pen"></i></a></td>
        </tr>
      `,
    });
  });
</script>
@endpush

