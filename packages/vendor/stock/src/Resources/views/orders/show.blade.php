@extends('layouts.global')
@section('title', 'Commande')
@section('breadcrumb')<a href="{{ route('stock.orders.index') }}">Commandes</a><i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i><span style="color:var(--c-ink)">{{ $order->number }}</span>@endsection
@section('content')
<div class="page-header">
  <div class="page-header-left"><h1>{{ $order->number }}</h1><p>Commande fournisseur</p></div>
  <div class="page-header-actions">
    @if($order->status !== 'received')
      <button class="btn btn-secondary" onclick="receiveOrder()"><i class="fas fa-box-open"></i> Marquer recue</button>
    @endif
    <a href="{{ route('stock.orders.edit', $order) }}" class="btn btn-primary">Modifier</a>
  </div>
</div>
<div class="form-section">
  <div class="row">
    <div class="col-3"><strong>Fournisseur</strong><div>{{ $order->supplier?->name }}</div></div>
    <div class="col-3"><strong>Date commande</strong><div>{{ optional($order->order_date)->format('Y-m-d') }}</div></div>
    <div class="col-3"><strong>Statut</strong><div>{{ $order->status }}</div></div>
    <div class="col-3"><strong>Total</strong><div>{{ $order->total }}</div></div>
  </div>
</div>
<div class="table-wrapper"><table class="crm-table"><thead><tr><th>Article</th><th>Qt</th><th>Unite</th><th>Prix</th><th>Total</th></tr></thead><tbody>@foreach($order->items as $item)<tr><td>{{ $item->name }}</td><td>{{ $item->quantity }}</td><td>{{ $item->unit }}</td><td>{{ $item->unit_price }}</td><td>{{ $item->total }}</td></tr>@endforeach</tbody></table></div>
@endsection
@push('scripts')
<script>
async function receiveOrder(){
  const {ok,data} = await Http.post('{{ route('stock.orders.receive', $order) }}', {});
  if(ok){ Toast.success('Succes', data.message); window.location.reload(); }
  else { Toast.error('Erreur', data.message || 'Echec'); }
}
</script>
@endpush
