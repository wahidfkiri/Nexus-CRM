@extends('layouts.global')

@section('title', 'Nouveau bon de livraison')

@section('breadcrumb')
  <a href="{{ route('stock.delivery-notes.index') }}">Bons de livraison</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Nouveau</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left"><h1>Nouveau bon de livraison</h1><p>Preparez un BL d'entree ou de sortie avant validation.</p></div>
  <a href="{{ route('stock.delivery-notes.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
</div>
@include('stock::partials.module-nav')

@php
  $articleOptionsHtml = '<option value="">-</option>';
  foreach ($articles as $article) {
      $articleOptionsHtml .= '<option value="' . $article->id . '" data-name="' . e($article->name) . '" data-sku="' . e($article->sku) . '" data-unit="' . e($article->unit) . '">' . e($article->name) . ' (' . e($article->sku ?: 'sans SKU') . ') - stock ' . number_format((float) $article->current_stock, 4, '.', '') . '</option>';
  }
@endphp

<form id="deliveryNoteForm" action="{{ route('stock.delivery-notes.store') }}" method="POST">
@csrf
<div class="row" style="align-items:flex-start;">
  <div class="col-8" style="padding:0 12px 0 0;">
    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-file-lines"></i> Informations BL</h3>
      <div class="row">
        <div class="col-4"><div class="form-group"><label class="form-label">Type <span class="required">*</span></label><select name="type" id="deliveryTypeInput" class="form-control" required><option value="in">BL entree</option><option value="out">BL sortie</option></select></div></div>
        <div class="col-4"><div class="form-group"><label class="form-label">Date</label><input type="date" name="issue_date" class="form-control" value="{{ date('Y-m-d') }}"></div></div>
        <div class="col-4"><div class="form-group"><label class="form-label">Reference</label><input type="text" name="reference" class="form-control" placeholder="REF-BL"></div></div>
        <div class="col-6" id="deliverySupplierWrap"><div class="form-group"><label class="form-label">Fournisseur <span class="required">*</span></label><select name="supplier_id" class="form-control"><option value="">Selectionner...</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select></div></div>
        <div class="col-6" id="deliveryClientWrap" style="display:none;"><div class="form-group"><label class="form-label">Client <span class="required">*</span></label><select name="client_id" class="form-control" disabled><option value="">Selectionner...</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->company_name }}</option>@endforeach</select></div></div>
        <div class="col-6"><div class="form-group"><label class="form-label">Commande fournisseur liee</label><select name="stock_order_id" class="form-control"><option value="">Aucune</option>@foreach($orders as $order)<option value="{{ $order->id }}">{{ $order->number }} - {{ $order->supplier?->name ?? '—' }} ({{ $order->status }})</option>@endforeach</select></div></div>
      </div>
    </div>

    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-list"></i> Lignes du BL</h3>
      <div class="order-items-wrap">
        <table>
          <thead><tr><th>Article lie</th><th>SKU</th><th>Nom *</th><th>Qt</th><th>Unite</th><th></th></tr></thead>
          <tbody id="deliveryNoteItemsBody">
            <tr>
              <td><select name="items[0][article_id]" class="form-control" onchange="Stock.fillDeliveryLineFromArticle(this)">{!! $articleOptionsHtml !!}</select></td>
              <td><input type="text" name="items[0][sku]" class="form-control" placeholder="SKU"></td>
              <td><input type="text" name="items[0][name]" class="form-control" required></td>
              <td><input type="number" name="items[0][quantity]" class="form-control" min="0.0001" step="any" value="1" required></td>
              <td><input type="text" name="items[0][unit]" class="form-control" value="piece"></td>
              <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
            </tr>
          </tbody>
        </table>
      </div>
      <button type="button" class="btn btn-ghost" onclick="Stock.addDeliveryLine('deliveryNoteItemsBody')"><i class="fas fa-plus"></i> Ajouter une ligne</button>
    </div>

    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-note-sticky"></i> Notes</h3>
      <div class="form-group"><textarea name="notes" class="form-control" rows="4" placeholder="Commentaires internes ou details logistiques..."></textarea></div>
    </div>
  </div>
  <div class="col-4" style="padding:0 0 0 12px;">
    <div class="form-section">
      <div class="form-actions" style="padding-top:0;display:flex;flex-direction:column;gap:10px;">
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-check"></i> Creer le BL</button>
        <a href="{{ route('stock.delivery-notes.index') }}" class="btn btn-secondary" style="width:100%;justify-content:center;"><i class="fas fa-times"></i> Annuler</a>
      </div>
    </div>
  </div>
</div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  window.StockArticleOptionsHtml = @json($articleOptionsHtml);
  Stock.bindAjaxForm('deliveryNoteForm');
  Stock.toggleDeliveryType(document.getElementById('deliveryTypeInput')?.value || 'in');
  document.getElementById('deliveryTypeInput')?.addEventListener('change', (event) => Stock.toggleDeliveryType(event.target.value));
});
</script>
@endpush
