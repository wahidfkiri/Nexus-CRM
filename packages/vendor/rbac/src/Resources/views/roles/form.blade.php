@extends('layouts.global')

@section('title', isset($role) ? 'Modifier — '.$role->label : 'Nouveau rôle')

@section('breadcrumb')
  <a href="{{ route('rbac.roles.index') }}">Rôles & Permissions</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ isset($role) ? 'Modifier' : 'Nouveau rôle' }}</span>
@endsection

@section('content')

@php
  $isEdit       = isset($role);
  $formAction   = $isEdit ? route('rbac.roles.update', $role) : route('rbac.roles.store');
  $formMethod   = $isEdit ? 'PUT' : 'POST';
  $activePerms  = $isEdit ? $role->permissions->pluck('name')->toArray() : [];
  $systemColors = ['#2563eb','#7c3aed','#0891b2','#059669','#d97706','#dc2626','#db2777','#0f172a'];
@endphp

<div class="page-header">
  <div class="page-header-left">
    <h1>{{ $isEdit ? 'Modifier le rôle' : 'Nouveau rôle' }}</h1>
    <p>{{ $isEdit ? ($role->label ?? $role->name) : 'Définissez un rôle et ses droits d\'accès' }}</p>
  </div>
  <a href="{{ $isEdit ? route('rbac.roles.show', $role) : route('rbac.roles.index') }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> Retour
  </a>
</div>

@if($isEdit && $role->is_system)
<div style="background:var(--c-warning-lt);border:1px solid #fcd34d;border-radius:var(--r-md);padding:12px 16px;margin-bottom:20px;font-size:13px;color:#92400e;display:flex;gap:10px;align-items:center;">
  <i class="fas fa-lock"></i>
  <span>Ce rôle est un <strong>rôle système</strong>. Seules les permissions peuvent être modifiées.</span>
</div>
@endif

<form id="roleForm" action="{{ $formAction }}" method="POST">
  @csrf
  @if($isEdit) @method('PUT') @endif

  <div class="row" style="align-items:flex-start;">

    {{-- Colonne principale : matrice des permissions --}}
    <div class="col-8" style="padding:0 12px 0 0;">

      {{-- Sélection rapide globale --}}
      <div style="background:var(--surface-0);border:1px solid var(--c-ink-05);border-radius:var(--r-xl);padding:20px 24px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <div>
          <div style="font-weight:var(--fw-semi);font-size:14px;color:var(--c-ink);">Sélection rapide des permissions</div>
          <div style="font-size:12.5px;color:var(--c-ink-40);margin-top:2px;"><span id="checkedCount">0</span> permission(s) sélectionnée(s)</div>
        </div>
        <div style="display:flex;gap:8px;">
          <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAll(true)">
            <i class="fas fa-check-double"></i> Tout sélectionner
          </button>
          <button type="button" class="btn btn-ghost btn-sm" onclick="toggleAll(false)">
            <i class="fas fa-times"></i> Tout désélectionner
          </button>
        </div>
      </div>

      {{-- Matrice des permissions par groupe --}}
      @foreach($permissionsGrouped as $groupKey => $group)
      <div class="form-section" style="margin-bottom:16px;padding:0;overflow:hidden;">
        {{-- En-tête du groupe --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px;background:var(--surface-1);border-bottom:1px solid var(--c-ink-05);cursor:pointer;"
             onclick="toggleGroup('{{ $groupKey }}')">
          <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:34px;height:34px;background:var(--c-accent-lt);border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;">
              <i class="fas {{ $group['icon'] }}" style="color:var(--c-accent);font-size:14px;"></i>
            </div>
            <div>
              <div style="font-weight:var(--fw-semi);font-size:14px;color:var(--c-ink);">{{ $group['label'] }}</div>
              <div style="font-size:12px;color:var(--c-ink-40);">
                <span id="group-count-{{ $groupKey }}">0</span> / {{ count($group['permissions']) }} permission(s) activée(s)
              </div>
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:10px;">
            {{-- Toggle groupe --}}
            <button type="button" class="btn btn-ghost btn-sm" onclick="event.stopPropagation();toggleGroup('{{ $groupKey }}', true)">
              Tout activer
            </button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="event.stopPropagation();toggleGroup('{{ $groupKey }}', false)">
              Tout désactiver
            </button>
            <i class="fas fa-chevron-down" id="chevron-{{ $groupKey }}" style="color:var(--c-ink-20);font-size:12px;transition:transform .2s;"></i>
          </div>
        </div>

        {{-- Liste des permissions --}}
        <div id="group-{{ $groupKey }}" style="padding:8px 0;">
          @foreach($group['permissions'] as $permission)
          @php $isChecked = in_array($permission->name, $activePerms); @endphp
          <label style="display:flex;align-items:center;justify-content:space-between;padding:12px 24px;cursor:pointer;transition:background var(--dur-fast);"
                 class="perm-row" data-group="{{ $groupKey }}"
                 onmouseover="this.style.background='var(--c-accent-xl)'"
                 onmouseout="this.style.background=''">
            <div style="display:flex;align-items:center;gap:12px;">
              <div style="width:8px;height:8px;border-radius:50%;background:var(--c-ink-10);flex-shrink:0;" class="perm-dot"></div>
              <div>
                <div style="font-size:13.5px;font-weight:var(--fw-medium);color:var(--c-ink);">
                  {{ config("rbac.permission_groups.{$groupKey}.permissions.{$permission->name}", $permission->name) }}
                </div>
                <div style="font-size:11.5px;color:var(--c-ink-40);font-family:monospace;">{{ $permission->name }}</div>
              </div>
            </div>
            {{-- Toggle switch --}}
            <div style="position:relative;width:44px;height:24px;flex-shrink:0;">
              <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                     class="perm-checkbox" data-group="{{ $groupKey }}"
                     id="perm_{{ str_replace('.','_',$permission->name) }}"
                     {{ $isChecked ? 'checked' : '' }}
                     onchange="onPermChange(this)"
                     style="position:absolute;opacity:0;width:100%;height:100%;cursor:pointer;margin:0;z-index:1;">
              <div class="toggle-track-rbac" style="position:absolute;inset:0;border-radius:12px;transition:background .2s;background:{{ $isChecked ? 'var(--c-accent)' : 'var(--c-ink-10)' }};">
                <div class="toggle-knob-rbac" style="position:absolute;width:18px;height:18px;background:#fff;border-radius:50%;top:3px;transition:transform .2s;box-shadow:var(--shadow-sm);transform:translateX({{ $isChecked ? '20px' : '3px' }});">
                </div>
              </div>
            </div>
          </label>
          @endforeach
        </div>
      </div>
      @endforeach

    </div>

    {{-- Sidebar : infos du rôle --}}
    <div class="col-4" style="padding:0 0 0 12px;">

      {{-- Identité du rôle --}}
      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title"><i class="fas fa-shield"></i> Identité du rôle</h3>

        <div class="form-group">
          <label class="form-label">Nom du rôle <span class="required">*</span></label>
          <div class="input-group">
            <i class="fas fa-shield-halved input-icon"></i>
            <input type="text" name="label" class="form-control"
                   value="{{ old('label', $isEdit ? $role->label : '') }}"
                   placeholder="Ex: Comptable, Commercial…"
                   {{ ($isEdit && $role->is_system) ? 'readonly' : '' }}
                   required>
          </div>
          @if($isEdit)
          <span class="form-hint">Slug interne : <code>{{ $role->name }}</code></span>
          @else
          <span class="form-hint">Le slug sera généré automatiquement.</span>
          @endif
        </div>

        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="2"
                    placeholder="Décrivez les responsabilités de ce rôle…"
                    {{ ($isEdit && $role->is_system) ? 'readonly' : '' }}>{{ old('description', $isEdit ? $role->description : '') }}</textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Couleur d'identification</label>
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
            @foreach($systemColors as $col)
            <label style="cursor:pointer;">
              <input type="radio" name="color" value="{{ $col }}"
                     {{ old('color', $isEdit ? ($role->color ?? '#64748b') : '#2563eb') === $col ? 'checked' : '' }}
                     style="display:none;" class="color-radio" onchange="updateColorPreview('{{ $col }}')">
              <div class="color-swatch" data-color="{{ $col }}"
                   style="width:28px;height:28px;border-radius:50%;background:{{ $col }};cursor:pointer;transition:transform .15s,box-shadow .15s;{{ old('color', $isEdit ? ($role->color ?? '#64748b') : '#2563eb') === $col ? 'box-shadow:0 0 0 3px '.$col.'50,0 0 0 5px '.$col.'30;transform:scale(1.15);' : '' }}"
                   onclick="selectColor('{{ $col }}')">
              </div>
            </label>
            @endforeach
            {{-- Custom color --}}
            <label style="cursor:pointer;position:relative;">
              <input type="color" id="customColor" style="position:absolute;opacity:0;width:28px;height:28px;cursor:pointer;"
                     onchange="selectColor(this.value)">
              <div style="width:28px;height:28px;border-radius:50%;background:conic-gradient(red,orange,yellow,green,blue,violet,red);cursor:pointer;display:flex;align-items:center;justify-content:center;" title="Couleur personnalisée">
                <i class="fas fa-plus" style="font-size:10px;color:#fff;text-shadow:0 0 2px rgba(0,0,0,.5);"></i>
              </div>
            </label>
          </div>
          {{-- Aperçu --}}
          <div style="margin-top:12px;display:flex;align-items:center;gap:10px;">
            <div id="rolePreview" style="display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:99px;font-size:12px;font-weight:600;transition:all .2s;">
              <i class="fas fa-shield-halved" style="font-size:10px;"></i>
              <span id="previewLabel">Aperçu</span>
            </div>
          </div>
        </div>

        @if($isEdit && !$role->is_system)
        <div class="form-group" style="margin-bottom:0;">
          <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;">
            <div>
              <div style="font-size:13.5px;font-weight:var(--fw-medium);">Rôle actif</div>
              <div style="font-size:12px;color:var(--c-ink-40);">Les membres avec ce rôle peuvent se connecter</div>
            </div>
            <label style="position:relative;width:44px;height:24px;">
              <input type="checkbox" name="is_active" value="1" {{ old('is_active', $role->is_active ?? true) ? 'checked' : '' }}
                     style="position:absolute;opacity:0;width:100%;height:100%;cursor:pointer;margin:0;z-index:1;"
                     onchange="document.getElementById('activeTrack').style.background=this.checked?'var(--c-accent)':'var(--c-ink-10)'">
              <div id="activeTrack" style="position:absolute;inset:0;border-radius:12px;transition:background .2s;background:{{ old('is_active', $role->is_active ?? true) ? 'var(--c-accent)' : 'var(--c-ink-10)' }};">
                <div style="position:absolute;width:18px;height:18px;background:#fff;border-radius:50%;top:3px;left:3px;transition:transform .2s;box-shadow:var(--shadow-sm);{{ old('is_active', $role->is_active ?? true) ? 'transform:translateX(20px);' : '' }}"></div>
              </div>
            </label>
          </div>
        </div>
        @endif
      </div>

      {{-- Résumé des permissions sélectionnées --}}
      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title"><i class="fas fa-key"></i> Résumé des permissions</h3>
        <div id="permSummary" style="font-size:13px;color:var(--c-ink-60);">
          <div style="color:var(--c-ink-40);font-style:italic;">Aucune permission sélectionnée</div>
        </div>
      </div>

      {{-- Actions --}}
      <div class="form-section">
        <div style="display:flex;flex-direction:column;gap:10px;">
          <button type="submit" class="btn btn-primary" id="submitBtn" style="justify-content:center;">
            <i class="fas fa-check"></i> {{ $isEdit ? 'Enregistrer les modifications' : 'Créer le rôle' }}
          </button>
          <a href="{{ $isEdit ? route('rbac.roles.show', $role) : route('rbac.roles.index') }}" class="btn btn-secondary" style="justify-content:center;">
            <i class="fas fa-times"></i> Annuler
          </a>
        </div>
      </div>

    </div>
  </div>

</form>

@endsection

@push('scripts')
<script>
/* ── Couleur active au départ ──────────────────────────────────────────── */
let activeColor = '{{ old("color", $isEdit ? ($role->color ?? "#2563eb") : "#2563eb") }}';
updateColorPreview(activeColor);

function selectColor(color) {
  activeColor = color;
  document.querySelectorAll('input[name=color]').forEach(r => r.checked = false);
  document.querySelectorAll('.color-swatch').forEach(s => {
    s.style.boxShadow = '';
    s.style.transform = '';
  });
  const swatch = document.querySelector(`.color-swatch[data-color="${color}"]`);
  if (swatch) {
    swatch.style.boxShadow = `0 0 0 3px ${color}50,0 0 0 5px ${color}30`;
    swatch.style.transform = 'scale(1.15)';
  }
  updateColorPreview(color);

  // Injecter un champ hidden si la couleur n'est pas un radio
  let existing = document.getElementById('colorHidden');
  if (!existing) {
    existing = document.createElement('input');
    existing.type = 'hidden';
    existing.name = 'color';
    existing.id = 'colorHidden';
    document.getElementById('roleForm').appendChild(existing);
  }
  existing.value = color;
}

function updateColorPreview(color) {
  const preview = document.getElementById('rolePreview');
  const label   = document.getElementById('previewLabel');
  const nameInput = document.querySelector('input[name=label]');
  if (preview) {
    preview.style.background = color + '22';
    preview.style.color      = color;
    preview.style.border     = `1px solid ${color}44`;
  }
  if (label && nameInput) {
    label.textContent = nameInput.value || 'Aperçu';
  }
}

document.querySelector('input[name=label]')?.addEventListener('input', function() {
  const lbl = document.getElementById('previewLabel');
  if (lbl) lbl.textContent = this.value || 'Aperçu';
});

/* ── Toggle groupe ─────────────────────────────────────────────────────── */
function toggleGroup(groupKey, forceState = undefined) {
  const container = document.getElementById(`group-${groupKey}`);
  const chevron   = document.getElementById(`chevron-${groupKey}`);

  if (forceState !== undefined) {
    // Activer/désactiver toutes les permissions du groupe
    document.querySelectorAll(`.perm-checkbox[data-group="${groupKey}"]`).forEach(cb => {
      cb.checked = forceState;
      updateToggleUI(cb);
    });
    updateGroupCount(groupKey);
    updateGlobalCount();
    updatePermSummary();
    return;
  }

  // Toggle visibilité
  const isHidden = container.style.display === 'none';
  container.style.display = isHidden ? '' : 'none';
  if (chevron) chevron.style.transform = isHidden ? '' : 'rotate(-90deg)';
}

/* ── Toggle all ────────────────────────────────────────────────────────── */
function toggleAll(state) {
  document.querySelectorAll('.perm-checkbox').forEach(cb => {
    cb.checked = state;
    updateToggleUI(cb);
  });
  document.querySelectorAll('[id^="group-count-"]').forEach(el => {
    const groupKey = el.id.replace('group-count-','');
    const total = document.querySelectorAll(`.perm-checkbox[data-group="${groupKey}"]`).length;
    el.textContent = state ? total : 0;
  });
  updateGlobalCount();
  updatePermSummary();
}

/* ── On permission change ──────────────────────────────────────────────── */
function onPermChange(cb) {
  updateToggleUI(cb);
  updateGroupCount(cb.dataset.group);
  updateGlobalCount();
  updatePermSummary();
}

function updateToggleUI(cb) {
  const track = cb.nextElementSibling;
  const knob  = track?.querySelector('.toggle-knob-rbac');
  const dot   = cb.closest('label')?.querySelector('.perm-dot');
  if (track) track.style.background = cb.checked ? 'var(--c-accent)' : 'var(--c-ink-10)';
  if (knob)  knob.style.transform   = cb.checked ? 'translateX(20px)' : 'translateX(3px)';
  if (dot)   dot.style.background   = cb.checked ? 'var(--c-accent)' : 'var(--c-ink-10)';
}

function updateGroupCount(groupKey) {
  const checked = document.querySelectorAll(`.perm-checkbox[data-group="${groupKey}"]:checked`).length;
  const el = document.getElementById(`group-count-${groupKey}`);
  if (el) el.textContent = checked;
}

function updateGlobalCount() {
  const total = document.querySelectorAll('.perm-checkbox:checked').length;
  const el = document.getElementById('checkedCount');
  if (el) el.textContent = total;
}

function updatePermSummary() {
  const summary = document.getElementById('permSummary');
  if (!summary) return;
  const groups = {};
  document.querySelectorAll('.perm-checkbox:checked').forEach(cb => {
    const g = cb.dataset.group;
    if (!groups[g]) groups[g] = 0;
    groups[g]++;
  });
  if (!Object.keys(groups).length) {
    summary.innerHTML = '<div style="color:var(--c-ink-40);font-style:italic;">Aucune permission sélectionnée</div>';
    return;
  }
  const groupDefs = @json(collect($permissionsGrouped)->map(fn($g) => ['label' => $g['label'], 'icon' => $g['icon']]));
  summary.innerHTML = Object.entries(groups).map(([key, count]) => {
    const def = groupDefs[key] || { label: key, icon: 'fa-key' };
    return `<div style="display:flex;align-items:center;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--c-ink-05);font-size:13px;">
      <div style="display:flex;align-items:center;gap:8px;">
        <i class="fas ${def.icon}" style="color:var(--c-accent);width:14px;text-align:center;font-size:12px;"></i>
        ${def.label}
      </div>
      <span style="background:var(--c-success-lt);color:var(--c-success);padding:2px 8px;border-radius:99px;font-size:11px;font-weight:600;">${count}</span>
    </div>`;
  }).join('') + `<div style="padding-top:10px;font-size:12px;color:var(--c-ink-40);">Total : <strong style="color:var(--c-ink);">${Object.values(groups).reduce((a,b)=>a+b,0)}</strong> permission(s)</div>`;
}

/* ── Init au chargement ────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  // Init les compteurs et UIs pour les permissions pré-cochées
  document.querySelectorAll('.perm-checkbox').forEach(cb => updateToggleUI(cb));
  @foreach($permissionsGrouped as $groupKey => $group)
  updateGroupCount('{{ $groupKey }}');
  @endforeach
  updateGlobalCount();
  updatePermSummary();

  // Submit
  ajaxForm('roleForm', {
    onSuccess: (data) => {
      Toast.success('{{ $isEdit ? "Rôle mis à jour !" : "Rôle créé !" }}', data.message);
    }
  });
});
</script>
@endpush