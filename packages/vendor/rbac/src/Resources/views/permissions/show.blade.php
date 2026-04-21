@extends('layouts.global')

@section('title', $role->label ?? $role->name)

@section('breadcrumb')
  <a href="{{ route('rbac.roles.index') }}">Rôles & Permissions</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $role->label ?? $role->name }}</span>
@endsection

@section('content')

@php
  $color      = $role->color ?? '#64748b';
  $rolePerms  = $role->permissions->pluck('name')->toArray();
  $isSystem   = (bool) $role->is_system;
@endphp

<div class="page-header">
  <div class="page-header-left" style="display:flex;align-items:center;gap:16px;">
    <div style="width:52px;height:52px;border-radius:var(--r-md);background:{{ $color }}22;border:2px solid {{ $color }}44;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
      <i class="fas fa-shield-halved" style="color:{{ $color }};font-size:22px;"></i>
    </div>
    <div>
      <h1 style="margin-bottom:6px;">
        {{ $role->label ?? $role->name }}
        @if($isSystem)
          <span style="font-size:11px;background:#f3e8ff;color:#7c3aed;padding:3px 9px;border-radius:99px;margin-left:8px;font-weight:600;vertical-align:middle;">
            <i class="fas fa-lock"></i> Système
          </span>
        @endif
      </h1>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <span style="background:{{ $color }}18;color:{{ $color }};border:1px solid {{ $color }}30;padding:3px 10px;border-radius:99px;font-size:11.5px;font-weight:600;">
          {{ $role->permissions->count() }} permission(s)
        </span>
        <span style="background:var(--c-accent-lt);color:var(--c-accent);padding:3px 10px;border-radius:99px;font-size:11.5px;font-weight:600;">
          {{ $users->count() }} membre(s)
        </span>
        @if($role->description)
          <span style="font-size:12.5px;color:var(--c-ink-40);">{{ $role->description }}</span>
        @endif
      </div>
    </div>
  </div>
  <div class="page-header-actions">
    @if(!$isSystem)
    <a href="{{ route('rbac.roles.edit', $role) }}" class="btn btn-primary">
      <i class="fas fa-pen"></i> Modifier
    </a>
    @endif
    <div class="dropdown">
      <button class="btn btn-secondary" data-dropdown-toggle>
        <i class="fas fa-ellipsis"></i>
      </button>
      <div class="dropdown-menu">
        @if(!$isSystem)
          <a href="{{ route('rbac.roles.edit', $role) }}" class="dropdown-item"><i class="fas fa-pen"></i> Modifier</a>
          <div class="dropdown-divider"></div>
          <button class="dropdown-item danger" onclick="deleteRole()"><i class="fas fa-trash"></i> Supprimer</button>
        @else
          <span class="dropdown-item" style="color:var(--c-ink-40);cursor:default;"><i class="fas fa-lock"></i> Rôle système non modifiable</span>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="row" style="align-items:flex-start;">

  {{-- Matrice des permissions --}}
  <div class="col-8" style="padding:0 12px 0 0;">

    {{-- Barre d'action rapide pour sync permissions --}}
    @if(!$isSystem)
    <div style="background:var(--surface-0);border:1px solid var(--c-ink-05);border-radius:var(--r-xl);padding:16px 20px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
      <div style="font-size:13.5px;color:var(--c-ink-60);">
        <i class="fas fa-circle-info" style="color:var(--c-accent);margin-right:6px;"></i>
        Modifiez les permissions directement ici, elles sont sauvegardées instantanément.
      </div>
      <button type="button" class="btn btn-primary btn-sm" id="syncBtn" onclick="syncPermissions()" disabled>
        <i class="fas fa-rotate"></i> Sauvegarder les changements
      </button>
    </div>
    @endif

    {{-- Matrice --}}
    @foreach($permissionsGrouped as $groupKey => $group)
    @php
      $groupActive = collect($group['permissions'])->filter(fn($p) => in_array($p->name, $rolePerms))->count();
      $groupTotal  = count($group['permissions']);
    @endphp
    <div class="form-section" style="margin-bottom:16px;padding:0;overflow:hidden;">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px;background:var(--surface-1);border-bottom:1px solid var(--c-ink-05);">
        <div style="display:flex;align-items:center;gap:10px;">
          <div style="width:34px;height:34px;background:{{ $groupActive === $groupTotal ? 'var(--c-success-lt)' : ($groupActive > 0 ? 'var(--c-warning-lt)' : 'var(--c-ink-02)') }};border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;transition:background .2s;" id="groupIcon-{{ $groupKey }}">
            <i class="fas {{ $group['icon'] }}" style="color:{{ $groupActive === $groupTotal ? 'var(--c-success)' : ($groupActive > 0 ? 'var(--c-warning)' : 'var(--c-ink-20)') }};font-size:14px;transition:color .2s;" id="groupIconColor-{{ $groupKey }}"></i>
          </div>
          <div>
            <div style="font-weight:var(--fw-semi);font-size:14px;color:var(--c-ink);">{{ $group['label'] }}</div>
            <div style="font-size:12px;color:var(--c-ink-40);">
              <span id="grpCount-{{ $groupKey }}">{{ $groupActive }}</span>/{{ $groupTotal }} activée(s)
            </div>
          </div>
        </div>
        @if(!$isSystem)
        <div style="display:flex;gap:8px;">
          <button type="button" class="btn btn-ghost btn-sm" onclick="toggleGroupShow('{{ $groupKey }}', true)">Tout activer</button>
          <button type="button" class="btn btn-ghost btn-sm" onclick="toggleGroupShow('{{ $groupKey }}', false)">Tout désactiver</button>
        </div>
        @endif
      </div>

      <div>
        @foreach($group['permissions'] as $permission)
        @php $active = in_array($permission->name, $rolePerms); @endphp
        <div style="display:flex;align-items:center;justify-content:space-between;padding:13px 24px;border-bottom:1px solid var(--c-ink-05);" class="perm-show-row" data-group="{{ $groupKey }}">
          <div style="display:flex;align-items:center;gap:12px;">
            <i class="fas {{ $active ? 'fa-circle-check' : 'fa-circle-xmark' }}"
               style="color:{{ $active ? 'var(--c-success)' : 'var(--c-ink-10)' }};font-size:16px;transition:color .2s;width:16px;"
               id="permIcon_{{ str_replace('.','_',$permission->name) }}"></i>
            <div>
              <div style="font-size:13.5px;font-weight:var(--fw-medium);color:var(--c-ink);">
                {{ config("rbac.permission_groups.{$groupKey}.permissions.{$permission->name}", $permission->name) }}
              </div>
              <div style="font-size:11.5px;color:var(--c-ink-40);font-family:monospace;">{{ $permission->name }}</div>
            </div>
          </div>
          @if(!$isSystem)
          <label style="position:relative;width:44px;height:24px;flex-shrink:0;cursor:pointer;">
            <input type="checkbox"
                   class="show-perm-checkbox" data-group="{{ $groupKey }}"
                   data-perm="{{ $permission->name }}"
                   id="showPerm_{{ str_replace('.','_',$permission->name) }}"
                   {{ $active ? 'checked' : '' }}
                   onchange="onShowPermChange(this)"
                   style="position:absolute;opacity:0;width:100%;height:100%;cursor:pointer;margin:0;z-index:1;">
            <div class="show-toggle-track" id="track_{{ str_replace('.','_',$permission->name) }}"
                 style="position:absolute;inset:0;border-radius:12px;transition:background .2s;background:{{ $active ? 'var(--c-success)' : 'var(--c-ink-10)' }};">
              <div class="show-toggle-knob" style="position:absolute;width:18px;height:18px;background:#fff;border-radius:50%;top:3px;transition:transform .2s;box-shadow:var(--shadow-sm);transform:translateX({{ $active ? '20px' : '3px' }});">
              </div>
            </div>
          </label>
          @else
          {{-- Lecture seule --}}
          <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--c-ink-40);">
            {{ $active ? 'Autorisé' : 'Refusé' }}
          </div>
          @endif
        </div>
        @endforeach
      </div>
    </div>
    @endforeach
  </div>

  {{-- Sidebar --}}
  <div class="col-4" style="padding:0 0 0 12px;">

    {{-- Info rôle --}}
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header">
        <i class="fas fa-circle-info"></i>
        <h3>Informations</h3>
      </div>
      <div class="info-card-body">
        <div class="info-row">
          <span class="info-row-label">Slug interne</span>
          <span class="info-row-value" style="font-family:monospace;font-size:12px;">{{ $role->name }}</span>
        </div>
        <div class="info-row">
          <span class="info-row-label">Couleur</span>
          <span class="info-row-value" style="display:flex;align-items:center;gap:8px;">
            <span style="width:14px;height:14px;border-radius:50%;background:{{ $color }};display:inline-block;"></span>
            <code style="font-size:12px;">{{ $color }}</code>
          </span>
        </div>
        <div class="info-row">
          <span class="info-row-label">Type</span>
          <span class="info-row-value">{{ $isSystem ? 'Système' : 'Personnalisé' }}</span>
        </div>
        <div class="info-row">
          <span class="info-row-label">Statut</span>
          <span class="badge badge-{{ $role->is_active ?? true ? 'actif' : 'inactif' }}">{{ $role->is_active ?? true ? 'Actif' : 'Inactif' }}</span>
        </div>
        <div class="info-row">
          <span class="info-row-label">Créé le</span>
          <span class="info-row-value">{{ $role->created_at->format('d/m/Y') }}</span>
        </div>
      </div>
    </div>

    {{-- Membres avec ce rôle --}}
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header">
        <i class="fas fa-users"></i>
        <h3>Membres ({{ $users->count() }})</h3>
        @if($users->count() > 0)
          <a href="{{ route('users.index') }}?role={{ $role->name }}" class="btn btn-ghost btn-sm" style="margin-left:auto;">
            Voir tous
          </a>
        @endif
      </div>
      <div class="info-card-body" style="padding:0;">
        @forelse($users->take(5) as $u)
        @php
          $colors = ['#2563eb','#7c3aed','#0891b2','#059669','#d97706'];
          $uc = $colors[ord($u->name[0]??'A') % count($colors)];
        @endphp
        <div style="display:flex;align-items:center;gap:10px;padding:11px 20px;border-bottom:1px solid var(--c-ink-05);">
          @if($u->avatar)
            <img src="{{ asset('storage/'.$u->avatar) }}" style="width:32px;height:32px;border-radius:var(--r-xs);object-fit:cover;">
          @else
            <div style="width:32px;height:32px;border-radius:var(--r-xs);background:{{ $uc }};color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;">
              {{ strtoupper(substr($u->name,0,2)) }}
            </div>
          @endif
          <div style="flex:1;min-width:0;">
            <div style="font-size:13px;font-weight:var(--fw-medium);color:var(--c-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $u->name }}</div>
            <div style="font-size:11.5px;color:var(--c-ink-40);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $u->email }}</div>
          </div>
          <a href="{{ route('users.show', $u) }}" class="btn-icon btn-sm" title="Voir"><i class="fas fa-arrow-right" style="font-size:10px;"></i></a>
        </div>
        @empty
        <div style="padding:20px;text-align:center;color:var(--c-ink-40);font-size:13px;">
          <i class="fas fa-users" style="font-size:20px;margin-bottom:8px;display:block;opacity:.3;"></i>
          Aucun membre avec ce rôle
        </div>
        @endforelse
        @if($users->count() > 5)
          <div style="padding:10px 20px;text-align:center;font-size:12.5px;color:var(--c-ink-40);">
            + {{ $users->count() - 5 }} autre(s) membre(s)
          </div>
        @endif
      </div>
    </div>

    @if(!$isSystem)
    {{-- Actions rapides --}}
    <div class="info-card">
      <div class="info-card-header"><i class="fas fa-bolt"></i><h3>Actions</h3></div>
      <div class="info-card-body" style="display:flex;flex-direction:column;gap:8px;">
        <a href="{{ route('rbac.roles.edit', $role) }}" class="btn btn-secondary" style="justify-content:flex-start;">
          <i class="fas fa-pen"></i> Modifier le rôle
        </a>
        <button class="btn btn-secondary" style="justify-content:flex-start;color:var(--c-danger);border-color:var(--c-danger-lt);" onclick="deleteRole()">
          <i class="fas fa-trash"></i> Supprimer ce rôle
        </button>
      </div>
    </div>
    @endif

  </div>
</div>

@endsection

@push('scripts')
<script>
let changedPerms = new Set();

function onShowPermChange(cb) {
  const permName  = cb.dataset.perm;
  const groupKey  = cb.dataset.group;
  const trackId   = `track_${permName.replace(/\./g,'_')}`;
  const iconId    = `permIcon_${permName.replace(/\./g,'_')}`;
  const track     = document.getElementById(trackId);
  const icon      = document.getElementById(iconId);
  const knob      = track?.querySelector('.show-toggle-knob');

  // Update UI
  if (track) track.style.background = cb.checked ? 'var(--c-success)' : 'var(--c-ink-10)';
  if (knob)  knob.style.transform   = cb.checked ? 'translateX(20px)' : 'translateX(3px)';
  if (icon) {
    icon.className = `fas ${cb.checked ? 'fa-circle-check' : 'fa-circle-xmark'}`;
    icon.style.color = cb.checked ? 'var(--c-success)' : 'var(--c-ink-10)';
  }

  changedPerms.add(permName);
  updateGroupIconShow(groupKey);

  // Activer le bouton sync
  const syncBtn = document.getElementById('syncBtn');
  if (syncBtn) {
    syncBtn.disabled = false;
    syncBtn.classList.add('btn-success');
    syncBtn.classList.remove('btn-primary');
  }
}

function toggleGroupShow(groupKey, state) {
  document.querySelectorAll(`.show-perm-checkbox[data-group="${groupKey}"]`).forEach(cb => {
    cb.checked = state;
    onShowPermChange(cb);
  });
}

function updateGroupIconShow(groupKey) {
  const checkboxes = document.querySelectorAll(`.show-perm-checkbox[data-group="${groupKey}"]`);
  const total    = checkboxes.length;
  const checked  = [...checkboxes].filter(cb => cb.checked).length;
  const grpCount = document.getElementById(`grpCount-${groupKey}`);
  if (grpCount)  grpCount.textContent = checked;
  const icon     = document.getElementById(`groupIconColor-${groupKey}`);
  const iconWrap = document.getElementById(`groupIcon-${groupKey}`);
  if (icon && iconWrap) {
    if (checked === total) {
      iconWrap.style.background = 'var(--c-success-lt)';
      icon.style.color = 'var(--c-success)';
    } else if (checked > 0) {
      iconWrap.style.background = 'var(--c-warning-lt)';
      icon.style.color = 'var(--c-warning)';
    } else {
      iconWrap.style.background = 'var(--c-ink-02)';
      icon.style.color = 'var(--c-ink-20)';
    }
  }
}

async function syncPermissions() {
  const syncBtn = document.getElementById('syncBtn');
  if (syncBtn) CrmForm.setLoading(syncBtn, true);

  const permissions = [...document.querySelectorAll('.show-perm-checkbox:checked')].map(cb => cb.dataset.perm);

  const { ok, data } = await Http.post('{{ route("rbac.roles.sync", $role) }}', { permissions });

  if (syncBtn) CrmForm.setLoading(syncBtn, false);

  if (ok) {
    changedPerms.clear();
    syncBtn.disabled = true;
    syncBtn.classList.remove('btn-success');
    syncBtn.classList.add('btn-primary');
    Toast.success('Permissions sauvegardées !', `${data.count} permission(s) active(s) sur ce rôle.`);
  } else {
    Toast.error('Erreur', data.message || 'Impossible de sauvegarder.');
  }
}

async function deleteRole() {
  Modal.confirm({
    title: 'Supprimer « {{ addslashes($role->label ?? $role->name) }} » ?',
    message: 'Ce rôle sera retiré de tous les membres qui le possèdent.',
    confirmText: 'Supprimer',
    type: 'danger',
    onConfirm: async () => {
      const { ok, data } = await Http.delete('{{ route("rbac.roles.destroy", $role) }}');
      if (ok) {
        Toast.success('Supprimé', data.message);
        setTimeout(() => window.location.href = '{{ route("rbac.roles.index") }}', 900);
      } else {
        Toast.error('Erreur', data.message);
      }
    }
  });
}

// Initialiser les icônes de groupe au chargement
document.addEventListener('DOMContentLoaded', () => {
  @foreach($permissionsGrouped as $groupKey => $group)
  updateGroupIconShow('{{ $groupKey }}');
  @endforeach
});
</script>
@endpush