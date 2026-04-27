@extends('layouts.global')
@section('title', 'Commandes stock')
@section('breadcrumb')<span>Stock</span><i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i><span style="color:var(--c-ink)">Commandes</span>@endsection
@section('content')
<div class="page-header"><div class="page-header-left"><h1>Commandes fournisseur</h1></div><div class="page-header-actions"><a href="{{ route('stock.orders.export.excel') }}" class="btn btn-secondary">Export Excel</a><a href="{{ route('stock.orders.create') }}" class="btn btn-primary">Nouvelle commande</a></div></div>
<div class="table-wrapper">
<div class="table-header"><span class="table-title">Commandes</span><div class="table-spacer"></div><div class="table-search"><i class="fas fa-search"></i><input type="text" id="searchInput" placeholder="Numero, fournisseur..."></div><select class="filter-select" data-filter="status"><option value="">Tous statuts</option>@foreach($statuses as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
<table class="crm-table"><thead><tr><th>Numero</th><th>Fournisseur</th><th>Date</th><th>Total</th><th>Statut</th><th></th></tr></thead><tbody id="ordersTableBody"></tbody></table>
<div class="table-pagination"><span class="pagination-info" id="paginationInfo"></span><div class="pagination-spacer"></div><div class="pagination-pages" id="paginationControls"></div></div>
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
 window._stockOrdersTable = new CrmTable({
  tbodyId:'ordersTableBody', dataUrl:'{{ route('stock.orders.data') }}',
  renderRow:(o)=>`<tr><td><a href="/stock/orders/${o.id}" style="color:var(--c-accent);font-weight:600;text-decoration:none;">${o.number}</a></td><td>${o.supplier?.name ?? '—'}</td><td>${o.order_date ?? '—'}</td><td>${o.total}</td><td><span class="badge badge-${o.status==='received'?'paid':(o.status==='cancelled'?'cancelled':'sent')}">${o.status}</span></td><td><a class="btn-icon" href="/stock/orders/${o.id}/edit"><i class="fas fa-pen"></i></a></td></tr>`
 });
});
</script>
@endpush
