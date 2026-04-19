@extends('layouts.global')
@section('title', 'Fournisseur')
@section('breadcrumb')<a href="{{ route('stock.suppliers.index') }}">Fournisseurs</a><i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i><span style="color:var(--c-ink)">{{ $supplier->name }}</span>@endsection
@section('content')
<div class="page-header"><div class="page-header-left"><h1>{{ $supplier->name }}</h1></div><div class="page-header-actions"><a href="{{ route('stock.suppliers.edit', $supplier) }}" class="btn btn-primary">Modifier</a></div></div>
<div class="form-section"><div class="row"><div class="col-4"><strong>Contact</strong><div>{{ $supplier->contact_name ?: '—' }}</div></div><div class="col-4"><strong>Email</strong><div>{{ $supplier->email ?: '—' }}</div></div><div class="col-4"><strong>Telephone</strong><div>{{ $supplier->phone ?: '—' }}</div></div><div class="col-12" style="margin-top:10px"><strong>Adresse</strong><div>{{ $supplier->address ?: '—' }}</div></div></div></div>
@endsection
