@extends('layouts.global')

@section('title', $extension->name)

@section('breadcrumb')
  <a href="{{ route('superadmin.extensions.index') }}">Extensions</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $extension->name }}</span>
@endsection

@section('content')

@php $color = $extension->category_color; @endphp

<div class="page-header">
  <div class="page-header-left" style="display:flex;align-items:center;gap:16px;">
    <div style="width:56px;height:56px;border-radius:16px;background:{{ $color }}18;display:flex;align-items:center;justify-content:center;font-size:26px;border:1px solid {{ $color }}22;flex-shrink:0;">
      @if($extension->icon_url)
        <img src="{{ $extension->icon_url }}" style="width:34px;height:34px;object-fit:contain;" alt="">
      @else
        <i class="fas {{ $extension->category_icon }}" style="color:{{ $color }};"></i>
      @endif
    </div>
    <div>
      <h1 style="margin-bottom:6px;">{{ $extension->name }}</h1>
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <span class="badge badge-{{ $extension->status === 'active' ? 'actif' : 'inactif' }}">{{ $extension->status_label }}</span>
        @if($extension->is_featured)
          <span style="background:#fef3c7;color:#92400e;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;"><i class="fas fa-star" style="font-size:9px;"></i> Vedette</span>
        @endif
        @if($extension->is_official)
          <span style="background:#f3e8ff;color:#7c3aed;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;"><i class="fas fa-certificate" style="font-size:9px;"></i> Officiel</span>
        @endif
        <span style="font-size:12px;color:var(--c-ink-40);">v{{ $extension->version }} · {{ $extension->slug }}</span>
      </div>
    </div>
  </div>
  <div class="page-header-actions">
    <button class="btn btn-secondary" onclick="toggleFeatured({{ $extension->id }})">
      <i class="fas fa-star" style="color:{{ $extension->is_featured ? '#f59e0b' : 'var(--c-ink-40)' }};"></i>
      {{ $extension->is_featured ? 'Retirer vedette' : 'Mettre en vedette' }}
    </button>
    <button class="btn btn-secondary" onclick="toggleStatus({{ $extension->id }}, '{{ $extension->status }}')">
      <i class="fas fa-{{ $extension->status === 'active' ? 'pause' : 'play' }}"></i>
      {{ $extension->status === 'active' ? 'Désactiver' : 'Activer' }}
    </button>
    <a href="{{ route('superadmin.extensions.edit', $extension) }}" class="btn btn-primary">
      <i class="fas fa-pen"></i> Modifier
    </a>
  </div>
</div>

{{-- KPIs --}}
<div class="stats-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:24px;">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent);"><i class="fas fa-download"></i></div>
    <div class="stat-body"><div class="stat-value">{{ number_format($extension->installs_count) }}</div><div class="stat-label">Installations</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success);"><i class="fas fa-plug"></i></div>
    <div class="stat-body"><div class="stat-value">{{ number_format($extension->active_installs_count) }}</div><div class="stat-label">Actives</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fef3c7;color:#92400e;"><i class="fas fa-star"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $extension->rating ?: '—' }}</div><div class="stat-label">Note / 5</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-info-lt);color:var(--c-info);"><i class="fas fa-comments"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $extension->approved_reviews_count }}</div><div class="stat-label">Avis</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#dcfce7;color:#15803d;"><i class="fas fa-euro-sign"></i></div>
    <div class="stat-body">
      <div class="stat-value">{{ $extension->is_free ? 'Gratuit' : number_format($extension->price,2).' '.$extension->currency }}</div>
      <div class="stat-label">{{ $extension->is_free ? 'Tarification' : config("extensions.billing_cycles.{$extension->billing_cycle}", '') }}</div>
    </div>
  </div>
</div>

<div class="row" style="align-items:flex-start;">
  <div class="col-8" style="padding:0 12px 0 0;">

    {{-- Activations tenants --}}
    <div class="table-wrapper">
      <div class="table-header">
        <span class="table-title">Activations tenants</span>
        <span class="table-count">{{ $activations->total() }}</span>
      </div>
      <table class="crm-table">
        <thead>
          <tr>
            <th>Tenant</th>
            <th>Statut</th>
            <th>Activé par</th>
            <th>Date</th>
            <th>Prix payé</th>
            <th style="text-align:right;padding-right:20px">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($activations as $act)
          @php
            $stMap = ['active'=>['actif','Active'],'trial'=>['info','Essai'],'inactive'=>['inactif','Inactive'],'suspended'=>['inactif','Suspendue'],'pending'=>['warning','En attente']];
            $stCls = $stMap[$act->status] ?? ['secondary', $act->status];
          @endphp
          <tr>
            <td style="font-weight:var(--fw-semi);">{{ $act->tenant->name ?? '—' }}</td>
            <td><span class="badge badge-{{ $stCls[0] }}">{{ $stCls[1] }}</span></td>
            <td style="font-size:13px;color:var(--c-ink-60);">{{ $act->activatedByUser->name ?? '—' }}</td>
            <td style="font-size:13px;color:var(--c-ink-60);">{{ $act->activated_at?->format('d/m/Y') ?? '—' }}</td>
            <td style="font-size:13px;">
              {!! $act->price_paid > 0 ? number_format($act->price_paid,2).' '.$act->currency : '<span style="color:var(--c-ink-40);">Gratuit</span>' !!}
            </td>
            <td>
              <div class="row-actions" style="justify-content:flex-end;padding-right:4px;">
                @if(in_array($act->status, ['active','trial']))
                  <button class="btn-icon danger" onclick="suspendTenantAct({{ $act->id }})" title="Suspendre">
                    <i class="fas fa-ban"></i>
                  </button>
                @elseif($act->status === 'suspended')
                  <button class="btn-icon" onclick="restoreTenantAct({{ $act->id }})" title="Restaurer">
                    <i class="fas fa-check-circle"></i>
                  </button>
                @endif
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--c-ink-40);">Aucune activation</td></tr>
          @endforelse
        </tbody>
      </table>
      @if($activations->hasPages())
      <div class="table-pagination">
        {{ $activations->links() }}
      </div>
      @endif
    </div>
  </div>

  <div class="col-4" style="padding:0 0 0 12px;">
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-circle-info"></i><h3>Informations</h3></div>
      <div class="info-card-body">
        <div class="info-row"><span class="info-row-label">Catégorie</span>
          <span style="background:{{ $color }}18;color:{{ $color }};padding:3px 10px;border-radius:99px;font-size:11.5px;font-weight:600;">
            <i class="fas {{ $extension->category_icon }}" style="font-size:10px;margin-right:4px;"></i>{{ $extension->category_label }}
          </span>
        </div>
        <div class="info-row"><span class="info-row-label">Tarification</span><span class="info-row-value">{{ $extension->pricing_label }}</span></div>
        <div class="info-row"><span class="info-row-label">Essai</span><span class="info-row-value">{{ $extension->has_trial ? $extension->trial_days.' jours' : 'Non' }}</span></div>
        @if($extension->developer_name)
        <div class="info-row"><span class="info-row-label">Éditeur</span><span class="info-row-value">{{ $extension->developer_name }}</span></div>
        @endif
        <div class="info-row"><span class="info-row-label">Ordre</span><span class="info-row-value">{{ $extension->sort_order }}</span></div>
        <div class="info-row"><span class="info-row-label">Créée le</span><span class="info-row-value">{{ $extension->created_at->format('d/m/Y') }}</span></div>
      </div>
    </div>

    <div class="info-card">
      <div class="info-card-header"><i class="fas fa-bolt"></i><h3>Actions rapides</h3></div>
      <div class="info-card-body" style="display:flex;flex-direction:column;gap:8px;">
        <a href="{{ route('superadmin.extensions.edit', $extension) }}" class="btn btn-secondary" style="justify-content:flex-start;">
          <i class="fas fa-pen"></i> Modifier l'extension
        </a>
        <button class="btn btn-secondary" style="justify-content:flex-start;" onclick="toggleFeatured({{ $extension->id }})">
          <i class="fas fa-star" style="color:{{ $extension->is_featured ? '#f59e0b' : 'var(--c-ink-40)' }};"></i>
          {{ $extension->is_featured ? 'Retirer de la vedette' : 'Mettre en vedette' }}
        </button>
        <button class="btn btn-secondary" style="justify-content:flex-start;color:var(--c-danger);border-color:var(--c-danger-lt);" onclick="deleteExt({{ $extension->id }}, '{{ addslashes($extension->name) }}')">
          <i class="fas fa-trash"></i> Supprimer
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Suspend modal --}}
<div class="modal-overlay" id="suspendModal">
  <div class="modal modal-sm">
    <div class="modal-header"><div class="modal-title">Suspendre l'activation</div><button class="modal-close" data-modal-close>&times;</button></div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Raison <span class="required">*</span></label>
        <textarea id="suspendReason" class="form-control" rows="3" placeholder="Raison de la suspension…"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Annuler</button>
      <button class="btn btn-danger" id="confirmSuspend">Suspendre</button>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
let _suspendActId = null;

async function toggleFeatured(id) {
  const { ok, data } = await Http.post(`/superadmin/extensions/${id}/featured`, {});
  if (ok) { Toast.success('Mis à jour', data.message); setTimeout(() => location.reload(), 800); }
  else Toast.error('Erreur', data.message);
}

async function toggleStatus(id, status) {
  const { ok, data } = await Http.post(`/superadmin/extensions/${id}/status`, {});
  if (ok) { Toast.success('Statut mis à jour', data.message); setTimeout(() => location.reload(), 800); }
  else Toast.error('Erreur', data.message);
}

async function deleteExt(id, name) {
  Modal.confirm({
    title: `Supprimer « ${name} » ?`,
    message: 'Impossible si des tenants ont cette extension active.',
    confirmText: 'Supprimer',
    type: 'danger',
    onConfirm: async () => {
      const { ok, data } = await Http.delete(`/superadmin/extensions/${id}`);
      if (ok) { Toast.success('Supprimée', data.message); setTimeout(() => window.location.href = '{{ route("superadmin.extensions.index") }}', 900); }
      else Toast.error('Erreur', data.message);
    }
  });
}

function suspendTenantAct(id) {
  _suspendActId = id;
  document.getElementById('suspendReason').value = '';
  Modal.open(document.getElementById('suspendModal'));
}

document.getElementById('confirmSuspend').addEventListener('click', async () => {
  const reason = document.getElementById('suspendReason').value.trim();
  if (!reason) { Toast.warning('Requis', 'Veuillez saisir une raison.'); return; }
  const { ok, data } = await Http.post(`/superadmin/extensions/activations/${_suspendActId}/suspend`, { reason });
  Modal.close(document.getElementById('suspendModal'));
  if (ok) { Toast.success('Suspendue', data.message); setTimeout(() => location.reload(), 900); }
  else Toast.error('Erreur', data.message);
});

async function restoreTenantAct(id) {
  const { ok, data } = await Http.post(`/superadmin/extensions/activations/${id}/restore`, {});
  if (ok) { Toast.success('Restaurée', data.message); setTimeout(() => location.reload(), 900); }
  else Toast.error('Erreur', data.message);
}
</script>
@endpush