@extends('client::layouts.crm')

@section('title', $client->company_name)

@section('breadcrumb')
  <a href="{{ route('clients.index') }}">Clients</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $client->company_name }}</span>
@endsection

@section('content')

{{-- Page Header --}}
<div class="page-header">
  <div class="page-header-left" style="display:flex;align-items:center;gap:16px;">
    @php
      $colors = ['#2563eb','#7c3aed','#0891b2','#059669','#d97706'];
      $color  = $colors[ord($client->company_name[0] ?? 'A') % count($colors)];
    @endphp
    <div style="width:56px;height:56px;border-radius:var(--r-md);background:{{ $color }};color:#fff;display:flex;align-items:center;justify-content:center;font-family:var(--ff-display);font-size:20px;font-weight:700;flex-shrink:0;">
      {{ strtoupper(substr($client->company_name, 0, 2)) }}
    </div>
    <div>
      <h1 style="margin-bottom:6px;">{{ $client->company_name }}</h1>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <span class="badge badge-{{ $client->status }}">{{ $client->status_label }}</span>
        <span class="badge badge-{{ $client->type }}">{{ $client->type_label }}</span>
        @if($client->source)
          <span style="font-size:12px;color:var(--c-ink-40)"><i class="fas fa-arrow-right-to-bracket" style="margin-right:4px;"></i>{{ $client->source_label }}</span>
        @endif
        <span style="font-size:12px;color:var(--c-ink-40)"><i class="fas fa-calendar" style="margin-right:4px;"></i>Client depuis {{ $client->created_at->format('M Y') }}</span>
      </div>
    </div>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('clients.edit', $client) }}" class="btn btn-primary">
      <i class="fas fa-pen"></i> Modifier
    </a>
    <div class="dropdown">
      <button class="btn btn-secondary" data-dropdown-toggle>
        <i class="fas fa-ellipsis"></i>
      </button>
      <div class="dropdown-menu">
        <a href="{{ route('clients.edit', $client) }}" class="dropdown-item">
          <i class="fas fa-pen"></i> Modifier
        </a>
        <div class="dropdown-divider"></div>
        <button class="dropdown-item danger" onclick="deleteThisClient()">
          <i class="fas fa-trash"></i> Supprimer
        </button>
      </div>
    </div>
  </div>
</div>

{{-- KPI row --}}
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-euro-sign"></i></div>
    <div class="stat-body">
      <div class="stat-value">{{ number_format($client->revenue ?? 0, 0, ',', ' ') }} €</div>
      <div class="stat-label">Chiffre d'affaires</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#f3e8ff;color:#7c3aed"><i class="fas fa-star"></i></div>
    <div class="stat-body">
      <div class="stat-value">{{ number_format($client->potential_value ?? 0, 0, ',', ' ') }} €</div>
      <div class="stat-label">Potentiel</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-users"></i></div>
    <div class="stat-body">
      <div class="stat-value">{{ $client->employee_count ?? '—' }}</div>
      <div class="stat-label">Employés</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-calendar-check"></i></div>
    <div class="stat-body">
      <div class="stat-value" style="font-size:16px;">
        {{ $client->next_follow_up_at ? $client->next_follow_up_at->format('d M') : '—' }}
      </div>
      <div class="stat-label">Prochain suivi</div>
      @if($client->next_follow_up_at && $client->next_follow_up_at->isPast())
        <span class="stat-trend down"><i class="fas fa-exclamation"></i> En retard</span>
      @elseif($client->next_follow_up_at)
        <span class="stat-trend up"><i class="fas fa-clock"></i> Dans {{ $client->next_follow_up_at->diffForHumans() }}</span>
      @endif
    </div>
  </div>
</div>

<div class="row" style="align-items:flex-start;">

  {{-- Left column --}}
  <div class="col-8" style="padding:0 12px 0 0;">

    {{-- Contact info --}}
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header">
        <i class="fas fa-address-card"></i>
        <h3>Coordonnées</h3>
      </div>
      <div class="info-card-body">
        @if($client->contact_name)
        <div class="info-row">
          <span class="info-row-label"><i class="fas fa-user" style="color:var(--c-accent);margin-right:6px;width:14px;text-align:center;"></i>Contact</span>
          <span class="info-row-value fw-medium">{{ $client->contact_name }}</span>
        </div>
        @endif
        <div class="info-row">
          <span class="info-row-label"><i class="fas fa-envelope" style="color:var(--c-accent);margin-right:6px;width:14px;text-align:center;"></i>Email</span>
          <span class="info-row-value"><a href="mailto:{{ $client->email }}" style="color:var(--c-accent);text-decoration:none;">{{ $client->email }}</a></span>
        </div>
        @if($client->phone)
        <div class="info-row">
          <span class="info-row-label"><i class="fas fa-phone" style="color:var(--c-accent);margin-right:6px;width:14px;text-align:center;"></i>Téléphone</span>
          <span class="info-row-value"><a href="tel:{{ $client->phone }}" style="color:inherit;text-decoration:none;">{{ $client->phone }}</a></span>
        </div>
        @endif
        @if($client->mobile)
        <div class="info-row">
          <span class="info-row-label"><i class="fas fa-mobile" style="color:var(--c-accent);margin-right:6px;width:14px;text-align:center;"></i>Mobile</span>
          <span class="info-row-value"><a href="tel:{{ $client->mobile }}" style="color:inherit;text-decoration:none;">{{ $client->mobile }}</a></span>
        </div>
        @endif
        @if($client->website)
        <div class="info-row">
          <span class="info-row-label"><i class="fas fa-globe" style="color:var(--c-accent);margin-right:6px;width:14px;text-align:center;"></i>Site web</span>
          <span class="info-row-value"><a href="{{ $client->website }}" target="_blank" rel="noopener" style="color:var(--c-accent);text-decoration:none;">{{ $client->website }}</a></span>
        </div>
        @endif
        @if($client->full_address)
        <div class="info-row">
          <span class="info-row-label"><i class="fas fa-location-dot" style="color:var(--c-accent);margin-right:6px;width:14px;text-align:center;"></i>Adresse</span>
          <span class="info-row-value">{{ $client->full_address }}</span>
        </div>
        @endif
      </div>
    </div>

    {{-- Notes --}}
    @if($client->notes)
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header">
        <i class="fas fa-note-sticky"></i>
        <h3>Notes internes</h3>
      </div>
      <div class="info-card-body">
        <p style="font-size:13.5px;color:var(--c-ink-60);line-height:1.7;margin:0;">{{ $client->notes }}</p>
      </div>
    </div>
    @endif

    {{-- Tags --}}
    @if(!empty($client->tags))
    <div class="info-card">
      <div class="info-card-header">
        <i class="fas fa-tags"></i>
        <h3>Tags</h3>
      </div>
      <div class="info-card-body" style="display:flex;flex-wrap:wrap;gap:8px;">
        @foreach($client->tags as $tag)
          <span class="badge" style="background:var(--c-accent-lt);color:var(--c-accent);font-size:12px;">{{ $tag }}</span>
        @endforeach
      </div>
    </div>
    @endif

  </div>

  {{-- Right column --}}
  <div class="col-4" style="padding:0 0 0 12px;">

    {{-- Commercial info --}}
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header">
        <i class="fas fa-chart-bar"></i>
        <h3>Informations commerciales</h3>
      </div>
      <div class="info-card-body">
        <div class="info-row">
          <span class="info-row-label">Type</span>
          <span class="badge badge-{{ $client->type }}">{{ $client->type_label }}</span>
        </div>
        <div class="info-row">
          <span class="info-row-label">Statut</span>
          <span class="badge badge-{{ $client->status }}">{{ $client->status_label }}</span>
        </div>
        @if($client->source)
        <div class="info-row">
          <span class="info-row-label">Source</span>
          <span class="info-row-value">{{ $client->source_label }}</span>
        </div>
        @endif
        @if($client->industry)
        <div class="info-row">
          <span class="info-row-label">Secteur</span>
          <span class="info-row-value">{{ $client->industry }}</span>
        </div>
        @endif
        @if($client->payment_term)
        <div class="info-row">
          <span class="info-row-label">Délai paiement</span>
          <span class="info-row-value">{{ ['immediate'=>'Immédiat','15j'=>'15 jours','30j'=>'30 jours','45j'=>'45 jours','60j'=>'60 jours'][$client->payment_term] ?? $client->payment_term }}</span>
        </div>
        @endif
        @if($client->vat_number)
        <div class="info-row">
          <span class="info-row-label">N° TVA</span>
          <span class="info-row-value" style="font-family:monospace;font-size:12px;">{{ $client->vat_number }}</span>
        </div>
        @endif
        @if($client->siret)
        <div class="info-row">
          <span class="info-row-label">SIRET</span>
          <span class="info-row-value" style="font-family:monospace;font-size:12px;">{{ $client->siret }}</span>
        </div>
        @endif
        <div class="info-row">
          <span class="info-row-label">Créé le</span>
          <span class="info-row-value">{{ $client->created_at->format('d/m/Y') }}</span>
        </div>
        @if($client->last_contact_at)
        <div class="info-row">
          <span class="info-row-label">Dernier contact</span>
          <span class="info-row-value">{{ $client->last_contact_at->format('d/m/Y') }}</span>
        </div>
        @endif
      </div>
    </div>

    {{-- Quick actions --}}
    <div class="info-card">
      <div class="info-card-header">
        <i class="fas fa-bolt"></i>
        <h3>Actions rapides</h3>
      </div>
      <div class="info-card-body" style="display:flex;flex-direction:column;gap:8px;">
        <a href="mailto:{{ $client->email }}" class="btn btn-secondary" style="justify-content:flex-start;">
          <i class="fas fa-envelope"></i> Envoyer un email
        </a>
        @if($client->phone)
        <a href="tel:{{ $client->phone }}" class="btn btn-secondary" style="justify-content:flex-start;">
          <i class="fas fa-phone"></i> Appeler
        </a>
        @endif
        <a href="{{ route('clients.edit', $client) }}" class="btn btn-secondary" style="justify-content:flex-start;">
          <i class="fas fa-pen"></i> Modifier le profil
        </a>
        <button class="btn btn-secondary" style="justify-content:flex-start;color:var(--c-danger);border-color:var(--c-danger-lt);" onclick="deleteThisClient()">
          <i class="fas fa-trash"></i> Supprimer
        </button>
      </div>
    </div>

  </div>
</div>

@endsection

@push('scripts')
<script>
async function deleteThisClient() {
  Modal.confirm({
    title:       'Supprimer ce client ?',
    message:     'Vous allez supprimer "{{ addslashes($client->company_name) }}". Cette action est irréversible.',
    confirmText: 'Supprimer',
    type:        'danger',
    onConfirm:   async () => {
      const { ok, data } = await Http.delete('{{ route("clients.destroy", $client) }}');
      if (ok) {
        Toast.success('Client supprimé', data.message);
        setTimeout(() => window.location.href = '{{ route("clients.index") }}', 900);
      } else {
        Toast.error('Erreur', data.message || 'Impossible de supprimer ce client.');
      }
    },
  });
}
</script>
@endpush
