@extends('layouts.global')

@section('title', 'Nouvelle commande')

@section('breadcrumb')
  <a href="{{ route('stock.orders.index') }}">Commandes</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Nouvelle</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left"><h1>Nouvelle commande fournisseur</h1><p>Creez une commande d'approvisionnement</p></div>
  <a href="{{ route('stock.orders.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
</div>

<form id="orderForm" action="{{ route('stock.orders.store') }}" method="POST">
@csrf
<div class="row" style="align-items:flex-start;">
  <div class="col-8" style="padding:0 12px 0 0;">
    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-file-lines"></i> Informations commande <span class="form-section-badge">Etape 1/3</span></h3>
      <div class="row">
        <div class="col-6"><div class="form-group"><label class="form-label">Fournisseur <span class="required">*</span></label><select name="supplier_id" class="form-control" required><option value="">Selectionner...</option>@foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div></div>
        <div class="col-6"><div class="form-group"><label class="form-label">Reference</label><input name="reference" class="form-control" placeholder="CMD-REF"></div></div>
        <div class="col-4"><div class="form-group"><label class="form-label">Date commande</label><input type="date" name="order_date" class="form-control" value="{{ date('Y-m-d') }}"></div></div>
        <div class="col-4"><div class="form-group"><label class="form-label">Date prevue</label><input type="date" name="expected_date" class="form-control"></div></div>
        <div class="col-4"><div class="form-group"><label class="form-label">Statut</label><select name="status" class="form-control">@foreach($statuses as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div></div>
      </div>
    </div>

    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-list"></i> Lignes commande <span class="form-section-badge">Etape 2/3</span></h3>
      <div class="order-items-wrap">
        <table>
          <thead><tr><th>Article lie</th><th>Nom *</th><th>Qt</th><th>Unite</th><th>Prix</th><th></th></tr></thead>
          <tbody id="orderItemsBody">
            <tr>
              <td><select name="items[0][article_id]" class="form-control" onchange="Stock.fillOrderLineFromArticle(this)"><option value="">-</option>@foreach($articles as $a)<option value="{{ $a->id }}" data-name="{{ $a->name }}" data-unit="{{ $a->unit }}" data-price="{{ $a->sale_price }}">{{ $a->name }} ({{ $a->sku }})</option>@endforeach</select></td>
              <td><input name="items[0][name]" class="form-control" required></td>
              <td><input type="number" name="items[0][quantity]" class="form-control" min="0.0001" step="any" value="1" required></td>
              <td><input name="items[0][unit]" class="form-control" value="piece"></td>
              <td><input type="number" name="items[0][unit_price]" class="form-control" min="0" step="any" value="0" required></td>
              <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
            </tr>
          </tbody>
        </table>
      </div>
      <button type="button" class="btn btn-ghost" onclick="Stock.addOrderLine('orderItemsBody')"><i class="fas fa-plus"></i> Ajouter une ligne</button>
    </div>

    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-note-sticky"></i> Notes</h3>
      <div class="form-group"><textarea name="notes" class="form-control" rows="4" placeholder="Notes internes..."></textarea></div>
    </div>
  </div>

  <div class="col-4" style="padding:0 0 0 12px;">
    <div class="form-section" style="margin-bottom:16px;">
      <h3 class="form-section-title"><i class="fas fa-building-columns"></i> Fiscalite <span class="form-section-badge">Etape 3/3</span></h3>
      <div class="form-group"><label class="form-label">TVA %</label><input type="number" step="any" min="0" max="100" name="tax_rate" class="form-control" value="0"></div>
    </div>
    <div class="form-section">
      <div class="form-actions" style="padding-top:0;display:flex;flex-direction:column;gap:10px;">
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-check"></i> Creer la commande</button>
        <a href="{{ route('stock.orders.index') }}" class="btn btn-secondary" style="width:100%;justify-content:center;"><i class="fas fa-times"></i> Annuler</a>
      </div>
    </div>
  </div>
</div>
</form>
@endsection

@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>Stock.bindAjaxForm('orderForm'));</script>
@endpush
