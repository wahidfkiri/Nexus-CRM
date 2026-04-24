@extends('google-meet::layouts.meet')

@section('title', 'Google Meet')

@section('gm_breadcrumb')
  <a href="{{ route('marketplace.index') }}">Applications</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Google Meet</span>
@endsection

@section('gm_content')
<div class="page-header">
  <div class="page-header-left">
    <h1>Google Meet</h1>
    <p>Planifiez et gerez vos reunions Meet avec OAuth Google.</p>
  </div>
  <div class="page-header-actions">
    @if(!$storageReady)
      <button class="btn btn-warning" disabled>
        <i class="fas fa-database"></i> Migration requise
      </button>
    @elseif(!$extensionActive)
      <a href="{{ route('marketplace.show', 'google-meet') }}" class="btn btn-primary">
        <i class="fas fa-store"></i> Activer depuis Marketplace
      </a>
    @elseif($connected)
      <button class="btn btn-secondary" id="gmSyncBtn">
        <i class="fas fa-rotate"></i> Synchroniser
      </button>
      <button class="btn btn-primary" id="gmCreateMeetingBtn" data-modal-open="gmMeetingModal">
        <i class="fas fa-plus"></i> Nouvelle reunion
      </button>
      <button class="btn btn-danger" id="gmDisconnectBtn">
        <i class="fas fa-link-slash"></i> Deconnecter
      </button>
    @else
      <a href="{{ route('google-meet.oauth.connect') }}" class="btn btn-primary">
        <i class="fab fa-google"></i> Connecter Google Meet
      </a>
    @endif
  </div>
</div>

@if(!$storageReady)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-database"></i><h3>Migration base de donnees requise</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">
      Les tables Google Meet sont absentes. Executez la migration avant d'utiliser ce module.
    </p>
    <div style="background:var(--surface-2);border:1px solid var(--c-ink-05);border-radius:var(--r-sm);padding:10px 12px;font-family:monospace;font-size:12px;color:var(--c-ink-80);margin-bottom:10px;">
      php artisan migrate
    </div>
  </div>
</div>
@elseif(!$extensionActive)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-lock"></i><h3>Extension non activee</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">
      Google Meet est disponible sur la plateforme mais n'est pas encore active pour ce tenant.
      Activez l'application depuis le Marketplace.
    </p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="{{ route('marketplace.show', 'google-meet') }}" class="btn btn-primary"><i class="fas fa-store"></i> Ouvrir l'application</a>
      <a href="{{ route('marketplace.index') }}" class="btn btn-secondary"><i class="fas fa-puzzle-piece"></i> Explorer les applications</a>
    </div>
  </div>
</div>
@elseif(!$connected)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fab fa-google"></i><h3>Connexion Google Meet</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">
      Ce tenant n'a pas encore connecte Google Meet. Lancez l'authentification OAuth pour synchroniser et gerer vos reunions.
    </p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="{{ route('google-meet.oauth.connect') }}" class="btn btn-primary"><i class="fab fa-google"></i> Se connecter</a>
      <a href="{{ route('marketplace.show', 'google-meet') }}" class="btn btn-secondary"><i class="fas fa-store"></i> Ouvrir Marketplace</a>
    </div>
  </div>
</div>
@else
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-calendar"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gmStatCalendars">0</div>
      <div class="stat-label">Calendriers</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-video"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gmStatToday">0</div>
      <div class="stat-label">Aujourd'hui</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-calendar-week"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gmStatWeek">0</div>
      <div class="stat-label">7 prochains jours</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#ede9fe;color:#7c3aed"><i class="fas fa-calendar-days"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gmStatMonth">0</div>
      <div class="stat-label">Ce mois</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fff7ed;color:#c2410c"><i class="fas fa-link"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gmStatLinks">0</div>
      <div class="stat-label">Liens actifs</div>
    </div>
  </div>
</div>

<div class="row" style="align-items:flex-start;">
  <div class="col-3">
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-user-circle"></i><h3>Compte connecte</h3></div>
      <div class="info-card-body">
        <div class="info-row"><span class="info-row-label">Nom</span><span class="info-row-value">{{ $token?->google_name ?? 'Inconnu' }}</span></div>
        <div class="info-row"><span class="info-row-label">Email</span><span class="info-row-value">{{ $token?->google_email ?? '-' }}</span></div>
        <div class="info-row"><span class="info-row-label">Connecte le</span><span class="info-row-value">{{ $token?->connected_at?->format('d/m/Y H:i') ?? '-' }}</span></div>
        <div class="info-row"><span class="info-row-label">Derniere sync</span><span class="info-row-value" id="gmLastSyncLabel">{{ $token?->last_sync_at?->format('d/m/Y H:i') ?? 'Jamais' }}</span></div>
      </div>
    </div>

    <div class="info-card">
      <div class="info-card-header"><i class="fas fa-layer-group"></i><h3>Calendriers</h3></div>
      <div class="info-card-body" style="padding:0;">
        <div id="gmCalendarsList" class="gm-calendar-list"></div>
      </div>
    </div>
  </div>

  <div class="col-9">
    <div class="table-wrapper">
      <div class="table-header">
        <span class="table-title">Reunions Meet</span>
        <span class="table-count" id="gmCount">0 resultat(s)</span>
        <div class="table-spacer"></div>

        <div class="table-search">
          <i class="fas fa-search"></i>
          <input type="text" id="gmSearchInput" placeholder="Rechercher titre, description, organisateur..." autocomplete="off">
        </div>

        <input type="date" class="filter-select" id="gmFromDate" title="Du" style="width:140px;">
        <input type="date" class="filter-select" id="gmToDate" title="Au" style="width:140px;">

        <button class="btn btn-ghost btn-sm" id="gmResetFilters" title="Reinitialiser">
          <i class="fas fa-rotate-left"></i>
        </button>
      </div>

      <table class="crm-table">
        <thead>
          <tr>
            <th>Reunion</th>
            <th>Calendrier</th>
            <th>Debut</th>
            <th>Fin</th>
            <th>Statut</th>
            <th style="text-align:right;padding-right:20px;">Actions</th>
          </tr>
        </thead>
        <tbody id="gmMeetingsTableBody"></tbody>
      </table>

      <div class="table-pagination">
        <span class="pagination-info" id="gmPaginationInfo"></span>
        <div class="pagination-spacer"></div>
        <div class="pagination-pages" id="gmPaginationControls"></div>
      </div>
    </div>
  </div>
</div>
@endif

<div class="modal-overlay" id="gmMeetingModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:var(--c-accent-lt);color:var(--c-accent)">
        <i class="fas fa-video"></i>
      </div>
      <div>
        <div class="modal-title" id="gmMeetingModalTitle">Nouvelle reunion</div>
        <div class="modal-subtitle">Les donnees sont enregistrees dans Google Calendar avec lien Meet.</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <form id="gmMeetingForm">
        <input type="hidden" id="gmMeetingCalendarId" name="calendar_id">
        <input type="hidden" id="gmMeetingEventId" name="event_id">

        <div class="row">
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Titre <span class="required">*</span></label>
              <input type="text" class="form-control" id="gmSummary" name="summary" maxlength="255" required>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Debut <span class="required">*</span></label>
              <input type="datetime-local" class="form-control" id="gmStartAt" name="start_at" required>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Fin <span class="required">*</span></label>
              <input type="datetime-local" class="form-control" id="gmEndAt" name="end_at" required>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Lieu</label>
              <input type="text" class="form-control" id="gmLocation" name="location" maxlength="500" placeholder="Bureau, visio, etc.">
            </div>
          </div>
          <div class="col-3">
            <div class="form-group">
              <label class="form-label">Visibilite</label>
              <select class="form-control" id="gmVisibility" name="visibility">
                <option value="default">Par defaut</option>
                <option value="public">Public</option>
                <option value="private">Prive</option>
                <option value="confidential">Confidentiel</option>
              </select>
            </div>
          </div>
          <div class="col-3">
            <div class="form-group">
              <label class="form-label">Notifications</label>
              <select class="form-control" id="gmSendUpdates" name="send_updates">
                <option value="all">Tous</option>
                <option value="externalOnly">Externes</option>
                <option value="none">Aucune</option>
              </select>
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Participants (`,` ou touche Tab pour valider)</label>
              <div class="gm-tag-input" id="gmParticipantsField">
                <div class="gm-tag-list" id="gmAttendeesBadges"></div>
                <input type="text" class="gm-tag-text" id="gmAttendeesInput" placeholder="Ajouter un email participant...">
              </div>
              <input type="hidden" id="gmAttendees" name="attendees" value="">
            </div>
          </div>
          <div class="col-12">
            <div class="form-group" style="display:flex;align-items:center;gap:8px;">
              <input type="checkbox" id="gmCreateMeetLink" name="create_meet_link" value="1" checked>
              <label class="form-label" style="margin-bottom:0;">Generer un lien Google Meet automatiquement</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea class="form-control" id="gmDescription" name="description" rows="4" maxlength="8000"></textarea>
            </div>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Annuler</button>
      <button class="btn btn-primary" id="gmSaveMeetingBtn">
        <i class="fas fa-check"></i> Enregistrer
      </button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
window.GMEET_ROUTES = {
  connect: '{{ route('google-meet.oauth.connect') }}',
  disconnect: '{{ route('google-meet.oauth.disconnect') }}',
  calendarsData: '{{ route('google-meet.calendars.data') }}',
  selectCalendar: '{{ route('google-meet.calendar.select') }}',
  meetingsData: '{{ route('google-meet.meetings.data') }}',
  meetingsStore: '{{ route('google-meet.meetings.store') }}',
  meetingsBase: '{{ url('/extensions/google-meet/meetings') }}',
  stats: '{{ route('google-meet.stats') }}',
  sync: '{{ route('google-meet.sync') }}',
};

window.GMEET_BOOTSTRAP = {
  connected: @json((bool) $connected),
  selectedCalendarId: @json($token?->selected_calendar_id),
  timezone: @json(config('google-meet.defaults.timezone', 'Europe/Paris')),
  googleCalendarInstalled: @json((bool) $googleCalendarInstalled),
  googleCalendarTargetUrl: @json($googleCalendarTargetUrl),
};

document.addEventListener('DOMContentLoaded', function () {
  if (window.GoogleMeetModule) {
    window.GoogleMeetModule.boot(window.GMEET_BOOTSTRAP);
  }

  @if(session('success'))
  Toast.success('Succes', @json(session('success')));
  @endif

  @if(session('error'))
  Toast.error('Erreur', @json(session('error')));
  @endif
});
</script>
@endpush
