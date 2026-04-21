@extends('layouts.global')

@section('title', 'Modifier — ' . $user->name)

@section('breadcrumb')
  <a href="{{ route('users.index') }}">Équipe</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <a href="{{ route('users.show', $user) }}">{{ $user->name }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Modifier</span>
@endsection

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Modifier le membre</h1>
    <p>{{ $user->name }} · {{ $user->email }}</p>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('users.show', $user) }}" class="btn btn-secondary">
      <i class="fas fa-arrow-left"></i> Retour
    </a>
  </div>
</div>

{{-- Owner warning --}}
@if($user->is_tenant_owner)
<div style="background:var(--c-warning-lt);border:1px solid #fcd34d;border-radius:var(--r-md);padding:12px 16px;margin-bottom:20px;font-size:13px;color:#92400e;display:flex;gap:10px;align-items:center;">
  <i class="fas fa-crown" style="font-size:16px;"></i>
  <span>Ce membre est le <strong>propriétaire du compte</strong>. Son rôle ne peut pas être modifié.</span>
</div>
@endif

<form id="userForm" action="{{ route('users.update', $user) }}" method="POST">
  @csrf
  @method('PUT')

  <div class="row" style="align-items:flex-start;">

    <div class="col-8" style="padding:0 12px 0 0;">

      {{-- Infos générales --}}
      <div class="form-section">
        <h3 class="form-section-title">
          <i class="fas fa-user"></i> Informations personnelles
        </h3>
        <div class="row">
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Nom complet <span class="required">*</span></label>
              <div class="input-group">
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Email <span class="required">*</span></label>
              <div class="input-group">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Téléphone</label>
              <div class="input-group">
                <i class="fas fa-phone input-icon"></i>
                <input type="tel" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" placeholder="+33 6 12 34 56 78">
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Titre / Fonction</label>
              <div class="input-group">
                <i class="fas fa-briefcase input-icon"></i>
                <input type="text" name="job_title" class="form-control" value="{{ old('job_title', $user->job_title) }}" placeholder="Responsable commercial">
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Département</label>
              <div class="input-group">
                <i class="fas fa-building input-icon"></i>
                <input type="text" name="department" class="form-control" value="{{ old('department', $user->department) }}" placeholder="Commercial, Finance…">
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Avatar --}}
      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-image"></i> Photo de profil</h3>
        <div style="display:flex;align-items:center;gap:20px;">
          @if($user->avatar)
            <img src="{{ asset('storage/'.$user->avatar) }}" style="width:64px;height:64px;border-radius:var(--r-md);object-fit:cover;border:1px solid var(--c-ink-05);">
          @else
            @php
              $colors = ['#2563eb','#7c3aed','#0891b2','#059669','#d97706'];
              $c = $colors[ord($user->name[0]??'A') % count($colors)];
            @endphp
            <div style="width:64px;height:64px;border-radius:var(--r-md);background:{{ $c }};display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;font-weight:700;">
              {{ strtoupper(substr($user->name,0,2)) }}
            </div>
          @endif
          <div>
            <input type="file" id="avatarInput" accept="image/*" style="display:none" onchange="uploadAvatar(this)">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('avatarInput').click()">
              <i class="fas fa-upload"></i> Changer la photo
            </button>
            <div style="font-size:12px;color:var(--c-ink-40);margin-top:6px;">JPG, PNG, GIF · max 2 Mo</div>
          </div>
        </div>
      </div>

    </div>

    {{-- Sidebar --}}
    <div class="col-4" style="padding:0 0 0 12px;">

      {{-- Rôle --}}
      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title"><i class="fas fa-shield"></i> Rôle & Accès</h3>
        <div class="form-group">
          <label class="form-label">Rôle dans l'organisation <span class="required">*</span></label>
          <select name="role_in_tenant" class="form-control" {{ $user->is_tenant_owner ? 'disabled' : '' }}>
            @foreach($roles as $key => $label)
              <option value="{{ $key }}" {{ old('role_in_tenant', $user->role_in_tenant) === $key ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
          @if($user->is_tenant_owner)
            <input type="hidden" name="role_in_tenant" value="{{ $user->role_in_tenant }}">
          @endif
        </div>
        <div class="form-group">
          <label class="form-label">Statut</label>
          <select name="status" class="form-control" {{ $user->is_tenant_owner ? 'disabled' : '' }}>
            @foreach($statuses as $key => $label)
              <option value="{{ $key }}" {{ old('status', $user->status) === $key ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
          @if($user->is_tenant_owner)
            <input type="hidden" name="status" value="{{ $user->status }}">
          @endif
        </div>
      </div>

      {{-- Méta --}}
      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title"><i class="fas fa-clock"></i> Activité</h3>
        <div class="info-row">
          <span class="info-row-label">Membre depuis</span>
          <span class="info-row-value">{{ $user->created_at->format('d/m/Y') }}</span>
        </div>
        <div class="info-row">
          <span class="info-row-label">Dernière connexion</span>
          <span class="info-row-value">{{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : '—' }}</span>
        </div>
        <div class="info-row">
          <span class="info-row-label">Type</span>
          <span class="info-row-value">{{ $user->is_tenant_owner ? 'Propriétaire' : 'Membre invité' }}</span>
        </div>
      </div>

      {{-- Actions --}}
      <div class="form-section">
        <div style="display:flex;flex-direction:column;gap:10px;">
          <button type="submit" class="btn btn-primary" id="submitBtn" style="justify-content:center;">
            <i class="fas fa-check"></i> Enregistrer les modifications
          </button>
          <a href="{{ route('users.show', $user) }}" class="btn btn-secondary" style="justify-content:center;">
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
ajaxForm('userForm', {
  onSuccess: (data) => {
    Toast.success('Membre mis à jour !', 'Les modifications ont été enregistrées.');
  }
});

async function uploadAvatar(input) {
  const file = input.files[0];
  if (!file) return;
  const fd = new FormData();
  fd.append('avatar', file);
  fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
  const { ok, data } = await Http.post('{{ route("users.avatar", $user) }}', fd);
  if (ok) {
    Toast.success('Avatar mis à jour !');
    setTimeout(() => location.reload(), 800);
  } else {
    Toast.error('Erreur', data.message || 'Impossible de mettre à jour l\'avatar.');
  }
}
</script>
@endpush