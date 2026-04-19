@extends('layouts.global')

@section('title', 'Nouveau fournisseur')

@section('breadcrumb')
  <a href="{{ route('stock.suppliers.index') }}">Fournisseurs</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Nouveau</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left"><h1>Nouveau fournisseur</h1><p>Ajoutez un fournisseur au module stock</p></div>
  <a href="{{ route('stock.suppliers.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
</div>

<form id="supplierForm" action="{{ route('stock.suppliers.store') }}" method="POST">
@csrf
<div class="row" style="align-items:flex-start;">
  <div class="col-8" style="padding:0 12px 0 0;">
    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-truck-field"></i> Informations generales <span class="form-section-badge">Etape 1/2</span></h3>
      <div class="row">
        <div class="col-6"><div class="form-group"><label class="form-label">Nom <span class="required">*</span></label><input name="name" class="form-control" required></div></div>
        <div class="col-6"><div class="form-group"><label class="form-label">Contact principal</label><input name="contact_name" class="form-control"></div></div>
        <div class="col-6"><div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div></div>
        <div class="col-6"><div class="form-group"><label class="form-label">Telephone</label><input name="phone" class="form-control"></div></div>
      </div>
    </div>

    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-location-dot"></i> Adresse</h3>
      <div class="row">
        <div class="col-12"><div class="form-group"><label class="form-label">Adresse</label><textarea name="address" class="form-control" rows="2"></textarea></div></div>
        <div class="col-6"><div class="form-group"><label class="form-label">Ville</label><input name="city" class="form-control"></div></div>
        <div class="col-6"><div class="form-group"><label class="form-label">Pays</label><input name="country" class="form-control"></div></div>
      </div>
    </div>

    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-note-sticky"></i> Notes</h3>
      <div class="form-group"><textarea name="notes" class="form-control" rows="4" placeholder="Informations complementaires..."></textarea></div>
    </div>
  </div>

  <div class="col-4" style="padding:0 0 0 12px;">
    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-check-circle"></i> Actions <span class="form-section-badge">Etape 2/2</span></h3>
      <div class="form-actions" style="padding-top:0;display:flex;flex-direction:column;gap:10px;">
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-check"></i> Creer le fournisseur</button>
        <a href="{{ route('stock.suppliers.index') }}" class="btn btn-secondary" style="width:100%;justify-content:center;"><i class="fas fa-times"></i> Annuler</a>
      </div>
    </div>
  </div>
</div>
</form>
@endsection

@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>Stock.bindAjaxForm('supplierForm'));</script>
@endpush
