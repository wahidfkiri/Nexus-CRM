@extends('layouts.global')

@section('title', 'Article')

@section('breadcrumb')
  <a href="{{ route('stock.articles.index') }}">Articles</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $article->name }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left"><h1>{{ $article->name }}</h1><p>Article piloté via mouvements de stock auditables.</p></div>
  <div class="page-header-actions">
    <a href="{{ route('stock.movements.index', ['article_id' => $article->id]) }}" class="btn btn-secondary"><i class="fas fa-arrows-rotate"></i> Historique</a>
    <a href="{{ route('stock.articles.edit', $article) }}" class="btn btn-primary"><i class="fas fa-pen"></i> Modifier</a>
  </div>
</div>

@include('stock::partials.module-nav')

<div class="form-section" style="margin-bottom:18px;">
  <div class="row">
    <div class="col-3"><strong>SKU</strong><div>{{ $article->sku ?? '—' }}</div></div>
    <div class="col-3"><strong>Stock courant</strong><div>{{ $article->current_stock }}</div></div>
    <div class="col-3"><strong>Prix vente</strong><div>{{ $article->sale_price }}</div></div>
    <div class="col-3"><strong>Fournisseur</strong><div>{{ $article->supplier?->name ?? '—' }}</div></div>
    <div class="col-3" style="margin-top:10px;"><strong>Seuil mini</strong><div>{{ $article->min_stock }}</div></div>
    <div class="col-3" style="margin-top:10px;"><strong>Statut</strong><div>{{ $article->status }}</div></div>
    <div class="col-12" style="margin-top:10px"><strong>Description</strong><div>{{ $article->description ?: '—' }}</div></div>
  </div>
</div>

<div class="table-wrapper">
  <div class="table-header"><span class="table-title">Derniers mouvements</span></div>
  <table class="crm-table">
    <thead><tr><th>Date</th><th>BL / référence</th><th>Sens</th><th>Quantité</th><th>Raison</th></tr></thead>
    <tbody>
      @forelse($article->movements as $movement)
        <tr>
          <td>{{ optional($movement->happened_at)->format('Y-m-d H:i') ?: '—' }}</td>
          <td>@if($movement->deliveryNote)<a href="{{ route('stock.delivery-notes.show', $movement->deliveryNote) }}">{{ $movement->deliveryNote->number }}</a>@else{{ $movement->reference ?: '—' }}@endif</td>
          <td>{{ $movement->direction === 'in' ? 'Entrée' : 'Sortie' }}</td>
          <td>{{ $movement->direction === 'out' ? '-' : '+' }}{{ $movement->quantity }}</td>
          <td>{{ $movement->reason ?: '—' }}</td>
        </tr>
      @empty
        <tr><td colspan="5"><div class="table-empty"><div class="table-empty-icon"><i class="fas fa-arrows-rotate"></i></div><h3>Aucun mouvement</h3><p>Le stock de cet article n’a pas encore bougé.</p></div></td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
