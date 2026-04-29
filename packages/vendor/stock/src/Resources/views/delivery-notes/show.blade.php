@extends('layouts.global')

@section('title', 'Bon de livraison')

@section('breadcrumb')
  <a href="{{ route('stock.delivery-notes.index') }}">Bons de livraison</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $deliveryNote->number }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left"><h1>{{ $deliveryNote->number }}</h1><p>{{ $deliveryNote->type_label }} - {{ $deliveryNote->status_label }}</p></div>
  <div class="page-header-actions">
    <a href="{{ route('stock.delivery-notes.pdf', $deliveryNote) }}" class="btn btn-secondary"><i class="fas fa-file-pdf"></i> PDF</a>
    @if($deliveryNote->status === 'draft')
      <button class="btn btn-secondary" onclick="validateDeliveryNote()"><i class="fas fa-circle-check"></i> Valider</button>
      <a href="{{ route('stock.delivery-notes.edit', $deliveryNote) }}" class="btn btn-primary"><i class="fas fa-pen"></i> Modifier</a>
    @elseif($deliveryNote->status === 'validated')
      <button class="btn btn-danger" onclick="cancelDeliveryNote()"><i class="fas fa-ban"></i> Annuler</button>
    @endif
    <a href="{{ route('stock.delivery-notes.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
  </div>
</div>

<div class="row" style="align-items:flex-start;">
  <div class="col-4" style="padding-right:12px;">
    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-info-circle"></i> Informations</h3>
      <div class="row">
        <div class="col-12"><strong>Type</strong><div>{{ $deliveryNote->type_label }}</div></div>
        <div class="col-12" style="margin-top:10px;"><strong>Statut</strong><div>{{ $deliveryNote->status_label }}</div></div>
        <div class="col-12" style="margin-top:10px;"><strong>Date</strong><div>{{ optional($deliveryNote->issue_date)->format('Y-m-d') ?: '-' }}</div></div>
        <div class="col-12" style="margin-top:10px;"><strong>Reference</strong><div>{{ $deliveryNote->reference ?: '-' }}</div></div>
        <div class="col-12" style="margin-top:10px;"><strong>Fournisseur</strong><div>{{ $deliveryNote->supplier?->name ?: '-' }}</div></div>
        <div class="col-12" style="margin-top:10px;"><strong>Client</strong><div>{{ $deliveryNote->client?->company_name ?: '-' }}</div></div>
        <div class="col-12" style="margin-top:10px;"><strong>Commande liee</strong><div>@if($deliveryNote->order)<a href="{{ route('stock.orders.show', $deliveryNote->order) }}">{{ $deliveryNote->order->number }}</a>@else - @endif</div></div>
        <div class="col-12" style="margin-top:10px;"><strong>Valide le</strong><div>{{ $deliveryNote->validated_at?->format('Y-m-d H:i') ?: '-' }}</div></div>
        <div class="col-12" style="margin-top:10px;"><strong>Annule le</strong><div>{{ $deliveryNote->cancelled_at?->format('Y-m-d H:i') ?: '-' }}</div></div>
        <div class="col-12" style="margin-top:10px;"><strong>Notes</strong><div>{{ $deliveryNote->notes ?: '-' }}</div></div>
      </div>
    </div>
  </div>
  <div class="col-8" style="padding-left:12px;">
    <div class="table-wrapper" style="margin-bottom:18px;">
      <div class="table-header"><span class="table-title">Lignes du BL</span></div>
      <table class="crm-table">
        <thead><tr><th>Article</th><th>SKU</th><th>Quantite</th><th>Unite</th></tr></thead>
        <tbody>
          @foreach($deliveryNote->items as $item)
            <tr>
              <td>{{ $item->name }}</td>
              <td>{{ $item->sku ?: ($item->article?->sku ?: '-') }}</td>
              <td>{{ $item->quantity }}</td>
              <td>{{ $item->unit }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="table-wrapper">
      <div class="table-header"><span class="table-title">Mouvements de stock generes</span></div>
      <table class="crm-table">
        <thead><tr><th>Date</th><th>Article</th><th>Sens</th><th>Quantite</th><th>Raison</th></tr></thead>
        <tbody>
          @forelse($deliveryNote->movements as $movement)
            <tr>
              <td>{{ optional($movement->happened_at)->format('Y-m-d H:i') ?: '-' }}</td>
              <td>{{ $movement->article?->name ?: '-' }}</td>
              <td>{{ $movement->direction === 'in' ? 'Entree' : 'Sortie' }}</td>
              <td>{{ $movement->quantity }}</td>
              <td>{{ $movement->reason ?: '-' }}</td>
            </tr>
          @empty
            <tr><td colspan="5"><div class="table-empty"><div class="table-empty-icon"><i class="fas fa-arrows-rotate"></i></div><h3>Aucun mouvement</h3><p>Les mouvements apparaissent apres validation du BL.</p></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
async function validateDeliveryNote() {
  Modal.confirm({
    title: 'Valider ce bon de livraison ?',
    message: 'La validation va poster les mouvements de stock. Cette action enclenche la tracabilite du BL.',
    confirmText: 'Valider',
    type: 'warning',
    onConfirm: async () => {
      const { ok, data } = await Http.post('{{ route('stock.delivery-notes.validate', $deliveryNote) }}', {});
      if (!ok || !data.success) {
        Toast.error('Erreur', data.message || 'Impossible de valider le bon de livraison.');
        return;
      }

      Toast.success('Succes', data.message || 'Bon de livraison valide.');

      if (data.automation?.should_prompt && window.AutomationSuggestions) {
        const flow = window.AutomationSuggestions.open(data.automation, {
          redirectUrl: data.redirect || null,
        });

        await Promise.resolve(flow).finally(() => {
          window.location.href = data.redirect || window.location.href;
        });

        return;
      }

      window.location.href = data.redirect || window.location.href;
    }
  });
}

async function cancelDeliveryNote() {
  Modal.confirm({
    title: 'Annuler ce bon de livraison ?',
    message: 'Le systeme generera automatiquement les mouvements inverses pour conserver un historique d audit complet.',
    confirmText: 'Annuler le BL',
    type: 'danger',
    onConfirm: async () => {
      const { ok, data } = await Http.post('{{ route('stock.delivery-notes.cancel', $deliveryNote) }}', {});
      if (!ok || !data.success) {
        Toast.error('Erreur', data.message || 'Impossible d annuler le bon de livraison.');
        return;
      }
      Toast.success('Succes', data.message || 'Bon de livraison annule.');
      window.location.href = data.redirect || window.location.href;
    }
  });
}
</script>
@endpush
