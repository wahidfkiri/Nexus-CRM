@extends('layouts.global')

@section('title', 'Historique de stock')

@section('breadcrumb')
  <span>Stock</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Historique stock</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => 'fas fa-arrows-rotate', 'bg' => '#eff6ff', 'color' => '#1d4ed8', 'alt' => 'Historique stock'])
      <h1 style="margin:0;">Historique de stock</h1>
    </div>
    <p>Consultez tous les mouvements qui expliquent chaque variation de stock.</p>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('stock.delivery-notes.index') }}" class="btn btn-secondary"><i class="fas fa-truck-ramp-box"></i> Bons de livraison</a>
    <a href="{{ route('stock.movements.export.excel') }}" class="btn btn-primary"><i class="fas fa-file-excel"></i> Export Excel</a>
  </div>
</div>

@include('stock::partials.module-nav')

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Ledger stock</span>
    <div class="table-spacer"></div>
    <input type="date" class="filter-select" data-filter="date_from">
    <input type="date" class="filter-select" data-filter="date_to">
    <select class="filter-select" data-filter="article_id"><option value="">Tous articles</option>@foreach($articles as $article)<option value="{{ $article->id }}" {{ $selectedArticleId == $article->id ? 'selected' : '' }}>{{ $article->name }}{{ $article->sku ? ' (' . $article->sku . ')' : '' }}</option>@endforeach</select>
    <select class="filter-select" data-filter="direction"><option value="">Tous sens</option>@foreach($directions as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
    <select class="filter-select" data-filter="movement_type"><option value="">Tous types</option>@foreach($movementTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
  </div>
  <table class="crm-table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Article</th>
        <th>Type</th>
        <th>Sens</th>
        <th>Quantité</th>
        <th>Référence</th>
        <th>Raison</th>
      </tr>
    </thead>
    <tbody id="stockMovementsTableBody"></tbody>
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
  window._stockMovementsTable = new CrmTable({
    tbodyId: 'stockMovementsTableBody',
    dataUrl: '{{ route('stock.movements.data') }}',
    renderRow: (movement) => `
      <tr>
        <td>${movement.happened_at_display ?? Stock.formatDateTime(movement.happened_at)}</td>
        <td>${movement.article?.name ?? '—'}</td>
        <td>${movement.movement_type_label ?? movement.movement_type}</td>
        <td>${movement.direction_label ?? (movement.direction === 'in' ? 'Entrée' : 'Sortie')}</td>
        <td>${movement.direction === 'out' ? '-' : '+'}${movement.quantity}</td>
        <td>${movement.display_reference ?? movement.reference ?? '—'}</td>
        <td>${movement.display_reason ?? movement.reason ?? '—'}</td>
      </tr>`,
  });

  const articleFilter = document.querySelector('[data-filter="article_id"]');
  if (articleFilter && articleFilter.value) {
    window._stockMovementsTable.state.filters.article_id = articleFilter.value;
    window._stockMovementsTable.state.page = 1;
    window._stockMovementsTable.load();
  }
});
</script>
@endpush
