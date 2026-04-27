@extends('layouts.global')

@section('title', 'Paramètres globaux')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <h1>Paramètres globaux</h1>
    <p>Configurez l'identité de votre espace CRM, les préférences métier et les options générales.</p>
  </div>
</div>

@if(!$canManageTenant)
  <section class="info-card">
    <div class="info-card-body">
      <div class="form-error">Vous n'avez pas les permissions nécessaires pour modifier les paramètres globaux.</div>
    </div>
  </section>
@else
  <form id="globalSettingsForm"
        data-secure-form="1"
        data-secure-ajax="1"
        action="{{ route('settings.global.update') }}"
        method="POST"
        novalidate>
    @csrf
    @method('PUT')

    <section class="form-section">
      <h3 class="form-section-title">
        <i class="fas fa-building"></i>
        Entreprise et identité
      </h3>
      <div class="row">
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">Nom entreprise <span class="required">*</span></label>
            <input type="text" name="tenant_name" class="form-control @error('tenant_name') is-invalid @enderror" value="{{ old('tenant_name', $tenant->name) }}">
            @error('tenant_name')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="tenant_email" class="form-control @error('tenant_email') is-invalid @enderror" value="{{ old('tenant_email', $tenant->email) }}">
            @error('tenant_email')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">Téléphone (format +...)</label>
            <input type="text" name="tenant_phone" class="form-control @error('tenant_phone') is-invalid @enderror" placeholder="+33612345678" value="{{ old('tenant_phone', $tenant->phone) }}">
            @error('tenant_phone')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">Pays</label>
            <select name="company_country" class="form-control @error('company_country') is-invalid @enderror">
              <option value="">Sélectionnez...</option>
              @foreach(($countries ?? []) as $country)
                <option value="{{ $country['code'] }}" {{ old('company_country', $settings['company_country'] ?? '') === $country['code'] ? 'selected' : '' }}>
                  {{ $country['name'] }} ({{ $country['code'] }}) {{ $country['dial'] }}
                </option>
              @endforeach
            </select>
            @error('company_country')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-4">
          <div class="form-group">
            <label class="form-label">Code postal</label>
            <input type="text" name="company_postal_code" class="form-control @error('company_postal_code') is-invalid @enderror" value="{{ old('company_postal_code', $settings['company_postal_code'] ?? '') }}">
            @error('company_postal_code')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-8">
          <div class="form-group">
            <label class="form-label">Ville</label>
            <input type="text" name="company_city" class="form-control @error('company_city') is-invalid @enderror" value="{{ old('company_city', $settings['company_city'] ?? '') }}">
            @error('company_city')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-12">
          <div class="form-group">
            <label class="form-label">Adresse</label>
            <textarea name="tenant_address" rows="3" class="form-control @error('tenant_address') is-invalid @enderror">{{ old('tenant_address', $tenant->address) }}</textarea>
            @error('tenant_address')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">Site web</label>
            <input type="url" name="company_website" class="form-control @error('company_website') is-invalid @enderror" placeholder="https://..." value="{{ old('company_website', $settings['company_website'] ?? '') }}">
            @error('company_website')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">Description société</label>
            <textarea name="company_description" rows="3" class="form-control @error('company_description') is-invalid @enderror">{{ old('company_description', $settings['company_description'] ?? '') }}</textarea>
            @error('company_description')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
      </div>
    </section>

    <section class="form-section">
      <h3 class="form-section-title">
        <i class="fas fa-globe"></i>
        Préférences régionales
      </h3>
      <div class="row">
        <div class="col-4">
          <div class="form-group">
            <label class="form-label">Fuseau horaire <span class="required">*</span></label>
            <select name="tenant_timezone" class="form-control @error('tenant_timezone') is-invalid @enderror" required>
              @foreach(($timezones ?? []) as $tz)
                <option value="{{ $tz }}" {{ old('tenant_timezone', $tenant->timezone ?? 'Europe/Paris') === $tz ? 'selected' : '' }}>{{ $tz }}</option>
              @endforeach
            </select>
            @error('tenant_timezone')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-4">
          <div class="form-group">
            <label class="form-label">Devise <span class="required">*</span></label>
            <select name="tenant_currency" class="form-control @error('tenant_currency') is-invalid @enderror" required>
              @foreach(($currencies ?? []) as $code => $label)
                <option value="{{ $code }}" {{ old('tenant_currency', strtoupper($tenant->currency ?? 'EUR')) === $code ? 'selected' : '' }}>
                  {{ $code }} - {{ $label }}
                </option>
              @endforeach
            </select>
            @error('tenant_currency')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-4">
          <div class="form-group">
            <label class="form-label">Langue <span class="required">*</span></label>
            <select name="tenant_locale" class="form-control @error('tenant_locale') is-invalid @enderror" required>
              <option value="fr" {{ old('tenant_locale', $tenant->locale ?? 'fr') === 'fr' ? 'selected' : '' }}>Français</option>
              <option value="en" {{ old('tenant_locale', $tenant->locale ?? 'fr') === 'en' ? 'selected' : '' }}>English</option>
            </select>
            @error('tenant_locale')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
      </div>
    </section>

    <section class="form-section">
      <h3 class="form-section-title">
        <i class="fas fa-sliders"></i>
        Configuration CRM
      </h3>
      <div class="row">
        <div class="col-4">
          <div class="form-group">
            <label class="form-label">Préfixe facture</label>
            <input type="text" name="invoice_prefix" class="form-control @error('invoice_prefix') is-invalid @enderror" placeholder="INV" value="{{ old('invoice_prefix', $settings['invoice_prefix'] ?? 'INV') }}">
            @error('invoice_prefix')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-4">
          <div class="form-group">
            <label class="form-label">TVA par défaut (%)</label>
            <input type="number" min="0" max="100" step="0.01" name="default_tax_rate" class="form-control @error('default_tax_rate') is-invalid @enderror" value="{{ old('default_tax_rate', $settings['default_tax_rate'] ?? '20') }}">
            @error('default_tax_rate')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-4">
          <div class="form-group">
            <label class="form-label">Format date</label>
            @php($format = old('date_format', $settings['date_format'] ?? 'd/m/Y'))
            <select name="date_format" class="form-control @error('date_format') is-invalid @enderror">
              <option value="d/m/Y" {{ $format === 'd/m/Y' ? 'selected' : '' }}>Jour/Mois/Année (31/12/2026)</option>
              <option value="m/d/Y" {{ $format === 'm/d/Y' ? 'selected' : '' }}>Mois/Jour/Année (12/31/2026)</option>
              <option value="Y-m-d" {{ $format === 'Y-m-d' ? 'selected' : '' }}>ISO (2026-12-31)</option>
            </select>
            @error('date_format')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">Ouverture (HH:MM)</label>
            <input type="time" name="business_hours_start" class="form-control @error('business_hours_start') is-invalid @enderror" value="{{ old('business_hours_start', $settings['business_hours_start'] ?? '09:00') }}">
            @error('business_hours_start')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">Fermeture (HH:MM)</label>
            <input type="time" name="business_hours_end" class="form-control @error('business_hours_end') is-invalid @enderror" value="{{ old('business_hours_end', $settings['business_hours_end'] ?? '18:00') }}">
            @error('business_hours_end')<span class="form-error">{{ $message }}</span>@enderror
          </div>
        </div>
        <div class="col-6">
          <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;">
              <input type="checkbox" name="notifications_email" value="1" {{ old('notifications_email', $settings['notifications_email'] ?? '1') == '1' ? 'checked' : '' }}>
              Notifications email activées
            </label>
          </div>
        </div>
        <div class="col-6">
          <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;">
              <input type="checkbox" name="notifications_browser" value="1" {{ old('notifications_browser', $settings['notifications_browser'] ?? '1') == '1' ? 'checked' : '' }}>
              Notifications navigateur activées
            </label>
          </div>
        </div>
        <div class="col-12" id="automation-suggestions-settings">
          <div class="form-group" style="display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:16px 18px;border:1px solid var(--c-ink-05);border-radius:16px;background:var(--surface-0);">
            <div>
              <label class="form-label" style="margin-bottom:6px;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-wand-magic-sparkles"></i>
                Suggestions intelligentes
              </label>
              <p style="margin:0;color:var(--c-ink-50);font-size:13px;line-height:1.6;">
                Active ou desactive la modale de suggestions apres la creation d un client, devis, facture, projet ou tache.
              </p>
            </div>
            <label style="display:flex;align-items:center;gap:8px;font-weight:700;white-space:nowrap;margin-top:2px;">
              <input type="checkbox" name="automation_suggestions_enabled" value="1" {{ old('automation_suggestions_enabled', $settings['automation_suggestions_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
              Activer
            </label>
          </div>
        </div>
      </div>
    </section>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary" id="globalSettingsSaveBtn">
        <i class="fas fa-floppy-disk"></i> Enregistrer les paramètres
      </button>
    </div>
  </form>
@endif
@endsection

@push('scripts')
<script src="{{ asset('vendor/client/js/global-settings.js') }}"></script>
@endpush

