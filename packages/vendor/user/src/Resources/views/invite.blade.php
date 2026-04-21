@extends('layouts.global')

@section('title', 'Inviter un membre')

@section('breadcrumb')
  <a href="{{ route('users.index') }}">Équipe</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Inviter un membre</span>
@endsection

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Inviter un membre</h1>
    <p>Un email d'invitation sera envoyé avec un lien d'activation</p>
  </div>
  <a href="{{ route('users.index') }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> Retour
  </a>
</div>

<div class="row" style="max-width:820px;">
  <div class="col-12">
    <form id="inviteForm" action="{{ route('users.store') }}" method="POST">
      @csrf

      <div class="form-section">
        <h3 class="form-section-title">
          <i class="fas fa-envelope"></i> Informations de l'invitation
          <span class="form-section-badge">Étape 1/2</span>
        </h3>

        <div class="form-group">
          <label class="form-label">Adresse email <span class="required">*</span></label>
          <div class="input-group">
            <i class="fas fa-envelope input-icon"></i>
            <input type="email" name="email" class="form-control" placeholder="collaborateur@entreprise.com" autofocus required>
          </div>
          <span class="form-hint">Un email d'invitation sera envoyé à cette adresse.</span>
        </div>

        <div class="form-group">
          <label class="form-label">Rôle <span class="required">*</span></label>
          <div class="row" style="margin-top:8px;">
            @foreach($roles as $key => $label)
            <div class="col-6" style="margin-bottom:10px;">
              <label style="display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border:1.5px solid var(--c-ink-10);border-radius:var(--r-md);cursor:pointer;transition:all var(--dur-fast);"
                     class="role-card" data-role="{{ $key }}">
                <input type="radio" name="role_in_tenant" value="{{ $key }}" style="margin-top:2px;"
                  {{ $key === 'user' ? 'checked' : '' }}>
                <div>
                  <div style="font-weight:var(--fw-medium);color:var(--c-ink);margin-bottom:3px;">{{ $label }}</div>
                  <div style="font-size:12px;color:var(--c-ink-40);">
                    @switch($key)
                      @case('admin')    Accès complet sauf facturation critique @break
                      @case('manager')  Clients, factures et stock @break
                      @case('user')     Consultation et opérations courantes @break
                      @case('viewer')   Lecture seule @break
                      @default          Accès personnalisé @break
                    @endswitch
                  </div>
                </div>
              </label>
            </div>
            @endforeach
          </div>
        </div>
      </div>

      <div class="form-section">
        <h3 class="form-section-title">
          <i class="fas fa-message"></i> Message personnalisé
          <span class="form-section-badge">Étape 2/2 · Optionnel</span>
        </h3>
        <div class="form-group">
          <label class="form-label">Message d'accompagnement <span class="hint">(optionnel)</span></label>
          <textarea name="message" class="form-control" rows="3"
            placeholder="Bonjour, je vous invite à rejoindre notre espace de travail…"></textarea>
          <span class="form-hint">Ce message sera inclus dans l'email d'invitation.</span>
        </div>

        {{-- Aperçu --}}
        <div style="background:var(--c-accent-xl);border:1px solid var(--c-accent-lt);border-radius:var(--r-md);padding:16px 20px;font-size:13px;color:var(--c-ink-60);">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;font-weight:var(--fw-semi);color:var(--c-ink);">
            <i class="fas fa-circle-info" style="color:var(--c-accent);"></i>
            Ce que recevra votre invité
          </div>
          <ul style="margin:0;padding-left:18px;line-height:1.9;">
            <li>Un email avec un lien d'invitation valable <strong>{{ config('user.invitation.expire_days', 7) }} jours</strong></li>
            <li>Un formulaire pour créer son mot de passe</li>
            <li>L'accès immédiat avec le rôle sélectionné</li>
          </ul>
        </div>
      </div>

      <div class="form-actions" style="padding-top:8px;">
        <a href="{{ route('users.index') }}" class="btn btn-secondary">
          <i class="fas fa-times"></i> Annuler
        </a>
        <button type="submit" class="btn btn-primary" id="submitBtn">
          <i class="fas fa-paper-plane"></i> Envoyer l'invitation
        </button>
      </div>
    </form>
  </div>
</div>

@endsection

@push('scripts')
<script>
// Highlighting de la carte rôle sélectionné
document.querySelectorAll('.role-card').forEach(card => {
  const radio = card.querySelector('input[type=radio]');
  function highlight() {
    document.querySelectorAll('.role-card').forEach(c => {
      c.style.borderColor  = 'var(--c-ink-10)';
      c.style.background   = '';
    });
    if (radio.checked) {
      card.style.borderColor = 'var(--c-accent)';
      card.style.background  = 'var(--c-accent-xl)';
    }
  }
  radio.addEventListener('change', () => {
    document.querySelectorAll('input[name=role_in_tenant]').forEach(r =>
      r.closest('.role-card').style.borderColor = 'var(--c-ink-10)'
    );
    highlight();
  });
  if (radio.checked) highlight();
});

ajaxForm('inviteForm', {
  onSuccess: (data) => {
    Toast.success('Invitation envoyée !', data.message, 4000);
  }
});
</script>
@endpush