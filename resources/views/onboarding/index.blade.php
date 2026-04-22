@extends('layouts.global')

@section('title', 'Configuration du compte')

@section('breadcrumb')
  <span>Configuration</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Assistant de demarrage</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <h1>Configuration de votre espace</h1>
    <p>Terminez les etapes pour activer les modules adaptes a votre activite.</p>
  </div>
</div>

<div class="row">
  <div class="col-12" style="padding:0;">
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-building"></i><h3>1. Informations societe</h3></div>
      <div class="info-card-body">
        <form id="companyForm">
          @csrf
          <div class="row">
            <div class="col-6">
              <label class="form-label">Nom de societe</label>
              <input class="form-control" name="company_name" value="{{ $tenant->name }}" required>
            </div>
            <div class="col-6">
              <label class="form-label">Email societe</label>
              <input class="form-control" type="email" name="company_email" value="{{ $tenant->email }}" required>
            </div>
            <div class="col-6">
              <label class="form-label">Telephone</label>
              <input class="form-control" name="company_phone" value="{{ $tenant->phone }}">
            </div>
            <div class="col-6">
              <label class="form-label">Adresse</label>
              <input class="form-control" name="company_address" value="{{ $tenant->address }}">
            </div>
            <div class="col-6">
              <label class="form-label">Fuseau horaire</label>
              <input class="form-control" name="company_timezone" value="{{ $tenant->timezone ?? 'Europe/Paris' }}" required>
            </div>
            <div class="col-6">
              <label class="form-label">Devise</label>
              <input class="form-control" name="company_currency" value="{{ $tenant->currency ?? 'EUR' }}" required>
            </div>
          </div>
          <div style="margin-top:12px;">
            <button type="button" class="btn btn-primary" onclick="saveCompany()"><i class="fas fa-save"></i> Enregistrer</button>
          </div>
        </form>
      </div>
    </div>

    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-layer-group"></i><h3>2. Secteur d'activite</h3></div>
      <div class="info-card-body">
        <form id="sectorForm">
          @csrf
          <div class="row">
            @foreach($sectors as $key => $label)
              <div class="col-4" style="margin-bottom:8px;">
                <label style="display:flex;align-items:center;gap:8px;padding:10px;border:1px solid var(--c-ink-10);border-radius:8px;cursor:pointer;">
                  <input type="radio" name="sector" value="{{ $key }}" {{ $selectedSector === $key ? 'checked' : '' }}>
                  <span>{{ $label }}</span>
                </label>
              </div>
            @endforeach
          </div>
          <div style="margin-top:12px;">
            <button type="button" class="btn btn-primary" onclick="saveSector()"><i class="fas fa-check"></i> Valider le secteur</button>
          </div>
        </form>
      </div>
    </div>

    <div class="info-card">
      <div class="info-card-header"><i class="fas fa-puzzle-piece"></i><h3>3. Applications a activer</h3></div>
      <div class="info-card-body">
        <form id="appsForm">
          @csrf
          <div class="row">
            @foreach($apps as $app)
              @php
                $checked = in_array($app->slug, $activeSlugs, true) || in_array($app->slug, $recommendedApps, true);
              @endphp
              <div class="col-4" style="margin-bottom:8px;">
                <label style="display:flex;align-items:center;gap:8px;padding:10px;border:1px solid var(--c-ink-10);border-radius:8px;cursor:pointer;">
                  <input type="checkbox" name="apps[]" value="{{ $app->slug }}" {{ $checked ? 'checked' : '' }}>
                  <span><strong>{{ $app->name }}</strong><br><small style="color:var(--c-ink-40)">{{ $app->tagline }}</small></span>
                </label>
              </div>
            @endforeach
          </div>
          <div style="display:flex;gap:10px;margin-top:12px;">
            <button type="button" class="btn btn-secondary" onclick="applyRecommended()"><i class="fas fa-wand-magic-sparkles"></i> Appliquer recommandations</button>
            <button type="button" class="btn btn-primary" onclick="saveApps()"><i class="fas fa-plug"></i> Activer mes apps</button>
            <button type="button" class="btn btn-success" onclick="completeOnboarding()"><i class="fas fa-flag-checkered"></i> Terminer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
const ONBOARDING_ROUTES = {
  company: '{{ route("onboarding.company") }}',
  sector: '{{ route("onboarding.sector") }}',
  apps: '{{ route("onboarding.apps") }}',
  complete: '{{ route("onboarding.complete") }}',
};

let recommendedApps = @json($recommendedApps);

async function saveCompany() {
  const payload = Object.fromEntries(new FormData(document.getElementById('companyForm')).entries());
  const { ok, data } = await Http.post(ONBOARDING_ROUTES.company, payload);
  if (ok) Toast.success('Succes', data.message); else Toast.error('Erreur', data.message || 'Enregistrement impossible');
}

async function saveSector() {
  const payload = Object.fromEntries(new FormData(document.getElementById('sectorForm')).entries());
  const { ok, data } = await Http.post(ONBOARDING_ROUTES.sector, payload);
  if (ok) {
    recommendedApps = data.recommended_apps || [];
    Toast.success('Succes', data.message);
  } else {
    Toast.error('Erreur', data.message || 'Secteur invalide');
  }
}

function applyRecommended() {
  document.querySelectorAll('input[name="apps[]"]').forEach((cb) => {
    cb.checked = recommendedApps.includes(cb.value);
  });
  Toast.success('OK', 'Applications recommandees appliquees.');
}

async function saveApps() {
  const formData = new FormData(document.getElementById('appsForm'));
  const payload = { apps: formData.getAll('apps[]') };
  const { ok, data } = await Http.post(ONBOARDING_ROUTES.apps, payload);
  if (ok) Toast.success('Succes', data.message); else Toast.error('Erreur', data.message || 'Activation impossible');
}

async function completeOnboarding() {
  const { ok, data } = await Http.post(ONBOARDING_ROUTES.complete, {});
  if (!ok) {
    Toast.error('Erreur', data.message || 'Finalisation impossible');
    return;
  }
  Toast.success('Parfait', data.message);
  setTimeout(() => window.location.href = data.redirect || '/dashboard', 900);
}
</script>
@endpush

