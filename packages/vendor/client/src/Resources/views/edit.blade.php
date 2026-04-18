@extends('client::layouts.crm')

@section('title', 'Modifier — ' . $client->company_name)

@section('breadcrumb')
  <a href="{{ route('clients.index') }}">Clients</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <a href="{{ route('clients.show', $client) }}">{{ $client->company_name }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Modifier</span>
@endsection

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Modifier le client</h1>
    <p>{{ $client->company_name }}</p>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('clients.show', $client) }}" class="btn btn-secondary">
      <i class="fas fa-arrow-left"></i> Retour
    </a>
  </div>
</div>

{{-- Status banner --}}
<div style="background:var(--c-ink-02);border:1px solid var(--c-ink-05);border-radius:var(--r-md);padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:13px;color:var(--c-ink-60);">
  <i class="fas fa-circle-info" style="color:var(--c-accent)"></i>
  Les modifications seront enregistrées immédiatement. Statut actuel :
  <span class="badge badge-{{ $client->status }}">{{ $client->status_label }}</span>
</div>

<form id="clientForm" action="{{ route('clients.update', $client) }}" method="POST">
  @csrf
  @method('PUT')

  <div class="row" style="align-items:flex-start;">

    {{-- Main column --}}
    <div class="col-8" style="padding:0 12px 0 0;">

      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-building"></i> Informations générales</h3>
        <div class="row">
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Nom de l'entreprise <span class="required">*</span></label>
              <div class="input-group">
                <i class="fas fa-building input-icon"></i>
                <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $client->company_name) }}">
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Email <span class="required">*</span></label>
              <div class="input-group">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" name="email" class="form-control" value="{{ old('email', $client->email) }}">
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Personne de contact</label>
              <div class="input-group">
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="contact_name" class="form-control" value="{{ old('contact_name', $client->contact_name) }}">
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Téléphone</label>
              <div class="input-group">
                <i class="fas fa-phone input-icon"></i>
                <input type="tel" name="phone" class="form-control" value="{{ old('phone', $client->phone) }}">
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Mobile</label>
              <div class="input-group">
                <i class="fas fa-mobile input-icon"></i>
                <input type="tel" name="mobile" class="form-control" value="{{ old('mobile', $client->mobile) }}">
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Site web</label>
              <div class="input-group">
                <i class="fas fa-globe input-icon"></i>
                <input type="url" name="website" class="form-control" value="{{ old('website', $client->website) }}">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-location-dot"></i> Adresse</h3>
        <div class="row">
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Adresse</label>
              <input type="text" name="address" class="form-control" value="{{ old('address', $client->address) }}">
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">Ville</label>
              <input type="text" name="city" class="form-control" value="{{ old('city', $client->city) }}">
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">Code postal</label>
              <input type="text" name="postal_code" class="form-control" value="{{ old('postal_code', $client->postal_code) }}">
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">Pays</label>
              <input type="text" name="country" class="form-control" value="{{ old('country', $client->country) }}">
            </div>
          </div>
        </div>
      </div>

      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-receipt"></i> Informations fiscales</h3>
        <div class="row">
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">N° TVA</label>
              <input type="text" name="vat_number" class="form-control" value="{{ old('vat_number', $client->vat_number) }}">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">SIRET</label>
              <input type="text" name="siret" class="form-control" value="{{ old('siret', $client->siret) }}">
            </div>
          </div>
        </div>
      </div>

      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-note-sticky"></i> Notes internes</h3>
        <div class="form-group">
          <textarea name="notes" class="form-control" rows="4">{{ old('notes', $client->notes) }}</textarea>
        </div>
      </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-4" style="padding:0 0 0 12px;">

      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title"><i class="fas fa-tag"></i> Catégorisation</h3>
        <div class="form-group">
          <label class="form-label">Type <span class="required">*</span></label>
          <select name="type" class="form-control">
            @foreach($types as $key => $label)
              <option value="{{ $key }}" {{ old('type', $client->type) === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Statut <span class="required">*</span></label>
          <select name="status" class="form-control">
            @foreach($statuses as $key => $label)
              <option value="{{ $key }}" {{ old('status', $client->status) === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Source</label>
          <select name="source" class="form-control">
            <option value="">Sélectionner…</option>
            @foreach($sources as $key => $label)
              <option value="{{ $key }}" {{ old('source', $client->source) === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Secteur d'activité</label>
          <input type="text" name="industry" class="form-control" value="{{ old('industry', $client->industry) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Tags</label>
          <div class="tags-input-wrap" id="tags_wrap" data-tags-input="tags">
            @foreach($client->tags ?? [] as $tag)
              <span class="tag-chip">{{ $tag }}<button type="button">×</button></span>
              <input type="hidden" name="tags[]" value="{{ $tag }}">
            @endforeach
            <input type="text" class="tags-input" placeholder="Ajouter un tag…">
          </div>
        </div>
      </div>

      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title"><i class="fas fa-chart-line"></i> Finances</h3>
        <div class="form-group">
          <label class="form-label">Chiffre d'affaires (€)</label>
          <div class="input-group input-right">
            <input type="number" name="revenue" class="form-control" value="{{ old('revenue', $client->revenue) }}" min="0" step="100">
            <i class="fas fa-euro-sign input-icon"></i>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Valeur potentielle (€)</label>
          <div class="input-group input-right">
            <input type="number" name="potential_value" class="form-control" value="{{ old('potential_value', $client->potential_value) }}" min="0" step="100">
            <i class="fas fa-euro-sign input-icon"></i>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Délai de paiement</label>
          <select name="payment_term" class="form-control">
            @foreach(['immediate'=>'Immédiat','15j'=>'15 jours','30j'=>'30 jours','45j'=>'45 jours','60j'=>'60 jours'] as $v => $l)
              <option value="{{ $v }}" {{ old('payment_term', $client->payment_term) === $v ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Nombre d'employés</label>
          <input type="number" name="employee_count" class="form-control" value="{{ old('employee_count', $client->employee_count) }}" min="0">
        </div>
      </div>

      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-calendar-check"></i> Suivi</h3>
        <div class="form-group">
          <label class="form-label">Prochain suivi le</label>
          <input type="date" name="next_follow_up_at" class="form-control"
                 value="{{ old('next_follow_up_at', $client->next_follow_up_at?->format('Y-m-d')) }}">
        </div>
        <div style="background:var(--surface-1);border-radius:var(--r-sm);padding:10px 12px;font-size:12px;color:var(--c-ink-40);">
          <div>Créé le : {{ $client->created_at->format('d/m/Y à H:i') }}</div>
          @if($client->updated_at->ne($client->created_at))
          <div>Modifié le : {{ $client->updated_at->format('d/m/Y à H:i') }}</div>
          @endif
        </div>
      </div>

    </div>
  </div>

  <div class="form-section" style="margin-top:0;">
    <div class="form-actions" style="padding-top:0">
      <a href="{{ route('clients.show', $client) }}" class="btn btn-secondary">
        <i class="fas fa-times"></i> Annuler
      </a>
      <button type="submit" class="btn btn-primary" id="submitBtn">
        <i class="fas fa-check"></i> Enregistrer les modifications
      </button>
    </div>
  </div>

</form>

@endsection

@push('scripts')
<script>
ajaxForm('clientForm', {
  onSuccess: (data) => {
    Toast.success('Client mis à jour !', 'Les modifications ont été enregistrées.');
  }
});
</script>
@endpush
