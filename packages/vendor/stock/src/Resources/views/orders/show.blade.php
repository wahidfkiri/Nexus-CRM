@extends('layouts.global')

@section('title', 'Commande')

@section('breadcrumb')
  <a href="{{ route('stock.orders.index') }}">Commandes</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $order->number }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left"><h1>{{ $order->number }}</h1><p>Commande fournisseur</p></div>
  <div class="page-header-actions">
    @if($order->status !== 'received' && $order->status !== 'cancelled')
      <button class="btn btn-secondary" onclick="receiveOrder()"><i class="fas fa-truck-ramp-box"></i> Generer BL de reception</button>
    @endif
    <a href="{{ route('stock.orders.edit', $order) }}" class="btn btn-primary">Modifier</a>
  </div>
</div>

@include('stock::partials.module-nav')

<div class="form-section" style="margin-bottom:18px;">
  <div class="row">
    <div class="col-3"><strong>Fournisseur</strong><div>{{ $order->supplier?->name }}</div></div>
    <div class="col-3"><strong>Date commande</strong><div>{{ optional($order->order_date)->format('Y-m-d') }}</div></div>
    <div class="col-3"><strong>Statut</strong><div>{{ $order->status }}</div></div>
    <div class="col-3"><strong>Total</strong><div>{{ $order->total }}</div></div>
  </div>
</div>

<div class="table-wrapper" style="margin-bottom:18px;">
  <table class="crm-table">
    <thead><tr><th>Article</th><th>Qte</th><th>Unite</th><th>Prix</th><th>Total</th></tr></thead>
    <tbody>@foreach($order->items as $item)<tr><td>{{ $item->name }}</td><td>{{ $item->quantity }}</td><td>{{ $item->unit }}</td><td>{{ $item->unit_price }}</td><td>{{ $item->total }}</td></tr>@endforeach</tbody>
  </table>
</div>

<div class="table-wrapper">
  <div class="table-header"><span class="table-title">Bons de livraison lies</span></div>
  <table class="crm-table">
    <thead><tr><th>Numero</th><th>Type</th><th>Statut</th><th>Date</th></tr></thead>
    <tbody>
      @forelse($order->deliveryNotes as $note)
        <tr>
          <td><a href="{{ route('stock.delivery-notes.show', $note) }}">{{ $note->number }}</a></td>
          <td>{{ $note->type_label }}</td>
          <td>{{ $note->status_label }}</td>
          <td>{{ optional($note->issue_date)->format('Y-m-d') ?: '-' }}</td>
        </tr>
      @empty
        <tr><td colspan="4"><div class="table-empty"><div class="table-empty-icon"><i class="fas fa-truck-ramp-box"></i></div><h3>Aucun BL</h3><p>Utilisez la reception pour generer un BL d'entree trace.</p></div></td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection

@push('scripts')
<script>
async function receiveOrder(){
  const {ok,data} = await Http.post('{{ route('stock.orders.receive', $order) }}', {});
  if(ok && data.success){
    Toast.success('Succes', data.message || 'Commande receptionnee.');

    if (data.automation?.should_prompt && window.AutomationSuggestions) {
      const flow = window.AutomationSuggestions.open(data.automation, {
        redirectUrl: data.redirect || null,
      });

      await Promise.resolve(flow).finally(() => {
        if (data.redirect) {
          window.location.href = data.redirect;
        } else {
          window.location.reload();
        }
      });

      return;
    }

    if (data.redirect) {
      window.location.href = data.redirect;
    } else {
      window.location.reload();
    }
  }
  else { Toast.error('Erreur', data.message || 'Echec'); }
}
</script>
@endpush
