@extends('invoice::layouts.invoice')

@section('title', 'ParamÃ¨tres Facturation')

@section('breadcrumb')
  <span>Configuration</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">ParamÃ¨tres Facturation</span>
@endsection

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>ParamÃ¨tres Facturation</h1>
    <p>Configurez TVA, signatures, retenues, numÃ©rotation et prÃ©fÃ©rences</p>
  </div>
</div>

{{-- Tabs --}}
<div style="display:flex;gap:4px;margin-bottom:24px;background:var(--surface-0);border:1px solid var(--c-ink-05);border-radius:var(--r-lg);padding:6px;width:fit-content;box-shadow:var(--shadow-xs);">
  @foreach([
    ['id'=>'numbering',   'icon'=>'fa-hashtag',             'label'=>'NumÃ©rotation'],
    ['id'=>'taxes',       'icon'=>'fa-percent',              'label'=>'TVA'],
    ['id'=>'withholding', 'icon'=>'fa-building-columns',     'label'=>'Retenue Ã  la source'],
    ['id'=>'signature',   'icon'=>'fa-signature',            'label'=>'Signature Ã©lectronique'],
    ['id'=>'accounting',  'icon'=>'fa-book',                 'label'=>'ComptabilitÃ©'],
    ['id'=>'reminders',   'icon'=>'fa-bell',                 'label'=>'Rappels'],
    ['id'=>'templates',   'icon'=>'fa-palette',              'label'=>'Templates PDF'],
  ] as $tab)
  <button class="tab-btn {{ $loop->first ? 'active' : '' }}" onclick="switchTab('{{ $tab['id'] }}')" id="tab-btn-{{ $tab['id'] }}"
    style="padding:8px 14px;border:none;background:{{ $loop->first ? 'var(--c-accent)' : 'transparent' }};color:{{ $loop->first ? '#fff' : 'var(--c-ink-60)' }};border-radius:var(--r-sm);font-size:13px;font-weight:var(--fw-medium);cursor:pointer;display:flex;align-items:center;gap:7px;transition:all var(--dur-fast);white-space:nowrap;">
    <i class="fas {{ $tab['icon'] }}" style="font-size:12px;"></i> {{ $tab['label'] }}
  </button>
  @endforeach
</div>

<form id="settingsForm" action="{{ route('invoices.settings.update') }}" method="POST" enctype="multipart/form-data">
@csrf
@method('PUT')

{{-- â”€â”€ NUMÃ‰ROTATION â”€â”€ --}}
<div id="tab-numbering" class="tab-panel">
  <div class="form-section">
    <h3 class="form-section-title">
      <i class="fas fa-hashtag"></i> Configuration de la numÃ©rotation
    </h3>
    <div class="row">
      <div class="col-6">
        <div class="form-group">
          <label class="form-label">PrÃ©fixe factures <span class="required">*</span></label>
          <input type="text" name="invoice_prefix" class="form-control" value="{{ config('invoice.numbering.invoice_prefix', 'FAC') }}" placeholder="FAC">
          <span class="form-hint">Ex : FAC â†’ FAC-2024-0001</span>
        </div>
      </div>
      <div class="col-6">
        <div class="form-group">
          <label class="form-label">PrÃ©fixe devis <span class="required">*</span></label>
          <input type="text" name="quote_prefix" class="form-control" value="{{ config('invoice.numbering.quote_prefix', 'DEV') }}" placeholder="DEV">
          <span class="form-hint">Ex : DEV â†’ DEV-2024-0001</span>
        </div>
      </div>
      <div class="col-4">
        <div class="form-group">
          <label class="form-label">SÃ©parateur</label>
          <select name="numbering_separator" class="form-control">
            <option value="-" selected>- (tiret)</option>
            <option value="/">/  (slash)</option>
            <option value=".">. (point)</option>
          </select>
        </div>
      </div>
      <div class="col-4">
        <div class="form-group">
          <label class="form-label">Nombre de chiffres</label>
          <select name="numbering_digits" class="form-control">
            @foreach([3,4,5,6] as $d)
              <option value="{{ $d }}" {{ $d == 4 ? 'selected' : '' }}>{{ $d }} ({{ str_pad('1', $d, '0', STR_PAD_LEFT) }})</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="col-4">
        <div class="form-group">
          <label class="form-label">Remise Ã  zÃ©ro annuelle</label>
          <div style="padding-top:8px;">
            <label class="toggle-switch">
              <input type="checkbox" name="reset_yearly" checked>
              <span class="toggle-slider"></span>
            </label>
            <span style="margin-left:10px;font-size:13px;color:var(--c-ink-60);">Activer</span>
          </div>
        </div>
      </div>
    </div>
    <div style="background:var(--c-accent-xl);border-radius:var(--r-sm);padding:12px 16px;font-size:13px;color:var(--c-ink-60);">
      <i class="fas fa-eye" style="color:var(--c-accent);"></i>
      AperÃ§u : <strong style="font-family:monospace;">FAC-{{ date('Y') }}-0001</strong>
    </div>
  </div>
</div>

{{-- â”€â”€ TVA â”€â”€ --}}
<div id="tab-taxes" class="tab-panel" style="display:none;">
  <div class="form-section">
    <h3 class="form-section-title">
      <i class="fas fa-percent"></i> Taux de TVA
    </h3>
    <div style="margin-bottom:16px;">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
        <label class="toggle-switch">
          <input type="checkbox" name="tax_enabled" {{ config('invoice.tax.enabled') ? 'checked' : '' }}>
          <span class="toggle-slider"></span>
        </label>
        <span style="font-size:13.5px;font-weight:var(--fw-medium);">Activer la gestion TVA</span>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Taux TVA par dÃ©faut (%)</label>
      <select name="default_tax_rate" class="form-control" style="max-width:200px;">
        @foreach(config('invoice.tax.rates', [0,5,10,20]) as $rate)
          <option value="{{ $rate }}" {{ $rate == config('invoice.tax.default_rate', 20) ? 'selected' : '' }}>{{ $rate }} %</option>
        @endforeach
      </select>
    </div>

    <div style="margin-top:20px;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <label class="form-label" style="margin:0;">Taux disponibles</label>
        <button type="button" class="btn btn-sm btn-secondary" onclick="addTaxRate()">
          <i class="fas fa-plus"></i> Ajouter un taux
        </button>
      </div>
      <div id="taxRatesList">
        @foreach(config('invoice.tax.rates', [0,5,10,20]) as $rate)
        <div class="tax-rate-item" id="tax-{{ $rate }}">
          <div class="tax-badge">{{ $rate }} %</div>
          <span style="flex:1;font-size:13px;color:var(--c-ink-60);">
            {{ $rate === 0 ? 'ExonÃ©rÃ© de TVA' : ($rate === 20 ? 'Taux normal' : ($rate === 10 ? 'Taux intermÃ©diaire' : ($rate === 5.5 ? 'Taux rÃ©duit' : 'Taux personnalisÃ©'))) }}
          </span>
          <input type="hidden" name="tax_rates[]" value="{{ $rate }}">
          @if($rate !== 0 && $rate !== 20)
          <button type="button" class="btn-icon danger btn-sm" onclick="removeTaxRate({{ $rate }})">
            <i class="fas fa-times"></i>
          </button>
          @endif
        </div>
        @endforeach
      </div>
    </div>
  </div>
</div>

{{-- â”€â”€ RETENUE â”€â”€ --}}
<div id="tab-withholding" class="tab-panel" style="display:none;">
  <div class="form-section">
    <h3 class="form-section-title">
      <i class="fas fa-building-columns"></i> Retenue Ã  la source
    </h3>

    <div style="background:var(--c-warning-lt);border:1px solid #fcd34d;border-radius:var(--r-sm);padding:14px 16px;margin-bottom:20px;font-size:13px;color:#92400e;">
      <i class="fas fa-triangle-exclamation"></i>
      La retenue Ã  la source est applicable dans certains pays (Tunisie, Maroc, AlgÃ©rie, SÃ©nÃ©galâ€¦).
      Elle reprÃ©sente une avance d'impÃ´t prÃ©levÃ©e lors du paiement.
    </div>

    <div class="settings-row">
      <div class="settings-row-info">
        <h4>Activer la retenue Ã  la source</h4>
        <p>Affiche le champ retenue lors de la crÃ©ation de factures</p>
      </div>
      <label class="toggle-switch">
        <input type="checkbox" name="withholding_enabled" {{ config('invoice.withholding_tax.enabled') ? 'checked' : '' }}>
        <span class="toggle-slider"></span>
      </label>
    </div>

    <div class="form-group" style="margin-top:16px;">
      <label class="form-label">Taux par dÃ©faut (%)</label>
      <select name="default_withholding_rate" class="form-control" style="max-width:200px;">
        @foreach(config('invoice.withholding_tax.rates', []) as $r)
          <option value="{{ $r['value'] }}" {{ $r['value'] == config('invoice.withholding_tax.default_rate', 0) ? 'selected' : '' }}>{{ $r['label'] }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label">Pays concernÃ©s</label>
      <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:4px;">
        @foreach(config('invoice.withholding_tax.countries', []) as $country)
        <span style="background:var(--c-ink-02);border:1px solid var(--c-ink-05);border-radius:var(--r-full);padding:4px 12px;font-size:12px;font-weight:var(--fw-medium);">
          {{ $country }}
        </span>
        @endforeach
      </div>
      <span class="form-hint">La retenue sera suggÃ©rÃ©e automatiquement pour ces pays</span>
    </div>

    <div style="margin-top:20px;">
      <label class="form-label">Taux disponibles</label>
      @foreach(config('invoice.withholding_tax.rates', []) as $r)
      @if($r['value'] > 0)
      <div class="tax-rate-item">
        <div class="tax-badge" style="background:#fef3c7;color:#92400e;">{{ $r['label'] }}</div>
        <span style="flex:1;font-size:13px;color:var(--c-ink-60);">Retenue {{ $r['label'] }}</span>
        <input type="hidden" name="withholding_rates[]" value="{{ $r['value'] }}">
      </div>
      @endif
      @endforeach
    </div>
  </div>
</div>

{{-- â”€â”€ SIGNATURE Ã‰LECTRONIQUE â”€â”€ --}}
<div id="tab-signature" class="tab-panel" style="display:none;">
  <div class="form-section">
    <h3 class="form-section-title">
      <i class="fas fa-signature"></i> Signature Ã©lectronique
    </h3>

    <div class="settings-row">
      <div class="settings-row-info">
        <h4>Activer la signature Ã©lectronique</h4>
        <p>Permet de signer les devis et factures Ã©lectroniquement</p>
      </div>
      <label class="toggle-switch">
        <input type="checkbox" name="signature_enabled" id="signatureEnabled">
        <span class="toggle-slider"></span>
      </label>
    </div>

    <div id="signatureConfig" style="margin-top:24px;">
      <div class="row">
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">Votre signature</label>
            <div class="signature-pad-wrap">
              <canvas id="signaturePad" width="400" height="160"></canvas>
              <div class="signature-pad-controls">
                <button type="button" class="btn btn-ghost btn-sm" onclick="clearSignature()">
                  <i class="fas fa-eraser"></i> Effacer
                </button>
                <button type="button" class="btn btn-sm btn-secondary" onclick="saveSignature()">
                  <i class="fas fa-save"></i> Sauvegarder
                </button>
              </div>
            </div>
            <input type="hidden" name="signature_data" id="signature_data">
            <span class="form-hint">Signez avec la souris ou le doigt (tactile)</span>
          </div>
        </div>
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">Signature actuelle</label>
            @if($settings['signature_data'] ?? false)
              <img src="{{ $settings['signature_data'] }}" alt="Signature" style="max-width:100%;border:1px solid var(--c-ink-05);border-radius:var(--r-md);padding:10px;background:var(--surface-0);">
            @else
              <div style="height:160px;background:var(--surface-1);border:1px dashed var(--c-ink-10);border-radius:var(--r-md);display:flex;align-items:center;justify-content:center;color:var(--c-ink-20);">
                <span style="font-size:13px;">Aucune signature enregistrÃ©e</span>
              </div>
            @endif
          </div>
          <div class="form-group">
            <label class="form-label">Nom du signataire</label>
            <input type="text" name="signer_name" class="form-control" placeholder="PrÃ©nom Nom" value="{{ $settings['signer_name'] ?? auth()->user()->name }}">
          </div>
          <div class="form-group">
            <label class="form-label">Titre / Fonction</label>
            <input type="text" name="signer_title" class="form-control" placeholder="Directeur Commercial" value="{{ $settings['signer_title'] ?? '' }}">
          </div>
        </div>
      </div>

      <div class="settings-row">
        <div class="settings-row-info">
          <h4>Afficher sur les factures</h4>
          <p>La signature apparaÃ®tra sur les PDF de factures</p>
        </div>
        <label class="toggle-switch">
          <input type="checkbox" name="signature_on_invoice" {{ ($settings['signature_on_invoice'] ?? true) ? 'checked' : '' }}>
          <span class="toggle-slider"></span>
        </label>
      </div>
      <div class="settings-row">
        <div class="settings-row-info">
          <h4>Afficher sur les devis</h4>
          <p>La signature apparaÃ®tra sur les PDF de devis</p>
        </div>
        <label class="toggle-switch">
          <input type="checkbox" name="signature_on_quote" {{ ($settings['signature_on_quote'] ?? true) ? 'checked' : '' }}>
          <span class="toggle-slider"></span>
        </label>
      </div>
    </div>
  </div>
</div>

{{-- â”€â”€ COMPTABILITÃ‰ â”€â”€ --}}
<div id="tab-accounting" class="tab-panel" style="display:none;">
  <div class="form-section">
    <h3 class="form-section-title">
      <i class="fas fa-book"></i> ParamÃ¨tres comptables
    </h3>

    <div class="row">
      <div class="col-6">
        <div class="form-group">
          <label class="form-label">Exercice fiscal (dÃ©but)</label>
          <select name="fiscal_year_start" class="form-control">
            @foreach(['Janvier','FÃ©vrier','Mars','Avril','Mai','Juin','Juillet','AoÃ»t','Septembre','Octobre','Novembre','DÃ©cembre'] as $i => $m)
              <option value="{{ $i + 1 }}" {{ ($i + 1) == 1 ? 'selected' : '' }}>{{ $m }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="col-6">
        <div class="form-group">
          <label class="form-label">Devise de base</label>
          <select name="base_currency" class="form-control">
            @foreach(config('invoice.currencies') as $code => $cfg)
              <option value="{{ $code }}" {{ $code === 'EUR' ? 'selected' : '' }}>{{ $code }} â€” {{ $cfg['name'] }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="col-6">
        <div class="form-group">
          <label class="form-label">MÃ©thode de comptabilisation</label>
          <select name="accounting_method" class="form-control">
            <option value="accrual">ComptabilitÃ© d'engagement (droits constatÃ©s)</option>
            <option value="cash">ComptabilitÃ© de caisse (encaissements)</option>
          </select>
        </div>
      </div>
      <div class="col-6">
        <div class="form-group">
          <label class="form-label">DÃ©lai de paiement par dÃ©faut</label>
          <select name="default_payment_terms" class="form-control">
            @foreach(config('invoice.payment_terms') as $days => $label)
              <option value="{{ $days }}" {{ $days == 30 ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>

    <h4 style="font-size:14px;font-weight:var(--fw-semi);color:var(--c-ink);margin:20px 0 12px;padding-top:16px;border-top:1px solid var(--c-ink-05);">
      Comptes comptables (Plan Comptable GÃ©nÃ©ral)
    </h4>
    <div class="row">
      <div class="col-4">
        <div class="form-group">
          <label class="form-label">Compte clients (4xx)</label>
          <input type="text" name="account_customers" class="form-control font-mono" placeholder="411000" value="{{ $settings['account_customers'] ?? '411000' }}">
        </div>
      </div>
      <div class="col-4">
        <div class="form-group">
          <label class="form-label">Compte ventes (7xx)</label>
          <input type="text" name="account_sales" class="form-control font-mono" placeholder="706000" value="{{ $settings['account_sales'] ?? '706000' }}">
        </div>
      </div>
      <div class="col-4">
        <div class="form-group">
          <label class="form-label">Compte TVA collectÃ©e</label>
          <input type="text" name="account_vat" class="form-control font-mono" placeholder="445710" value="{{ $settings['account_vat'] ?? '445710' }}">
        </div>
      </div>
    </div>

    <div class="settings-row">
      <div class="settings-row-info">
        <h4>Export comptable automatique</h4>
        <p>GÃ©nÃ©rer un fichier d'export comptable Ã  chaque facture payÃ©e</p>
      </div>
      <label class="toggle-switch">
        <input type="checkbox" name="auto_accounting_export">
        <span class="toggle-slider"></span>
      </label>
    </div>
  </div>
</div>

{{-- â”€â”€ RAPPELS â”€â”€ --}}
<div id="tab-reminders" class="tab-panel" style="display:none;">
  <div class="form-section">
    <h3 class="form-section-title">
      <i class="fas fa-bell"></i> Rappels automatiques
    </h3>

    <div class="settings-row">
      <div class="settings-row-info">
        <h4>Activer les rappels automatiques</h4>
        <p>Envoi automatique d'emails de rappel pour les factures impayÃ©es</p>
      </div>
      <label class="toggle-switch">
        <input type="checkbox" name="reminders_enabled" {{ config('invoice.reminders.enabled') ? 'checked' : '' }}>
        <span class="toggle-slider"></span>
      </label>
    </div>

    <div class="form-group" style="margin-top:16px;">
      <label class="form-label">Nombre maximum de rappels</label>
      <select name="max_reminders" class="form-control" style="max-width:200px;">
        @foreach([1,2,3,4,5] as $n)
          <option value="{{ $n }}" {{ $n == config('invoice.reminders.max_reminders', 3) ? 'selected' : '' }}>{{ $n }} rappel(s)</option>
        @endforeach
      </select>
    </div>

    <div style="margin-top:16px;">
      <label class="form-label">Rappels avant Ã©chÃ©ance (jours)</label>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
        @foreach(config('invoice.reminders.days_before', [7,3,1]) as $d)
        <div style="display:flex;align-items:center;gap:8px;background:var(--c-ink-02);border:1px solid var(--c-ink-05);border-radius:var(--r-sm);padding:8px 12px;">
          <i class="fas fa-clock" style="color:var(--c-warning);"></i>
          <span style="font-size:13px;font-weight:var(--fw-medium);">J-{{ $d }}</span>
          <input type="hidden" name="reminder_days_before[]" value="{{ $d }}">
        </div>
        @endforeach
      </div>
    </div>

    <div style="margin-top:12px;">
      <label class="form-label">Rappels aprÃ¨s Ã©chÃ©ance (jours)</label>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
        @foreach(config('invoice.reminders.days_after', [1,7,14,30]) as $d)
        <div style="display:flex;align-items:center;gap:8px;background:var(--c-danger-lt);border:1px solid var(--c-danger-lt);border-radius:var(--r-sm);padding:8px 12px;">
          <i class="fas fa-triangle-exclamation" style="color:var(--c-danger);"></i>
          <span style="font-size:13px;font-weight:var(--fw-medium);">J+{{ $d }}</span>
          <input type="hidden" name="reminder_days_after[]" value="{{ $d }}">
        </div>
        @endforeach
      </div>
    </div>

    <div class="form-group" style="margin-top:20px;">
      <label class="form-label">Message de rappel (email)</label>
      <textarea name="reminder_message" class="form-control" rows="4" placeholder="Bonjour,&#10;Nous vous rappelons que la facture NÂ° {numero} d'un montant de {montant} est Ã©chue depuis {jours} jours...">{{ $settings['reminder_message'] ?? '' }}</textarea>
      <span class="form-hint">Variables : {numero}, {montant}, {client}, {echeance}, {jours}</span>
    </div>
  </div>
</div>

{{-- â”€â”€ TEMPLATES PDF â”€â”€ --}}
<div id="tab-templates" class="tab-panel" style="display:none;">
  <div class="form-section">
    <h3 class="form-section-title">
      <i class="fas fa-palette"></i> Personnalisation PDF
    </h3>
    <div class="row">
      <div class="col-6">
        <div class="form-group">
          <label class="form-label">Couleur principale</label>
          <div style="display:flex;gap:8px;align-items:center;">
            <input type="color" name="pdf_primary_color" value="#2563eb" style="width:44px;height:38px;border-radius:var(--r-sm);border:1.5px solid var(--c-ink-10);cursor:pointer;padding:2px;">
            <input type="text" name="pdf_primary_color_hex" class="form-control font-mono" value="#2563eb" style="max-width:100px;" placeholder="#2563eb">
          </div>
        </div>
      </div>
      <div class="col-6">
        <div class="form-group">
          <label class="form-label">Format papier</label>
          <select name="pdf_paper" class="form-control">
            <option value="A4" selected>A4 (210 Ã— 297 mm)</option>
            <option value="Letter">Letter (216 Ã— 279 mm)</option>
            <option value="Legal">Legal (216 Ã— 356 mm)</option>
          </select>
        </div>
      </div>
      <div class="col-12">
        <div class="form-group">
          <label class="form-label">Mentions lÃ©gales</label>
          <textarea name="pdf_legal_mentions" class="form-control" rows="3" placeholder="ConformÃ©ment Ã  l'article L.441-6 du Code de commerceâ€¦">{{ $settings['pdf_legal_mentions'] ?? '' }}</textarea>
        </div>
      </div>
    </div>

    <div class="settings-row">
      <div class="settings-row-info">
        <h4>Filigrane "BROUILLON"</h4>
        <p>Affiche un filigrane sur les factures en mode brouillon</p>
      </div>
      <label class="toggle-switch">
        <input type="checkbox" name="pdf_watermark_draft" checked>
        <span class="toggle-slider"></span>
      </label>
    </div>
    <div class="settings-row">
      <div class="settings-row-info">
        <h4>Afficher RIB / coordonnÃ©es bancaires</h4>
        <p>Inclure les informations bancaires sur chaque PDF</p>
      </div>
      <label class="toggle-switch">
        <input type="checkbox" name="pdf_show_bank" {{ config('invoice.pdf.show_bank') ? 'checked' : '' }}>
        <span class="toggle-slider"></span>
      </label>
    </div>
  </div>
</div>

{{-- Ã¢â€â‚¬Ã¢â€â‚¬ IDENTITÃƒâ€° PDF & THÃƒË†MES (nouveau) Ã¢â€â‚¬Ã¢â€â‚¬ --}}
<div class="form-section" style="margin-top:16px;">
  <h3 class="form-section-title">
    <i class="fas fa-wand-magic-sparkles"></i> IdentitÃƒÂ© PDF, logo et thÃƒÂ¨mes
  </h3>
  <div class="row">
    <div class="col-6">
      <div class="form-group">
        <label class="form-label">ThÃƒÂ¨me visuel PDF</label>
        <select name="pdf_theme" class="form-control">
          <option value="ocean" {{ ($settings['pdf_theme'] ?? 'ocean') === 'ocean' ? 'selected' : '' }}>Ocean (Bleu pro)</option>
          <option value="emerald" {{ ($settings['pdf_theme'] ?? '') === 'emerald' ? 'selected' : '' }}>Emerald (Vert premium)</option>
          <option value="sunset" {{ ($settings['pdf_theme'] ?? '') === 'sunset' ? 'selected' : '' }}>Sunset (Orange business)</option>
          <option value="mono" {{ ($settings['pdf_theme'] ?? '') === 'mono' ? 'selected' : '' }}>Mono (Sobre)</option>
        </select>
      </div>
    </div>
    <div class="col-6">
      <div class="form-group">
        <label class="form-label">Logo PDF</label>
        <input type="file" name="pdf_logo" class="form-control" accept=".png,.jpg,.jpeg,.svg,.webp">
        <span class="form-hint">Conseil: PNG transparent, largeur 600px max.</span>
      </div>
    </div>
    @if(!empty($settings['pdf_logo']))
    <div class="col-12">
      <div class="form-group">
        <label class="form-label">Logo actuel</label>
        <div style="display:flex;align-items:center;gap:14px;padding:10px;border:1px solid var(--c-ink-05);border-radius:var(--r-md);background:var(--surface-1);">
          <img src="{{ asset('storage/' . ltrim($settings['pdf_logo'], '/')) }}" alt="Logo PDF" style="max-height:56px;max-width:220px;">
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--c-ink-60);cursor:pointer;">
            <input type="checkbox" name="pdf_logo_remove" value="1"> Supprimer le logo
          </label>
        </div>
      </div>
    </div>
    @endif
    <div class="col-12">
      <div class="form-group">
        <label class="form-label">Texte de pied de page PDF (optionnel)</label>
        <input type="text" name="pdf_footer" class="form-control" placeholder="{{ config('app.name') }} Ã¢â‚¬â€ SIRET / TVA / CoordonnÃƒÂ©es" value="{{ $settings['pdf_footer'] ?? '' }}">
      </div>
    </div>
  </div>

  <div class="settings-row">
    <div class="settings-row-info">
      <h4>Afficher le logo dans l'en-tÃƒÂªte PDF</h4>
      <p>Sinon, le PDF affiche juste le nom de l'entreprise.</p>
    </div>
    <label class="toggle-switch">
      <input type="checkbox" name="pdf_show_logo" {{ ($settings['pdf_show_logo'] ?? true) ? 'checked' : '' }}>
      <span class="toggle-slider"></span>
    </label>
  </div>

  <div class="settings-row">
    <div class="settings-row-info">
      <h4>Afficher le pied de page PDF</h4>
      <p>Utilise le texte optionnel configurÃƒÂ© ci-dessus.</p>
    </div>
    <label class="toggle-switch">
      <input type="checkbox" name="pdf_show_footer" {{ ($settings['pdf_show_footer'] ?? true) ? 'checked' : '' }}>
      <span class="toggle-slider"></span>
    </label>
  </div>
</div>

{{-- Save button --}}
<div class="form-section" style="margin-top:0;display:flex;justify-content:flex-end;gap:10px;padding:20px 28px;">
  <button type="reset" class="btn btn-secondary">
    <i class="fas fa-rotate-left"></i> Annuler
  </button>
  <button type="submit" class="btn btn-primary" id="saveBtn">
    <i class="fas fa-check"></i> Enregistrer les paramÃ¨tres
  </button>
</div>

</form>

@endsection

@push('scripts')
<script>
// Tabs
function switchTab(id) {
  document.querySelectorAll('.tab-panel').forEach(el => el.style.display = 'none');
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.style.background = 'transparent';
    btn.style.color = 'var(--c-ink-60)';
  });
  document.getElementById('tab-' + id).style.display = '';
  const activeBtn = document.getElementById('tab-btn-' + id);
  if (activeBtn) { activeBtn.style.background = 'var(--c-accent)'; activeBtn.style.color = '#fff'; }
}

// Signature pad
let isDrawing = false, lastX = 0, lastY = 0;
const canvas = document.getElementById('signaturePad');
const ctx    = canvas ? canvas.getContext('2d') : null;

if (ctx) {
  ctx.strokeStyle = '#0f172a';
  ctx.lineWidth   = 2.5;
  ctx.lineCap     = 'round';

  const getPos = (e) => {
    const rect = canvas.getBoundingClientRect();
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    return { x: (clientX - rect.left) * (canvas.width / rect.width), y: (clientY - rect.top) * (canvas.height / rect.height) };
  };

  const startDraw = (e) => { e.preventDefault(); isDrawing = true; const p = getPos(e); lastX = p.x; lastY = p.y; };
  const draw = (e) => {
    if (!isDrawing) return; e.preventDefault();
    const p = getPos(e);
    ctx.beginPath(); ctx.moveTo(lastX, lastY); ctx.lineTo(p.x, p.y); ctx.stroke();
    lastX = p.x; lastY = p.y;
  };
  const stopDraw = () => { isDrawing = false; };

  canvas.addEventListener('mousedown',  startDraw);
  canvas.addEventListener('mousemove',  draw);
  canvas.addEventListener('mouseup',    stopDraw);
  canvas.addEventListener('mouseleave', stopDraw);
  canvas.addEventListener('touchstart', startDraw, { passive: false });
  canvas.addEventListener('touchmove',  draw,      { passive: false });
  canvas.addEventListener('touchend',   stopDraw);
}

function clearSignature() {
  if (!ctx) return;
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  document.getElementById('signature_data').value = '';
}

function saveSignature() {
  if (!canvas) return;
  document.getElementById('signature_data').value = canvas.toDataURL('image/png');
  Toast.success('Signature sauvegardÃ©e', 'Elle sera utilisÃ©e sur vos documents.');
}

// Tax rate management
function addTaxRate() {
  const rate = prompt('Entrez un taux TVA (%) :');
  if (rate === null || isNaN(rate) || rate < 0 || rate > 100) return;
  const r = parseFloat(rate);
  const list = document.getElementById('taxRatesList');
  const item = document.createElement('div');
  item.className = 'tax-rate-item';
  item.id = `tax-${r}`;
  item.innerHTML = `
    <div class="tax-badge">${r} %</div>
    <span style="flex:1;font-size:13px;color:var(--c-ink-60);">Taux personnalisÃ©</span>
    <input type="hidden" name="tax_rates[]" value="${r}">
    <button type="button" class="btn-icon danger btn-sm" onclick="removeTaxRate(${r})"><i class="fas fa-times"></i></button>
  `;
  list.appendChild(item);
}

function removeTaxRate(rate) {
  document.getElementById(`tax-${rate}`)?.remove();
}

// Form submit (multipart: logo upload + booleans)
document.getElementById('settingsForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();

  const form = e.currentTarget;
  const btn = document.getElementById('saveBtn');
  if (!form) return;

  CrmForm.clearErrors(form);
  if (btn) CrmForm.setLoading(btn, true);

  const formData = new FormData(form);

  // Laravel method spoofing while keeping multipart request
  formData.set('_method', 'PUT');

  const { ok, data } = await Http.post(form.action, formData);

  if (btn) CrmForm.setLoading(btn, false);

  if (ok && data.success) {
    Toast.success('Parametres sauvegardes', data.message || 'Vos preferences ont ete enregistrees.');
    return;
  }

  if (data?.errors) {
    CrmForm.showErrors(form, data.errors);
    Toast.error('Validation', 'Merci de corriger les champs en erreur.');
    return;
  }

  Toast.error('Erreur', data?.message || 'Impossible de sauvegarder les parametres.');
});
</script>
@endpush

