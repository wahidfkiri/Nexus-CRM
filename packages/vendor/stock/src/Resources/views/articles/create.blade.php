@extends('layouts.global')

@section('title', 'Nouvel article')

@section('breadcrumb')
  <a href="{{ route('stock.articles.index') }}">Articles</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Nouveau</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left"><h1>Nouvel article</h1><p>Ajoutez un article a votre catalogue stock</p></div>
  <a href="{{ route('stock.articles.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
</div>

<form id="articleForm" action="{{ route('stock.articles.store') }}" method="POST">
@csrf
<div class="row" style="align-items:flex-start;">
  <div class="col-8" style="padding:0 12px 0 0;">
    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-box"></i> Informations generales <span class="form-section-badge">Etape 1/3</span></h3>
      <div class="row">
        <div class="col-4"><div class="form-group"><label class="form-label">SKU</label><input name="sku" class="form-control" placeholder="ART-001"></div></div>
        <div class="col-8"><div class="form-group"><label class="form-label">Nom de l'article <span class="required">*</span></label><input name="name" class="form-control" placeholder="Article" required></div></div>
        <div class="col-6"><div class="form-group"><label class="form-label">Fournisseur</label><select name="supplier_id" class="form-control"><option value="">Selectionner...</option>@foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div></div>
        <div class="col-6"><div class="form-group"><label class="form-label">Unite</label><input name="unit" class="form-control" value="piece"></div></div>
      </div>
    </div>

    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-warehouse"></i> Stock <span class="form-section-badge">Etape 2/3</span></h3>
      <div class="row">
        <div class="col-6"><div class="form-group"><label class="form-label">Quantite en stock</label><input type="number" step="any" min="0" name="stock_quantity" class="form-control" value="0"></div></div>
        <div class="col-6"><div class="form-group"><label class="form-label">Seuil minimum</label><input type="number" step="any" min="0" name="min_stock" class="form-control" value="0"></div></div>
      </div>
    </div>

    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-note-sticky"></i> Description</h3>
      <div class="form-group"><textarea name="description" class="form-control" rows="4" placeholder="Details de l'article..."></textarea></div>
    </div>
  </div>

  <div class="col-4" style="padding:0 0 0 12px;">
    <div class="form-section" style="margin-bottom:16px;">
      <h3 class="form-section-title"><i class="fas fa-tags"></i> Prix <span class="form-section-badge">Etape 3/3</span></h3>
      <div class="form-group"><label class="form-label">Prix achat</label><input type="number" step="any" min="0" name="purchase_price" class="form-control" value="0"></div>
      <div class="form-group"><label class="form-label">Prix vente <span class="required">*</span></label><input type="number" step="any" min="0" name="sale_price" class="form-control" value="0" required></div>
      <div class="form-group"><label class="form-label">Statut</label><select name="status" class="form-control">@foreach($statuses as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
    </div>

    <div class="form-section">
      <div class="form-actions" style="padding-top:0;display:flex;flex-direction:column;gap:10px;">
        <button type="submit" class="btn btn-primary" id="submitBtn" style="width:100%;justify-content:center;"><i class="fas fa-check"></i> Creer l'article</button>
        <a href="{{ route('stock.articles.index') }}" class="btn btn-secondary" style="width:100%;justify-content:center;"><i class="fas fa-times"></i> Annuler</a>
      </div>
    </div>
  </div>
</div>
</form>
@endsection

@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>Stock.bindAjaxForm('articleForm'));</script>
@endpush
