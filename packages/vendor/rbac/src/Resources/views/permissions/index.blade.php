@extends('layouts.global')

@section('title', 'Permissions')

@section('breadcrumb')
  <a href="{{ route('rbac.roles.index') }}">Rôles & Permissions</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Permissions</span>
@endsection

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Permissions disponibles</h1>
    <p>Référentiel de toutes les permissions du système, organisées par module</p>
  </div>
  <a href="{{ route('rbac.roles.index') }}" class="btn btn-secondary">
    <i class="fas fa-shield-halved"></i> Voir les rôles
  </a>
</div>

{{-- Recherche rapide --}}
<div class="form-section" style="margin-bottom:20px;">
  <div class="table-search" style="max-width:400px;">
    <i class="fas fa-search"></i>
    <input type="text" id="permSearch" placeholder="Rechercher une permission…"
           style="width:100%;padding:10px 12px 10px 36px;border:1.5px solid var(--c-ink-10);border-radius:var(--r-md);background:var(--surface-1);outline:none;font-size:14px;"
           oninput="filterPerms(this.value)">
  </div>
</div>

{{-- Grille des modules --}}
<div class="row">
  @foreach($permissionsGrouped as $groupKey => $group)
  @php $totalGroup = count($group['permissions']); @endphp
  <div class="col-6" style="margin-bottom:20px;" data-group-block="{{ $groupKey }}">
    <div class="info-card" style="height:100%;">
      <div class="info-card-header" style="justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:10px;">
          <div style="width:34px;height:34px;background:var(--c-accent-lt);border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;">
            <i class="fas {{ $group['icon'] }}" style="color:var(--c-accent);font-size:14px;"></i>
          </div>
          <div>
            <h3 style="margin:0;">{{ $group['label'] }}</h3>
          </div>
        </div>
        <span style="background:var(--c-success-lt);color:var(--c-success);padding:3px 10px;border-radius:99px;font-size:11px;font-weight:600;">
          {{ $totalGroup }} permission(s)
        </span>
      </div>
      <div class="info-card-body" style="padding:0;">
        @foreach($group['permissions'] as $permission)
        <div class="perm-item" data-name="{{ $permission->name }}" data-label="{{ strtolower(config("rbac.permission_groups.{$groupKey}.permissions.{$permission->name}", $permission->name)) }}"
             style="display:flex;align-items:center;justify-content:space-between;padding:11px 20px;border-bottom:1px solid var(--c-ink-05);transition:background var(--dur-fast);"
             onmouseover="this.style.background='var(--c-accent-xl)'" onmouseout="this.style.background=''">
          <div style="display:flex;align-items:center;gap:10px;">
            <i class="fas fa-key" style="color:var(--c-ink-20);font-size:12px;width:14px;text-align:center;"></i>
            <div>
              <div style="font-size:13.5px;font-weight:var(--fw-medium);color:var(--c-ink);">
                {{ config("rbac.permission_groups.{$groupKey}.permissions.{$permission->name}", $permission->name) }}
              </div>
              <div style="font-size:11.5px;color:var(--c-ink-40);font-family:monospace;">{{ $permission->name }}</div>
            </div>
          </div>
          {{-- Voir quels rôles ont cette permission --}}
          @php
            $rolesWithPerm = \Spatie\Permission\Models\Role::whereHas('permissions', fn($q) => $q->where('name', $permission->name))
                ->where(function($q) { $q->where('tenant_id', auth()->user()->tenant_id)->orWhereNull('tenant_id'); })
                ->pluck('name')->toArray();
          @endphp
          <div style="display:flex;gap:4px;flex-wrap:wrap;justify-content:flex-end;max-width:180px;">
            @forelse($rolesWithPerm as $r)
            @php $rc = ['owner'=>'#7c3aed','admin'=>'#2563eb','manager'=>'#0891b2','user'=>'#059669','viewer'=>'#64748b'][$r] ?? '#64748b'; @endphp
            <span style="background:{{ $rc }}18;color:{{ $rc }};padding:2px 7px;border-radius:99px;font-size:10.5px;font-weight:600;">
              {{ config("user.tenant_roles.{$r}", $r) }}
            </span>
            @empty
            <span style="font-size:11.5px;color:var(--c-ink-20);">Aucun rôle</span>
            @endforelse
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </div>
  @endforeach
</div>

@endsection

@push('scripts')
<script>
function filterPerms(term) {
  const q = term.toLowerCase().trim();
  document.querySelectorAll('.perm-item').forEach(el => {
    const match = !q || el.dataset.name.includes(q) || el.dataset.label.includes(q);
    el.style.display = match ? '' : 'none';
  });
  // Cacher les groupes vides
  document.querySelectorAll('[data-group-block]').forEach(block => {
    const visible = [...block.querySelectorAll('.perm-item')].some(el => el.style.display !== 'none');
    block.style.display = visible ? '' : 'none';
  });
}
</script>
@endpush