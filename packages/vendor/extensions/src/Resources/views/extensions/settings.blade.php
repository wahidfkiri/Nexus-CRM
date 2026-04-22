@extends('layouts.global')

@section('title', 'Paramètres — ' . $extension->name)

@section('breadcrumb')
  <a href="{{ route('marketplace.my-apps') }}">Mes applications</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $extension->name }}</span>
@endsection

@section('content')

@php $color = $extension->category_color; @endphp

<div class="page-header">
  <div class="page-header-left" style="display:flex;align-items:center;gap:16px;">
    <div style="width:52px;height:52px;border-radius:14px;background:{{ $color }}18;display:flex;align-items:center;justify-content:center;font-size:24px;border:1px solid {{ $color }}22;">
      @if($extension->icon_url)
        <img src="{{ $extension->icon_url }}" style="width:32px;height:32px;object-fit:contain;" alt="">
      @else
        <i class="fas {{ $extension->category_icon }}" style="color:{{ $color }};"></i>
      @endif
    </div>
    <div>
      <h1>{{ $extension->name }}</h1>
      <p>Configuration de l'intégration</p>
    </div>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('marketplace.show', $extension->slug) }}" class="btn btn-secondary">
      <i class="fas fa-arrow-left"></i> Retour
    </a>
  </div>
</div>

<div class="row" style="align-items:flex-start;max-width:960px;">
  <div class="col-8" style="padding:0 12px 0 0;">

    <form id="settingsForm" action="{{ route('marketplace.settings.save', $extension->slug) }}" method="POST">
      @csrf
      @method('POST')

      @php
        $schema   = $extension->settings_schema ?? [];
        $current  = $activation->settings ?? [];
      @endphp

      @if(empty($schema))
        {{-- Formulaire générique si pas de schéma défini --}}
        <div class="form-section">
          <h3 class="form-section-title"><i class="fas fa-key"></i> Clés API & Connexion</h3>

          <div class="form-group">
            <label class="form-label">Clé API / Token</label>
            <div class="input-group">
              <i class="fas fa-key input-icon"></i>
              <input type="password" name="api_key" class="form-control"
                     value="{{ $current['api_key'] ?? '' }}"
                     placeholder="sk_live_xxxxxxxxxxxxxxxx">
            </div>
            <span class="form-hint">Retrouvez cette clé dans les paramètres de votre compte {{ $extension->name }}.</span>
          </div>

          <div class="form-group">
            <label class="form-label">Webhook URL (optionnel)</label>
            <div class="input-group">
              <i class="fas fa-link input-icon"></i>
              <input type="url" name="webhook_url" class="form-control"
                     value="{{ $current['webhook_url'] ?? '' }}"
                     placeholder="https://…/webhook">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Environnement</label>
            <select name="environment" class="form-control">
              <option value="production" {{ ($current['environment'] ?? 'production') === 'production' ? 'selected' : '' }}>Production</option>
              <option value="sandbox"    {{ ($current['environment'] ?? '') === 'sandbox'    ? 'selected' : '' }}>Sandbox / Test</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Notes de configuration</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Informations internes…">{{ $current['notes'] ?? '' }}</textarea>
          </div>
        </div>

        {{-- Notifications --}}
        <div class="form-section">
          <h3 class="form-section-title"><i class="fas fa-bell"></i> Notifications</h3>
          @foreach([
            ['key'=>'notify_on_event',   'label'=>'Notifications d\'événements',    'desc'=>'Recevoir les alertes en temps réel'],
            ['key'=>'sync_contacts',     'label'=>'Synchronisation contacts',        'desc'=>'Synchroniser automatiquement les contacts'],
            ['key'=>'sync_invoices',     'label'=>'Synchronisation factures',        'desc'=>'Envoyer les factures automatiquement'],
          ] as $opt)
          @php $val = $current[$opt['key']] ?? false; @endphp
          <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--c-ink-05);">
            <div>
              <div style="font-size:13.5px;font-weight:var(--fw-medium);">{{ $opt['label'] }}</div>
              <div style="font-size:12px;color:var(--c-ink-40);">{{ $opt['desc'] }}</div>
            </div>
            <label style="position:relative;width:44px;height:24px;cursor:pointer;">
              <input type="checkbox" name="{{ $opt['key'] }}" value="1" {{ $val ? 'checked' : '' }}
                     onchange="this.nextElementSibling.style.background=this.checked?'var(--c-accent)':'var(--c-ink-10)';this.nextElementSibling.querySelector('div').style.transform=this.checked?'translateX(20px)':'translateX(3px)'"
                     style="position:absolute;opacity:0;width:100%;height:100%;cursor:pointer;margin:0;z-index:1;">
              <div style="position:absolute;inset:0;border-radius:12px;transition:background .2s;background:{{ $val ? 'var(--c-accent)' : 'var(--c-ink-10)' }};">
                <div style="position:absolute;width:18px;height:18px;background:#fff;border-radius:50%;top:3px;transition:transform .2s;box-shadow:var(--shadow-sm);{{ $val ? 'transform:translateX(20px);' : 'transform:translateX(3px);' }}"></div>
              </div>
            </label>
          </div>
          @endforeach
        </div>

      @else
        {{-- Schéma dynamique défini par l'extension --}}
        <div class="form-section">
          <h3 class="form-section-title"><i class="fas fa-sliders"></i> Configuration</h3>
          @foreach($schema as $field)
          <div class="form-group">
            <label class="form-label">
              {{ $field['label'] ?? $field['key'] }}
              @if(!empty($field['required'])) <span class="required">*</span> @endif
            </label>
            @switch($field['type'] ?? 'text')
              @case('text')
              @case('email')
              @case('url')
              @case('password')
                <input type="{{ $field['type'] ?? 'text' }}" name="{{ $field['key'] }}" class="form-control"
                       value="{{ $current[$field['key']] ?? ($field['default'] ?? '') }}"
                       placeholder="{{ $field['placeholder'] ?? '' }}"
                       {{ !empty($field['required']) ? 'required' : '' }}>
                @break
              @case('select')
                <select name="{{ $field['key'] }}" class="form-control">
                  @foreach($field['options'] ?? [] as $optKey => $optLabel)
                    <option value="{{ $optKey }}" {{ ($current[$field['key']] ?? ($field['default'] ?? '')) === $optKey ? 'selected' : '' }}>
                      {{ $optLabel }}
                    </option>
                  @endforeach
                </select>
                @break
              @case('textarea')
                <textarea name="{{ $field['key'] }}" class="form-control" rows="{{ $field['rows'] ?? 3 }}"
                          placeholder="{{ $field['placeholder'] ?? '' }}">{{ $current[$field['key']] ?? ($field['default'] ?? '') }}</textarea>
                @break
              @case('boolean')
                @php $bval = $current[$field['key']] ?? ($field['default'] ?? false); @endphp
                <label style="position:relative;width:44px;height:24px;cursor:pointer;display:block;">
                  <input type="checkbox" name="{{ $field['key'] }}" value="1" {{ $bval ? 'checked' : '' }}
                         style="position:absolute;opacity:0;width:100%;height:100%;cursor:pointer;margin:0;z-index:1;"
                         onchange="this.nextElementSibling.style.background=this.checked?'var(--c-accent)':'var(--c-ink-10)';this.nextElementSibling.querySelector('div').style.transform=this.checked?'translateX(20px)':'translateX(3px)'">
                  <div style="position:absolute;inset:0;border-radius:12px;transition:background .2s;background:{{ $bval ? 'var(--c-accent)' : 'var(--c-ink-10)' }};">
                    <div style="position:absolute;width:18px;height:18px;background:#fff;border-radius:50%;top:3px;transition:transform .2s;box-shadow:var(--shadow-sm);{{ $bval ? 'transform:translateX(20px)' : 'transform:translateX(3px)' }};"></div>
                  </div>
                </label>
                @break
            @endswitch
            @if(!empty($field['hint']))
              <span class="form-hint">{{ $field['hint'] }}</span>
            @endif
          </div>
          @endforeach
        </div>
      @endif

      <div class="form-actions">
        <a href="{{ route('marketplace.show', $extension->slug) }}" class="btn btn-secondary">
          <i class="fas fa-times"></i> Annuler
        </a>
        <button type="submit" class="btn btn-primary" id="submitBtn">
          <i class="fas fa-check"></i> Sauvegarder la configuration
        </button>
      </div>
    </form>

  </div>

  <div class="col-4" style="padding:0 0 0 12px;">
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-circle-info"></i><h3>Activation</h3></div>
      <div class="info-card-body">
        <div class="info-row">
          <span class="info-row-label">Statut</span>
          <span class="badge badge-{{ $activation->status === 'active' ? 'actif' : ($activation->status === 'trial' ? 'info' : 'inactif') }}">
            {{ $activation->status_label }}
          </span>
        </div>
        <div class="info-row">
          <span class="info-row-label">Activée le</span>
          <span class="info-row-value">{{ $activation->activated_at?->format('d/m/Y') ?? '—' }}</span>
        </div>
        @if($activation->is_trial && $activation->trial_ends_at)
        <div class="info-row">
          <span class="info-row-label">Essai expire</span>
          <span class="info-row-value" style="color:var(--c-warning);">{{ $activation->trial_ends_at->format('d/m/Y') }}</span>
        </div>
        @endif
        @if($activation->price_paid > 0)
        <div class="info-row">
          <span class="info-row-label">Prix</span>
          <span class="info-row-value">{{ number_format($activation->price_paid, 2) }} {{ $activation->currency }}</span>
        </div>
        @endif
      </div>
    </div>

    @if($extension->documentation_url)
    <div class="info-card">
      <div class="info-card-header"><i class="fas fa-book"></i><h3>Aide</h3></div>
      <div class="info-card-body">
        <a href="{{ $extension->documentation_url }}" target="_blank" rel="noopener" class="btn btn-secondary" style="justify-content:flex-start;width:100%;">
          <i class="fas fa-external-link-alt"></i> Voir la documentation
        </a>
      </div>
    </div>
    @endif
  </div>
</div>

@endsection

@push('scripts')
<script>
ajaxForm('settingsForm', {
  onSuccess: (data) => {
    Toast.success('Configuration sauvegardée !', data.message);
  }
});
</script>
@endpush
