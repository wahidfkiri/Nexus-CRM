@extends('layouts.global')

@section('title', 'Modifier bon de livraison')

@section('breadcrumb')
  <a href="{{ route('stock.delivery-notes.index') }}">Bons de livraison</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $deliveryNote->number }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left"><h1>Modifier {{ $deliveryNote->number }}</h1><p>Les modifications restent possibles tant que le BL est en brouillon.</p></div>
  <a href="{{ route('stock.delivery-notes.show', $deliveryNote) }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
</div>
@include('stock::partials.module-nav')

@if($deliveryNote->status !== 'draft')
  <div class="info-card"><div class="info-card-body"><p style="margin:0;">Ce bon de livraison n'est plus modifiable car il est {{ $deliveryNote->status_label }}.</p></div></div>
@else
@php
  $articleOptionsHtml = '<option value="">-</option>';
  foreach ($articles as $article) {
      $articleOptionsHtml .= '<option value="' . $article->id . '" data-name="' . e($article->name) . '" data-sku="' . e($article->sku) . '" data-unit="' . e($article->unit) . '">' . e($article->name) . ' (' . e($article->sku ?: 'sans SKU') . ') - stock ' . number_format((float) $article->current_stock, 4, '.', '') . '</option>';
  }
@endphp
<form id="deliveryNoteForm" action="{{ route('stock.delivery-notes.update', $deliveryNote) }}" method="POST">
@csrf
@method('PUT')
<div class="row" style="align-items:flex-start;">
  <div class="col-8" style="padding:0 12px 0 0;">
    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-file-lines"></i> Informations BL</h3>
      <div class="row">
        <div class="col-4"><div class="form-group"><label class="form-label">Type <span class="required">*</span></label><select name="type" id="deliveryTypeInput" class="form-control" required><option value="in" {{ $deliveryNote->type === 'in' ? 'selected' : '' }}>BL entree</option><option value="out" {{ $deliveryNote->type === 'out' ? 'selected' : '' }}>BL sortie</option></select></div></div>
        <div class="col-4"><div class="form-group"><label class="form-label">Date</label><input type="date" name="issue_date" class="form-control" value="{{ optional($deliveryNote->issue_date)->format('Y-m-d') }}"></div></div>
        <div class="col-4"><div class="form-group"><label class="form-label">Reference</label><input type="text" name="reference" class="form-control" value="{{ $deliveryNote->reference }}"></div></div>
        <div class="col-6" id="deliverySupplierWrap"><div class="form-group"><label class="form-label">Fournisseur <span class="required">*</span></label><select name="supplier_id" class="form-control"><option value="">Selectionner...</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" {{ $deliveryNote->supplier_id == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>@endforeach</select></div></div>
        <div class="col-6" id="deliveryClientWrap"><div class="form-group"><label class="form-label">Client <span class="required">*</span></label><select name="client_id" class="form-control"><option value="">Selectionner...</option>@foreach($clients as $client)<option value="{{ $client->id }}" {{ $deliveryNote->client_id == $client->id ? 'selected' : '' }}>{{ $client->company_name }}</option>@endforeach</select></div></div>
        <div class="col-6"><div class="form-group"><label class="form-label">Commande fournisseur liee</label><select name="stock_order_id" class="form-control"><option value="">Aucune</option>@foreach($orders as $order)<option value="{{ $order->id }}" {{ $deliveryNote->stock_order_id == $order->id ? 'selected' : '' }}>{{ $order->number }} - {{ $order->supplier?->name ?? '—' }} ({{ $order->status }})</option>@endforeach</select></div></div>
      </div>
    </div>

    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-list"></i> Lignes du BL</h3>
      <div class="order-items-wrap">
        <table>
          <thead><tr><th>Article lie</th><th>SKU</th><th>Nom *</th><th>Qt</th><th>Unite</th><th></th></tr></thead>
          <tbody id="deliveryNoteItemsBody">
            @foreach($deliveryNote->items as $index => $item)
            <tr>
              <td><select name="items[{{ $index }}][article_id]" class="form-control" onchange="Stock.fillDeliveryLineFromArticle(this)"><option value="">-</option>@foreach($articles as $article)<option value="{{ $article->id }}" data-name="{{ $article->name }}" data-sku="{{ $article->sku }}" data-unit="{{ $article->unit }}" {{ $item->article_id == $article->id ? 'selected' : '' }}>{{ $article->name }} ({{ $article->sku ?: 'sans SKU' }}) - stock {{ number_format((float) $article->current_stock, 4, '.', '') }}</option>@endforeach</select></td>
              <td><input type="text" name="items[{{ $index }}][sku]" class="form-control" value="{{ $item->sku }}"></td>
              <td><input type="text" name="items[{{ $index }}][name]" class="form-control" value="{{ $item->name }}" required></td>
              <td><input type="number" name="items[{{ $index }}][quantity]" class="form-control" min="0.0001" step="any" value="{{ $item->quantity }}" required></td>
              <td><input type="text" name="items[{{ $index }}][unit]" class="form-control" value="{{ $item->unit }}"></td>
              <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <button type="button" class="btn btn-ghost" onclick="Stock.addDeliveryLine('deliveryNoteItemsBody')"><i class="fas fa-plus"></i> Ajouter une ligne</button>
    </div>

    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-note-sticky"></i> Notes</h3>
      <div class="form-group"><textarea name="notes" class="form-control" rows="4">{{ $deliveryNote->notes }}</textarea></div>
    </div>
  </div>
  <div class="col-4" style="padding:0 0 0 12px;">
    <div class="form-section">
      <div class="form-actions" style="padding-top:0;display:flex;flex-direction:column;gap:10px;">
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-check"></i> Enregistrer</button>
        <a href="{{ route('stock.delivery-notes.show', $deliveryNote) }}" class="btn btn-secondary" style="width:100%;justify-content:center;"><i class="fas fa-times"></i> Annuler</a>
      </div>
    </div>
  </div>
</div>
</form>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  window.StockArticleOptionsHtml = @json($articleOptionsHtml ?? '');
  @if($deliveryNote->status === 'draft')
  Stock.bindAjaxForm('deliveryNoteForm');
  Stock.toggleDeliveryType(document.getElementById('deliveryTypeInput')?.value || 'in');
  document.getElementById('deliveryTypeInput')?.addEventListener('change', (event) => Stock.toggleDeliveryType(event.target.value));
  @endif
});
</script>
@endpush
