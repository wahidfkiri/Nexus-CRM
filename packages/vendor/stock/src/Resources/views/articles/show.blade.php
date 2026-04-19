@extends('layouts.global')

@section('title', 'Article')

@section('breadcrumb')
  <a href="{{ route('stock.articles.index') }}">Articles</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $article->name }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left"><h1>{{ $article->name }}</h1></div>
  <div class="page-header-actions"><a href="{{ route('stock.articles.edit', $article) }}" class="btn btn-primary"><i class="fas fa-pen"></i> Modifier</a></div>
</div>
<div class="form-section">
  <div class="row">
    <div class="col-3"><strong>SKU</strong><div>{{ $article->sku ?? '—' }}</div></div>
    <div class="col-3"><strong>Stock</strong><div>{{ $article->stock_quantity }}</div></div>
    <div class="col-3"><strong>Prix vente</strong><div>{{ $article->sale_price }}</div></div>
    <div class="col-3"><strong>Fournisseur</strong><div>{{ $article->supplier?->name ?? '—' }}</div></div>
    <div class="col-12" style="margin-top:10px"><strong>Description</strong><div>{{ $article->description ?: '—' }}</div></div>
  </div>
</div>
@endsection
