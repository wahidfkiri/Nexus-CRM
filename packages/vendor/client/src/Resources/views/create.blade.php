@extends('client::layouts.crm')

@section('title', 'Nouveau client')

@section('breadcrumb')
  <a href="{{ route('clients.index') }}">Clients</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Nouveau client</span>
@endsection

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Nouveau client</h1>
    <p>Ajoutez un nouveau client à votre portefeuille</p>
  </div>
  <a href="{{ route('clients.index') }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> Retour
  </a>
</div>

<form id="clientForm" action="{{ route('clients.store') }}" method="POST">
  @csrf
  <div class="row" style="align-items:flex-start;">

    {{-- Main column --}}
    <div class="col-8" style="padding:0 12px 0 0;">

      {{-- Section : Informations générales --}}
      <div class="form-section">
        <h3 class="form-section-title">
          <i class="fas fa-building"></i> Informations générales
          <span class="form-section-badge">Étape 1/4</span>
        </h3>
        <div class="row">
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Nom de l'entreprise <span class="required">*</span></label>
              <div class="input-group">
                <i class="fas fa-building input-icon"></i>
                <input type="text" name="company_name" class="form-control" placeholder="Acme Corporation" autofocus>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Email <span class="required">*</span></label>
              <div class="input-group">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" name="email" class="form-control" placeholder="contact@acme.com">
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Personne de contact</label>
              <div class="input-group">
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="contact_name" class="form-control" placeholder="Jean Dupont">
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Téléphone</label>
              <div class="input-group">
                <i class="fas fa-phone input-icon"></i>
                <input type="tel" name="phone" class="form-control" placeholder="+33 6 12 34 56 78">
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Mobile</label>
              <div class="input-group">
                <i class="fas fa-mobile input-icon"></i>
                <input type="tel" name="mobile" class="form-control" placeholder="+33 7 98 76 54 32">
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Site web</label>
              <div class="input-group">
                <i class="fas fa-globe input-icon"></i>
                <input type="url" name="website" class="form-control" placeholder="https://acme.com">
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Section : Adresse --}}
      <div class="form-section">
        <h3 class="form-section-title">
          <i class="fas fa-location-dot"></i> Adresse
          <span class="form-section-badge">Étape 2/4</span>
        </h3>
        <div class="row">
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Adresse</label>
              <input type="text" name="address" class="form-control" placeholder="123 rue de la Paix">
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">Ville</label>
              <input type="text" name="city" class="form-control" placeholder="Paris">
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">Code postal</label>
              <input type="text" name="postal_code" class="form-control" placeholder="75001">
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">Pays</label>
              <input type="text" name="country" class="form-control" placeholder="France">
            </div>
          </div>
        </div>
      </div>

      {{-- Section : Informations fiscales --}}
      <div class="form-section">
        <h3 class="form-section-title">
          <i class="fas fa-receipt"></i> Informations fiscales
        </h3>
        <div class="row">
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">N° TVA intracommunautaire</label>
              <input type="text" name="vat_number" class="form-control" placeholder="FR 12 345678901">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">SIRET</label>
              <input type="text" name="siret" class="form-control" placeholder="12345678901234">
            </div>
          </div>
        </div>
      </div>

      {{-- Section : Notes --}}
      <div class="form-section">
        <h3 class="form-section-title">
          <i class="fas fa-note-sticky"></i> Notes internes
        </h3>
        <div class="form-group">
          <textarea name="notes" class="form-control" rows="4" placeholder="Informations complémentaires sur ce client…"></textarea>
          <span class="form-hint">Ces notes sont visibles uniquement par votre équipe.</span>
        </div>
      </div>

    </div>

    {{-- Sidebar column --}}
    <div class="col-4" style="padding:0 0 0 12px;">

      {{-- Catégorisation --}}
      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title">
          <i class="fas fa-tag"></i> Catégorisation
          <span class="form-section-badge">Étape 3/4</span>
        </h3>
        <div class="form-group">
          <label class="form-label">Type de client <span class="required">*</span></label>
          <select name="type" class="form-control">
            @foreach($types as $key => $label)
              <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Statut <span class="required">*</span></label>
          <select name="status" class="form-control">
            @foreach($statuses as $key => $label)
              <option value="{{ $key }}" {{ $key === 'actif' ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Source d'acquisition</label>
          <select name="source" class="form-control">
            <option value="">Sélectionner…</option>
            @foreach($sources as $key => $label)
              <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Secteur d'activité</label>
          <input type="text" name="industry" class="form-control" placeholder="Technologie, Finance…">
        </div>
        <div class="form-group">
          <label class="form-label">Tags</label>
          <div class="tags-input-wrap" id="tags_wrap" data-tags-input="tags">
            <input type="text" class="tags-input" placeholder="Ajouter un tag, Entrée pour valider…">
          </div>
          <span class="form-hint">Appuyez sur Entrée ou virgule pour ajouter.</span>
        </div>
      </div>

      {{-- Finances --}}
      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title">
          <i class="fas fa-chart-line"></i> Finances
          <span class="form-section-badge">Étape 4/4</span>
        </h3>
        <div class="form-group">
          <label class="form-label">Chiffre d'affaires annuel (€)</label>
          <div class="input-group input-right">
            <input type="number" name="revenue" class="form-control" placeholder="0" min="0" step="100">
            <i class="fas fa-euro-sign input-icon"></i>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Valeur potentielle (€)</label>
          <div class="input-group input-right">
            <input type="number" name="potential_value" class="form-control" placeholder="0" min="0" step="100">
            <i class="fas fa-euro-sign input-icon"></i>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Délai de paiement</label>
          <select name="payment_term" class="form-control">
            <option value="">Sélectionner…</option>
            <option value="immediate">Immédiat</option>
            <option value="15j">15 jours</option>
            <option value="30j" selected>30 jours</option>
            <option value="45j">45 jours</option>
            <option value="60j">60 jours</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Nombre d'employés</label>
          <input type="number" name="employee_count" class="form-control" placeholder="Ex: 50" min="0">
        </div>
      </div>

      {{-- Suivi --}}
      <div class="form-section">
        <h3 class="form-section-title">
          <i class="fas fa-calendar-check"></i> Suivi commercial
        </h3>
        <div class="form-group">
          <label class="form-label">Prochain suivi le</label>
          <input type="date" name="next_follow_up_at" class="form-control" min="{{ date('Y-m-d') }}">
        </div>
      </div>

    </div>
  </div>

  {{-- Form Actions --}}
  <div class="form-section" style="margin-top:0;">
    <div class="form-actions" style="padding-top:0">
      <a href="{{ route('clients.index') }}" class="btn btn-secondary">
        <i class="fas fa-times"></i> Annuler
      </a>
      <button type="submit" class="btn btn-primary" id="submitBtn">
        <i class="fas fa-check"></i> Créer le client
      </button>
    </div>
  </div>

</form>

@endsection

@push('scripts')
<script>
ajaxForm('clientForm', {
  onSuccess: (data) => {
    Toast.success('Client créé !', 'Le client a été ajouté à votre portefeuille.', 3000);
  }
});
</script>
@endpush
