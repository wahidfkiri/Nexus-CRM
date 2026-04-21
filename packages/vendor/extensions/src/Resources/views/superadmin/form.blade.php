@extends('layouts.global')

@section('title', isset($extension) ? 'Modifier — '.$extension->name : 'Nouvelle extension')

@section('breadcrumb')
  <a href="{{ route('superadmin.extensions.index') }}">Extensions</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ isset($extension) ? 'Modifier' : 'Nouvelle extension' }}</span>
@endsection

@section('content')

@php
  $isEdit = isset($extension);
  $act    = $isEdit ? route('superadmin.extensions.update', $extension) : route('superadmin.extensions.store');
  $method = $isEdit ? 'PUT' : 'POST';
@endphp

<div class="page-header">
  <div class="page-header-left">
    <h1>{{ $isEdit ? 'Modifier l\'extension' : 'Nouvelle extension' }}</h1>
    <p>{{ $isEdit ? $extension->name : 'Ajoutez une application au marketplace' }}</p>
  </div>
  <a href="{{ $isEdit ? route('superadmin.extensions.show', $extension) : route('superadmin.extensions.index') }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> Retour
  </a>
</div>

<form id="extForm" action="{{ $act }}" method="POST" enctype="multipart/form-data">
  @csrf
  @if($isEdit) @method('PUT') @endif

  <div class="row" style="align-items:flex-start;">

    {{-- Colonne principale --}}
    <div class="col-8" style="padding:0 12px 0 0;">

      {{-- Infos de base --}}
      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-puzzle-piece"></i> Informations de l'extension</h3>
        <div class="row">
          <div class="col-8">
            <div class="form-group">
              <label class="form-label">Nom <span class="required">*</span></label>
              <input type="text" name="name" class="form-control" value="{{ old('name', $isEdit ? $extension->name : '') }}" placeholder="Ex: Google Drive" required>
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">Version</label>
              <input type="text" name="version" class="form-control" value="{{ old('version', $isEdit ? $extension->version : '1.0.0') }}" placeholder="1.0.0">
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Slogan court</label>
              <input type="text" name="tagline" class="form-control" value="{{ old('tagline', $isEdit ? $extension->tagline : '') }}" placeholder="Stockez et partagez vos fichiers sans effort" maxlength="255">
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Description courte</label>
              <textarea name="description" class="form-control" rows="2" maxlength="500" placeholder="Description affichée dans les cartes du marketplace…">{{ old('description', $isEdit ? $extension->description : '') }}</textarea>
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Description longue <span class="hint">(Markdown supporté)</span></label>
              <textarea name="long_description" class="form-control" rows="6" placeholder="Description détaillée, fonctionnalités, cas d'usage…">{{ old('long_description', $isEdit ? $extension->long_description : '') }}</textarea>
            </div>
          </div>
        </div>
      </div>

      {{-- Icône & Visuels --}}
      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-image"></i> Icône & Visuels</h3>
        <div class="row">
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Icône FontAwesome <span class="hint">(ou upload)</span></label>
              <div class="input-group">
                <i class="fas fa-icons input-icon"></i>
                <input type="text" name="icon" class="form-control" value="{{ old('icon', $isEdit ? $extension->icon : '') }}" placeholder="fa-puzzle-piece ou vide si upload"
                       oninput="updateIconPreview(this.value)">
              </div>
              <span class="form-hint">Ex: <code>fa-google-drive</code>, <code>fa-slack</code></span>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Couleur de fond icône</label>
              <div style="display:flex;gap:8px;align-items:center;">
                <input type="color" name="icon_bg_color" id="iconBgColor"
                       value="{{ old('icon_bg_color', $isEdit ? $extension->icon_bg_color : '#3b82f6') }}"
                       style="width:44px;height:38px;border-radius:var(--r-sm);border:1.5px solid var(--c-ink-10);cursor:pointer;padding:2px;"
                       oninput="updateIconPreview()">
                {{-- Aperçu icône --}}
                <div id="iconPreview" style="width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;transition:all .2s;">
                  <i class="fas fa-puzzle-piece" id="previewIcon"></i>
                </div>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Upload icône <span class="hint">(PNG/SVG, 256×256)</span></label>
              <input type="file" name="icon_file" class="form-control" accept="image/*">
              @if($isEdit && $extension->icon_url)
                <div style="margin-top:8px;"><img src="{{ $extension->icon_url }}" style="width:40px;height:40px;border-radius:10px;border:1px solid var(--c-ink-05);"></div>
              @endif
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Upload banner <span class="hint">(1200×400)</span></label>
              <input type="file" name="banner_file" class="form-control" accept="image/*">
              @if($isEdit && $extension->banner_url)
                <div style="margin-top:8px;"><img src="{{ $extension->banner_url }}" style="width:100%;border-radius:var(--r-sm);border:1px solid var(--c-ink-05);"></div>
              @endif
            </div>
          </div>
        </div>
      </div>

      {{-- Éditeur --}}
      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-code"></i> Éditeur & Liens</h3>
        <div class="row">
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Nom de l'éditeur</label>
              <input type="text" name="developer_name" class="form-control" value="{{ old('developer_name', $isEdit ? $extension->developer_name : '') }}">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Site web éditeur</label>
              <input type="url" name="developer_url" class="form-control" value="{{ old('developer_url', $isEdit ? $extension->developer_url : '') }}" placeholder="https://…">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Documentation</label>
              <input type="url" name="documentation_url" class="form-control" value="{{ old('documentation_url', $isEdit ? $extension->documentation_url : '') }}" placeholder="https://docs.…">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Support</label>
              <input type="url" name="support_url" class="form-control" value="{{ old('support_url', $isEdit ? $extension->support_url : '') }}" placeholder="https://…">
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">URL Webhook <span class="hint">(events d'activation)</span></label>
              <input type="url" name="webhook_url" class="form-control" value="{{ old('webhook_url', $isEdit ? $extension->webhook_url : '') }}" placeholder="https://…/webhook">
            </div>
          </div>
        </div>
      </div>

    </div>

    {{-- Sidebar --}}
    <div class="col-4" style="padding:0 0 0 12px;">

      {{-- Catégorie & Statut --}}
      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title"><i class="fas fa-tag"></i> Classification</h3>
        <div class="form-group">
          <label class="form-label">Catégorie <span class="required">*</span></label>
          <select name="category" class="form-control" required>
            @foreach($categories as $key => $cat)
              <option value="{{ $key }}" {{ old('category', $isEdit ? $extension->category : 'other') === $key ? 'selected' : '' }}>
                {{ $cat['label'] }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Statut <span class="required">*</span></label>
          <select name="status" class="form-control" required>
            @foreach($statuses as $key => $label)
              <option value="{{ $key }}" {{ old('status', $isEdit ? $extension->status : 'active') === $key ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Ordre d'affichage</label>
          <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $isEdit ? $extension->sort_order : 0) }}" min="0">
        </div>
      </div>

      {{-- Tarification --}}
      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title"><i class="fas fa-euro-sign"></i> Tarification</h3>
        <div class="form-group">
          <label class="form-label">Type de prix <span class="required">*</span></label>
          <select name="pricing_type" class="form-control" id="pricingType" onchange="togglePricing()" required>
            @foreach($pricingTypes as $key => $label)
              <option value="{{ $key }}" {{ old('pricing_type', $isEdit ? $extension->pricing_type : 'free') === $key ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
        </div>
        <div id="priceFields" style="{{ old('pricing_type', $isEdit ? $extension->pricing_type : 'free') === 'free' ? 'display:none;' : '' }}">
          <div class="form-group">
            <label class="form-label">Prix mensuel (€)</label>
            <div class="input-group input-right">
              <input type="number" name="price" class="form-control" value="{{ old('price', $isEdit ? $extension->price : 0) }}" min="0" step="0.01">
              <i class="fas fa-euro-sign input-icon"></i>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Prix annuel (€) <span class="hint">(optionnel)</span></label>
            <div class="input-group input-right">
              <input type="number" name="yearly_price" class="form-control" value="{{ old('yearly_price', $isEdit ? $extension->yearly_price : '') }}" min="0" step="0.01">
              <i class="fas fa-euro-sign input-icon"></i>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Cycle de facturation</label>
            <select name="billing_cycle" class="form-control">
              <option value="">Sélectionner…</option>
              @foreach($billingCycles as $key => $label)
                <option value="{{ $key }}" {{ old('billing_cycle', $isEdit ? $extension->billing_cycle : '') === $key ? 'selected' : '' }}>
                  {{ $label }}
                </option>
              @endforeach
            </select>
          </div>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-top:1px solid var(--c-ink-05);margin-top:4px;">
          <div>
            <div style="font-size:13.5px;font-weight:var(--fw-medium);">Essai gratuit</div>
            <div style="font-size:12px;color:var(--c-ink-40);">Activer une période d'essai</div>
          </div>
          <label style="position:relative;width:44px;height:24px;cursor:pointer;">
            <input type="checkbox" name="has_trial" id="hasTrial" value="1"
                   {{ old('has_trial', $isEdit ? $extension->has_trial : false) ? 'checked' : '' }}
                   onchange="toggleTrial(this.checked)"
                   style="position:absolute;opacity:0;width:100%;height:100%;cursor:pointer;margin:0;z-index:1;">
            <div id="trialTrack" style="position:absolute;inset:0;border-radius:12px;transition:background .2s;background:{{ old('has_trial', $isEdit ? $extension->has_trial : false) ? 'var(--c-accent)' : 'var(--c-ink-10)' }};">
              <div style="position:absolute;width:18px;height:18px;background:#fff;border-radius:50%;top:3px;transition:transform .2s;box-shadow:var(--shadow-sm);{{ old('has_trial', $isEdit ? $extension->has_trial : false) ? 'transform:translateX(20px);' : 'transform:translateX(3px);' }}"></div>
            </div>
          </label>
        </div>
        <div id="trialDaysField" style="{{ old('has_trial', $isEdit ? $extension->has_trial : false) ? '' : 'display:none;' }}">
          <div class="form-group" style="margin-top:10px;">
            <label class="form-label">Durée d'essai (jours)</label>
            <input type="number" name="trial_days" class="form-control" value="{{ old('trial_days', $isEdit ? $extension->trial_days : 14) }}" min="1" max="365">
          </div>
        </div>
      </div>

      {{-- Badges & Options --}}
      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title"><i class="fas fa-certificate"></i> Badges & Options</h3>
        @foreach([
          ['name'=>'is_featured', 'label'=>'Mise en avant', 'desc'=>'Affiché en tête du marketplace'],
          ['name'=>'is_new',      'label'=>'Nouveau',        'desc'=>'Badge "Nouveau" affiché'],
          ['name'=>'is_verified', 'label'=>'Vérifié',        'desc'=>'Vérifié par l\'équipe'],
          ['name'=>'is_official', 'label'=>'Officiel',       'desc'=>'Extension officielle NexusCRM'],
        ] as $opt)
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--c-ink-05);">
          <div>
            <div style="font-size:13.5px;font-weight:var(--fw-medium);">{{ $opt['label'] }}</div>
            <div style="font-size:12px;color:var(--c-ink-40);">{{ $opt['desc'] }}</div>
          </div>
          @php $optVal = old($opt['name'], $isEdit ? $extension->{$opt['name']} : false); @endphp
          <label style="position:relative;width:44px;height:24px;cursor:pointer;">
            <input type="checkbox" name="{{ $opt['name'] }}" value="1" {{ $optVal ? 'checked' : '' }}
                   onchange="this.nextElementSibling.style.background=this.checked?'var(--c-accent)':'var(--c-ink-10)'; this.nextElementSibling.querySelector('div').style.transform=this.checked?'translateX(20px)':'translateX(3px)'"
                   style="position:absolute;opacity:0;width:100%;height:100%;cursor:pointer;margin:0;z-index:1;">
            <div style="position:absolute;inset:0;border-radius:12px;transition:background .2s;background:{{ $optVal ? 'var(--c-accent)' : 'var(--c-ink-10)' }};">
              <div style="position:absolute;width:18px;height:18px;background:#fff;border-radius:50%;top:3px;transition:transform .2s;box-shadow:var(--shadow-sm);{{ $optVal ? 'transform:translateX(20px);' : 'transform:translateX(3px);' }}"></div>
            </div>
          </label>
        </div>
        @endforeach
      </div>

      {{-- Actions --}}
      <div class="form-section">
        <div style="display:flex;flex-direction:column;gap:10px;">
          <button type="submit" class="btn btn-primary" id="submitBtn" style="justify-content:center;">
            <i class="fas fa-check"></i> {{ $isEdit ? 'Enregistrer' : 'Créer l\'extension' }}
          </button>
          <a href="{{ $isEdit ? route('superadmin.extensions.show', $extension) : route('superadmin.extensions.index') }}" class="btn btn-secondary" style="justify-content:center;">
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
function togglePricing() {
  const type = document.getElementById('pricingType').value;
  document.getElementById('priceFields').style.display = type === 'free' ? 'none' : '';
}

function toggleTrial(checked) {
  document.getElementById('trialDaysField').style.display = checked ? '' : 'none';
  document.getElementById('trialTrack').style.background = checked ? 'var(--c-accent)' : 'var(--c-ink-10)';
  document.getElementById('trialTrack').querySelector('div').style.transform = checked ? 'translateX(20px)' : 'translateX(3px)';
}

function updateIconPreview(iconClass) {
  const icon  = iconClass || document.querySelector('input[name=icon]')?.value || 'fa-puzzle-piece';
  const color = document.getElementById('iconBgColor')?.value || '#3b82f6';
  const wrap  = document.getElementById('iconPreview');
  const el    = document.getElementById('previewIcon');
  if (wrap) wrap.style.background = color + '22';
  if (el)  { el.className = `fas ${icon}`; el.style.color = color; }
}

document.addEventListener('DOMContentLoaded', () => {
  updateIconPreview();

  ajaxForm('extForm', {
    onSuccess: (data) => {
      Toast.success('{{ $isEdit ? "Extension mise à jour !" : "Extension créée !" }}', data.message);
    }
  });
});
</script>
@endpush