@extends('layouts.global')

@section('title', $user->name)

@section('breadcrumb')
  <a href="{{ route('users.index') }}">Équipe</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $user->name }}</span>
@endsection

@section('content')

@php
  $roleColors = ['owner' => '#7c3aed', 'admin' => '#2563eb', 'manager' => '#0891b2', 'user' => '#059669', 'viewer' => '#64748b'];
  $tenantRole = $user->tenantRole(auth()->user()->tenant_id);
  $roleColor = $roleColors[$user->role_in_tenant] ?? ($tenantRole?->color ?? '#64748b');
  $roleLabel = $tenantRole?->label ?? ($roles[$user->role_in_tenant] ?? $user->role_in_tenant);
  $statusCls = ['active' => 'actif', 'inactive' => 'inactif', 'invited' => 'info', 'suspended' => 'suspendu'][$user->status] ?? 'inactif';
  $statusLabel = config("user.user_statuses.{$user->status}", $user->status);
  $avatarColors = ['#2563eb', '#7c3aed', '#0891b2', '#059669', '#d97706', '#dc2626'];
  $c = $avatarColors[ord($user->name[0] ?? 'A') % count($avatarColors)];
  $permissions = $tenantRole?->permissions?->pluck('name')->all() ?? [];
@endphp

<div class="page-header">
  <div class="page-header-left" style="display:flex;align-items:center;gap:16px;">
    @if($user->avatar)
      <img src="{{ asset('storage/' . $user->avatar) }}" style="width:56px;height:56px;border-radius:var(--r-md);object-fit:cover;">
    @else
      <div style="width:56px;height:56px;border-radius:var(--r-md);background:{{ $c }};color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;flex-shrink:0;">
        {{ strtoupper(substr($user->name, 0, 2)) }}
      </div>
    @endif
    <div>
      <h1 style="margin-bottom:6px;">
        {{ $user->name }}
        @if($user->is_tenant_owner)
          <span style="font-size:12px;background:#fef3c7;color:#92400e;padding:3px 10px;border-radius:99px;margin-left:8px;font-weight:600;vertical-align:middle;">
            <i class="fas fa-crown"></i> OWNER
          </span>
        @endif
      </h1>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <span class="badge badge-{{ $statusCls }}">
          <span class="badge-dot" style="background:currentColor"></span>{{ $statusLabel }}
        </span>
        <span style="background:{{ $roleColor }}18;color:{{ $roleColor }};border:1px solid {{ $roleColor }}30;padding:3px 10px;border-radius:99px;font-size:11.5px;font-weight:600;">
          {{ $roleLabel }}
        </span>
        <span style="font-size:12px;color:var(--c-ink-40);">
          <i class="fas fa-calendar" style="margin-right:4px;"></i>Membre depuis {{ $user->created_at->format('M Y') }}
        </span>
      </div>
    </div>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('users.edit', $user) }}" class="btn btn-primary">
      <i class="fas fa-pen"></i> Modifier
    </a>
    @if(!$user->is_tenant_owner && $user->id !== auth()->id())
      <div class="dropdown">
        <button class="btn btn-secondary" data-dropdown-toggle>
          <i class="fas fa-ellipsis"></i>
        </button>
        <div class="dropdown-menu">
          @if($user->status === 'active')
            <button class="dropdown-item danger" onclick="suspendUser()"><i class="fas fa-ban"></i> Suspendre</button>
          @else
            <button class="dropdown-item" onclick="activateUser()"><i class="fas fa-check-circle"></i> Activer</button>
          @endif
          <div class="dropdown-divider"></div>
          <button class="dropdown-item danger" onclick="deleteUser()"><i class="fas fa-trash"></i> Supprimer</button>
        </div>
      </div>
    @endif
  </div>
</div>

<div class="row" style="align-items:flex-start;">
  <div class="col-8" style="padding:0 12px 0 0;">
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header">
        <i class="fas fa-address-card"></i>
        <h3>Coordonnées</h3>
      </div>
      <div class="info-card-body">
        <div class="info-row">
          <span class="info-row-label"><i class="fas fa-envelope" style="color:var(--c-accent);margin-right:6px;width:14px;text-align:center;"></i>Email</span>
          <span class="info-row-value"><a href="mailto:{{ $user->email }}" style="color:var(--c-accent);text-decoration:none;">{{ $user->email }}</a></span>
        </div>
        @if($user->phone)
          <div class="info-row">
            <span class="info-row-label"><i class="fas fa-phone" style="color:var(--c-accent);margin-right:6px;width:14px;text-align:center;"></i>Téléphone</span>
            <span class="info-row-value"><a href="tel:{{ $user->phone }}" style="color:inherit;text-decoration:none;">{{ $user->phone }}</a></span>
          </div>
        @endif
        @if($user->job_title)
          <div class="info-row">
            <span class="info-row-label"><i class="fas fa-briefcase" style="color:var(--c-accent);margin-right:6px;width:14px;text-align:center;"></i>Fonction</span>
            <span class="info-row-value">{{ $user->job_title }}</span>
          </div>
        @endif
        @if($user->department)
          <div class="info-row">
            <span class="info-row-label"><i class="fas fa-building" style="color:var(--c-accent);margin-right:6px;width:14px;text-align:center;"></i>Département</span>
            <span class="info-row-value">{{ $user->department }}</span>
          </div>
        @endif
      </div>
    </div>

    <div class="info-card">
      <div class="info-card-header">
        <i class="fas fa-shield-halved"></i>
        <h3>Permissions du rôle {{ $roleLabel }}</h3>
      </div>
      <div class="info-card-body">
        @if($user->role_in_tenant === 'owner')
          <div style="display:flex;align-items:center;gap:8px;padding:8px 0;font-size:13px;color:var(--c-success);">
            <i class="fas fa-circle-check"></i> Accès total à toutes les fonctionnalités du tenant
          </div>
        @elseif(count($permissions))
          <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:4px;">
            @foreach($permissions as $permission)
              <span style="background:var(--c-accent-lt);color:var(--c-accent);padding:3px 10px;border-radius:var(--r-full);font-size:12px;font-weight:600;">
                <i class="fas fa-check" style="font-size:10px;margin-right:4px;"></i>{{ $permission }}
              </span>
            @endforeach
          </div>
        @else
          <div style="font-size:13px;color:var(--c-ink-50);">Aucune permission active pour ce rôle.</div>
        @endif
        <div style="margin-top:12px;font-size:12px;color:var(--c-ink-40);">
          <i class="fas fa-circle-info" style="margin-right:4px;"></i>
          Les permissions affichées correspondent au rôle rattaché à cet utilisateur dans ce tenant.
        </div>
      </div>
    </div>
  </div>

  <div class="col-4" style="padding:0 0 0 12px;">
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header">
        <i class="fas fa-chart-bar"></i>
        <h3>Informations du compte</h3>
      </div>
      <div class="info-card-body">
        <div class="info-row">
          <span class="info-row-label">Statut</span>
          <span class="badge badge-{{ $statusCls }}">{{ $statusLabel }}</span>
        </div>
        <div class="info-row">
          <span class="info-row-label">Rôle</span>
          <span class="info-row-value" style="color:{{ $roleColor }};font-weight:var(--fw-semi);">{{ $roleLabel }}</span>
        </div>
        <div class="info-row">
          <span class="info-row-label">Type</span>
          <span class="info-row-value">{{ $user->is_tenant_owner ? 'Propriétaire' : 'Membre invité' }}</span>
        </div>
        <div class="info-row">
          <span class="info-row-label">Créé le</span>
          <span class="info-row-value">{{ $user->created_at->format('d/m/Y') }}</span>
        </div>
        <div class="info-row">
          <span class="info-row-label">Dernière connexion</span>
          <span class="info-row-value">{{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : '—' }}</span>
        </div>
        @if($user->last_login_ip)
          <div class="info-row">
            <span class="info-row-label">Dernière IP</span>
            <span class="info-row-value" style="font-family:monospace;font-size:12px;">{{ $user->last_login_ip }}</span>
          </div>
        @endif
      </div>
    </div>

    @if(!$user->is_tenant_owner && $user->id !== auth()->id())
      <div class="info-card">
        <div class="info-card-header">
          <i class="fas fa-bolt"></i>
          <h3>Actions rapides</h3>
        </div>
        <div class="info-card-body" style="display:flex;flex-direction:column;gap:8px;">
          <a href="mailto:{{ $user->email }}" class="btn btn-secondary" style="justify-content:flex-start;">
            <i class="fas fa-envelope"></i> Envoyer un email
          </a>
          <a href="{{ route('users.edit', $user) }}" class="btn btn-secondary" style="justify-content:flex-start;">
            <i class="fas fa-pen"></i> Modifier le profil
          </a>
          @if($user->status === 'active')
            <button class="btn btn-secondary" style="justify-content:flex-start;color:var(--c-warning);border-color:var(--c-warning-lt);" onclick="suspendUser()">
              <i class="fas fa-ban"></i> Suspendre l'accès
            </button>
          @else
            <button class="btn btn-secondary" style="justify-content:flex-start;color:var(--c-success);border-color:var(--c-success-lt);" onclick="activateUser()">
              <i class="fas fa-check-circle"></i> Activer l'accès
            </button>
          @endif
          <button class="btn btn-secondary" style="justify-content:flex-start;color:var(--c-danger);border-color:var(--c-danger-lt);" onclick="deleteUser()">
            <i class="fas fa-trash"></i> Supprimer
          </button>
        </div>
      </div>
    @endif
  </div>
</div>

@endsection

@push('scripts')
<script>
async function suspendUser() {
  Modal.confirm({
    title: 'Suspendre {{ addslashes($user->name) }} ?',
    message: 'L\'utilisateur n\'aura plus accès à la plateforme.',
    confirmText: 'Suspendre',
    type: 'danger',
    onConfirm: async () => {
      const { ok, data } = await Http.post('{{ route("users.suspend", $user) }}', {});
      if (ok) { Toast.success('Suspendu', data.message); setTimeout(() => location.reload(), 900); }
      else Toast.error('Erreur', data.message);
    }
  });
}

async function activateUser() {
  const { ok, data } = await Http.post('{{ route("users.activate", $user) }}', {});
  if (ok) { Toast.success('Activé', data.message); setTimeout(() => location.reload(), 900); }
  else Toast.error('Erreur', data.message);
}

async function deleteUser() {
  Modal.confirm({
    title: 'Supprimer {{ addslashes($user->name) }} ?',
    message: 'Cette action est irréversible.',
    confirmText: 'Supprimer',
    type: 'danger',
    onConfirm: async () => {
      const { ok, data } = await Http.delete('{{ route("users.destroy", $user) }}');
      if (ok) { Toast.success('Supprimé', data.message); setTimeout(() => window.location.href = '{{ route("users.index") }}', 900); }
      else Toast.error('Erreur', data.message);
    }
  });
}
</script>
@endpush
