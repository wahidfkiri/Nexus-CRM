@extends('layouts.global')

@section('title', 'Fournisseurs')

@section('breadcrumb')
  <span>Stock</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Fournisseurs</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => 'fas fa-building', 'bg' => '#f3e8ff', 'color' => '#7c3aed', 'alt' => 'Fournisseurs'])
      <h1 style="margin:0;">Fournisseurs</h1>
    </div>
    <p>Référentiel fournisseurs pour les achats, réceptions et bons de livraison entrants.</p>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('stock.orders.index') }}" class="btn btn-secondary">Commandes</a>
    <a href="{{ route('stock.suppliers.export.excel') }}" class="btn btn-secondary">Export Excel</a>
    <a href="{{ route('stock.suppliers.create') }}" class="btn btn-primary">Nouveau fournisseur</a>
  </div>
</div>

@include('stock::partials.module-nav')

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Liste</span>
    <div class="table-spacer"></div>
    <div class="table-search"><i class="fas fa-search"></i><input type="text" id="searchInput" placeholder="Nom, email..."></div>
  </div>
  <table class="crm-table">
    <thead><tr><th>Nom</th><th>Contact</th><th>Email</th><th>Téléphone</th><th></th></tr></thead>
    <tbody id="suppliersTableBody"></tbody>
  </table>
  <div class="table-pagination"><span class="pagination-info" id="paginationInfo"></span><div class="pagination-spacer"></div><div class="pagination-pages" id="paginationControls"></div></div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
 window._stockSuppliersTable = new CrmTable({
  tbodyId:'suppliersTableBody',
  dataUrl:'{{ route('stock.suppliers.data') }}',
  renderRow:(s)=>`<tr><td><a href="/stock/suppliers/${s.id}" style="color:var(--c-accent);font-weight:600;text-decoration:none;">${s.name}</a></td><td>${s.contact_name ?? '—'}</td><td>${s.email ?? '—'}</td><td>${s.phone ?? '—'}</td><td><a class="btn-icon" href="/stock/suppliers/${s.id}/edit"><i class="fas fa-pen"></i></a></td></tr>`
 });
});
</script>
@endpush
