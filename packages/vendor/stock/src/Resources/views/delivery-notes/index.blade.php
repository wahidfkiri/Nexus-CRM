@extends('layouts.global')

@section('title', 'Bons de livraison')

@section('breadcrumb')
  <span>Stock</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Bons de livraison</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => 'fas fa-truck-ramp-box', 'bg' => '#ecfeff', 'color' => '#0f766e', 'alt' => 'Bons de livraison'])
      <h1 style="margin:0;">Bons de livraison</h1>
    </div>
    <p>Recevez, livrez et tracez chaque mouvement de stock via les BL.</p>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('stock.movements.index') }}" class="btn btn-secondary"><i class="fas fa-arrows-rotate"></i> Historique stock</a>
    <a href="{{ route('stock.delivery-notes.export.excel') }}" class="btn btn-secondary"><i class="fas fa-file-excel"></i> Export Excel</a>
    <a href="{{ route('stock.delivery-notes.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Nouveau BL</a>
  </div>
</div>

@include('stock::partials.module-nav')

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Registre des BL</span>
    <div class="table-spacer"></div>
    <div class="table-search"><i class="fas fa-search"></i><input type="text" id="searchInput" placeholder="Numéro, référence, fournisseur, client..."></div>
    <select class="filter-select" data-filter="type"><option value="">Tous types</option>@foreach($types as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
    <select class="filter-select" data-filter="status"><option value="">Tous statuts</option>@foreach($statuses as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
  </div>
  <table class="crm-table">
    <thead>
      <tr>
        <th>Numéro</th>
        <th>Type</th>
        <th>Partenaire</th>
        <th>Date</th>
        <th>Statut</th>
        <th>Lignes</th>
        <th></th>
      </tr>
    </thead>
    <tbody id="deliveryNotesTableBody"></tbody>
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
  window._stockDeliveryNotesTable = new CrmTable({
    tbodyId: 'deliveryNotesTableBody',
    dataUrl: '{{ route('stock.delivery-notes.data') }}',
    renderRow: (note) => {
      const partner = note.type === 'in' ? (note.supplier?.name ?? '—') : (note.client?.company_name ?? '—');
      const typeLabel = note.type === 'in' ? 'BL entrée' : 'BL sortie';
      const badgeTone = note.status === 'validated' ? 'paid' : (note.status === 'cancelled' ? 'cancelled' : 'sent');
      return `
        <tr>
          <td><a href="/stock/delivery-notes/${note.id}" style="color:var(--c-accent);font-weight:600;text-decoration:none;">${note.number}</a><div style="font-size:11px;color:var(--c-ink-40);">${note.reference ?? '—'}</div></td>
          <td>${typeLabel}</td>
          <td>${partner}</td>
          <td>${Stock.formatDate(note.issue_date)}</td>
          <td><span class="badge badge-${badgeTone}">${note.status}</span></td>
          <td>${note.items?.length ?? 0}</td>
          <td><a class="btn-icon" href="/stock/delivery-notes/${note.id}/edit"><i class="fas fa-pen"></i></a></td>
        </tr>`;
    },
  });
});
</script>
@endpush
