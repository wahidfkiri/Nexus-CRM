@extends('google-calendar::layouts.calendar')

@section('title', 'Google Calendar')

@section('gc_breadcrumb')
  <a href="{{ route('marketplace.index') }}">Applications</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Google Calendar</span>
@endsection

@section('gc_content')
<div class="page-header">
  <div class="page-header-left">
    <h1>Google Calendar</h1>
    <p>Synchronize calendars and manage tenant events with Google OAuth.</p>
  </div>
  <div class="page-header-actions">
    @if(!$storageReady)
      <button class="btn btn-warning" disabled>
        <i class="fas fa-database"></i> Migration Required
      </button>
    @elseif(!$extensionActive)
      <a href="{{ route('marketplace.show', 'google-calendar') }}" class="btn btn-primary">
        <i class="fas fa-store"></i> Activate from Marketplace
      </a>
    @elseif($connected)
      <button class="btn btn-secondary" id="gcSyncBtn">
        <i class="fas fa-rotate"></i> Sync
      </button>
      <button class="btn btn-primary" id="gcCreateEventBtn" data-modal-open="gcEventModal">
        <i class="fas fa-plus"></i> New Event
      </button>
      <button class="btn btn-danger" id="gcDisconnectBtn">
        <i class="fas fa-link-slash"></i> Disconnect
      </button>
    @else
      <a href="{{ route('google-calendar.oauth.connect') }}" class="btn btn-primary">
        <i class="fab fa-google"></i> Connect Google Calendar
      </a>
    @endif
  </div>
</div>

@if(!$storageReady)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-database"></i><h3>Database Migration Required</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">
      Google Calendar tables are missing. Please run migration before using this module.
    </p>
    <div style="background:var(--surface-2);border:1px solid var(--c-ink-05);border-radius:var(--r-sm);padding:10px 12px;font-family:monospace;font-size:12px;color:var(--c-ink-80);margin-bottom:10px;">
      php artisan migrate
    </div>
  </div>
</div>
@elseif(!$extensionActive)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-lock"></i><h3>Extension Not Activated</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">
      Google Calendar is installed in the platform but not yet activated for this tenant.
      Activate it from Marketplace to enable OAuth connection and event synchronization.
    </p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="{{ route('marketplace.show', 'google-calendar') }}" class="btn btn-primary"><i class="fas fa-store"></i> Open Application Page</a>
      <a href="{{ route('marketplace.index') }}" class="btn btn-secondary"><i class="fas fa-puzzle-piece"></i> Browse Applications</a>
    </div>
  </div>
</div>
@elseif(!$connected)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fab fa-google"></i><h3>Google Calendar Connection</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">
      This tenant has not connected Google Calendar yet. Start OAuth authentication to enable event sync, calendar selection,
      and full CRUD on Google events from this module.
    </p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="{{ route('google-calendar.oauth.connect') }}" class="btn btn-primary"><i class="fab fa-google"></i> Connect Now</a>
      <a href="{{ route('marketplace.show', 'google-calendar') }}" class="btn btn-secondary"><i class="fas fa-store"></i> Open Marketplace</a>
    </div>
  </div>
</div>
@else
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-calendar-days"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gcStatCalendars">0</div>
      <div class="stat-label">Calendars</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-sun"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gcStatToday">0</div>
      <div class="stat-label">Events Today</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-calendar-week"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gcStatMonth">0</div>
      <div class="stat-label">This Month</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#ede9fe;color:#7c3aed"><i class="fas fa-forward"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gcStatNext">0</div>
      <div class="stat-label">Next 30 Days</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fff7ed;color:#c2410c"><i class="fas fa-flag"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gcStatHolidays">0</div>
      <div class="stat-label">Holidays (Year)</div>
    </div>
  </div>
</div>

<div class="row" style="align-items:flex-start;">
  <div class="col-3">
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-user-circle"></i><h3>Connected Account</h3></div>
      <div class="info-card-body">
        <div class="info-row"><span class="info-row-label">Name</span><span class="info-row-value">{{ $token?->google_name ?? 'Unknown' }}</span></div>
        <div class="info-row"><span class="info-row-label">Email</span><span class="info-row-value">{{ $token?->google_email ?? '-' }}</span></div>
        <div class="info-row"><span class="info-row-label">Connected</span><span class="info-row-value">{{ $token?->connected_at?->format('d/m/Y H:i') ?? '-' }}</span></div>
        <div class="info-row"><span class="info-row-label">Last Sync</span><span class="info-row-value" id="gcLastSyncLabel">{{ $token?->last_sync_at?->format('d/m/Y H:i') ?? 'Never' }}</span></div>
      </div>
    </div>

    <div class="info-card">
      <div class="info-card-header"><i class="fas fa-layer-group"></i><h3>Calendars</h3></div>
      <div class="info-card-body" style="padding:0;">
        <div id="gcCalendarsList" class="gc-calendar-list"></div>
      </div>
    </div>
  </div>

  <div class="col-9">
    <div class="table-wrapper">
      <div class="table-header">
        <span class="table-title">Events</span>
        <span class="table-count" id="gcCount">0 result(s)</span>
        <div class="table-spacer"></div>

        <div class="table-search">
          <i class="fas fa-search"></i>
          <input type="text" id="gcSearchInput" placeholder="Search title, description, location..." autocomplete="off">
        </div>

        <input type="date" class="filter-select" id="gcFromDate" title="From" style="width:140px;">
        <input type="date" class="filter-select" id="gcToDate" title="To" style="width:140px;">
        <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--c-ink-60);padding:0 4px;">
          <input type="checkbox" id="gcIncludeHolidays" checked>
          Include Holidays
        </label>

        <button class="btn btn-ghost btn-sm" id="gcResetFilters" title="Reset">
          <i class="fas fa-rotate-left"></i>
        </button>
      </div>

      <table class="crm-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Calendar</th>
            <th>Start</th>
            <th>End</th>
            <th>Status</th>
            <th style="text-align:right;padding-right:20px;">Actions</th>
          </tr>
        </thead>
        <tbody id="gcEventsTableBody"></tbody>
      </table>

      <div class="table-pagination">
        <span class="pagination-info" id="gcPaginationInfo"></span>
        <div class="pagination-spacer"></div>
        <div class="pagination-pages" id="gcPaginationControls"></div>
      </div>
    </div>
  </div>
</div>
@endif

<div class="modal-overlay" id="gcEventModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:var(--c-accent-lt);color:var(--c-accent)">
        <i class="fas fa-calendar-plus"></i>
      </div>
      <div>
        <div class="modal-title" id="gcEventModalTitle">Create Event</div>
        <div class="modal-subtitle">Data is saved on Google Calendar and synced locally.</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <form id="gcEventForm">
        <input type="hidden" id="gcEventCalendarId" name="calendar_id">
        <input type="hidden" id="gcEventId" name="event_id">

        <div class="row">
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Title <span class="required">*</span></label>
              <input type="text" class="form-control" id="gcSummary" name="summary" maxlength="255" required>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Start <span class="required">*</span></label>
              <input type="datetime-local" class="form-control" id="gcStartAt" name="start_at" required>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">End <span class="required">*</span></label>
              <input type="datetime-local" class="form-control" id="gcEndAt" name="end_at" required>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Location</label>
              <input type="text" class="form-control" id="gcLocation" name="location" maxlength="500">
            </div>
          </div>
          <div class="col-3">
            <div class="form-group">
              <label class="form-label">Visibility</label>
              <select class="form-control" id="gcVisibility" name="visibility">
                <option value="default">Default</option>
                <option value="public">Public</option>
                <option value="private">Private</option>
                <option value="confidential">Confidential</option>
              </select>
            </div>
          </div>
          <div class="col-3">
            <div class="form-group">
              <label class="form-label">Reminder (min)</label>
              <input type="number" class="form-control" id="gcReminder" name="reminder_minutes" min="1" max="40320" placeholder="10">
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Attendees (comma-separated emails)</label>
              <input type="text" class="form-control" id="gcAttendees" name="attendees" placeholder="john@company.com, jane@company.com">
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea class="form-control" id="gcDescription" name="description" rows="4" maxlength="8000"></textarea>
            </div>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Cancel</button>
      <button class="btn btn-primary" id="gcSaveEventBtn">
        <i class="fas fa-check"></i> Save Event
      </button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
window.GCAL_ROUTES = {
  connect: '{{ route('google-calendar.oauth.connect') }}',
  disconnect: '{{ route('google-calendar.oauth.disconnect') }}',
  calendarsData: '{{ route('google-calendar.calendars.data') }}',
  selectCalendar: '{{ route('google-calendar.calendar.select') }}',
  eventsData: '{{ route('google-calendar.events.data') }}',
  eventsStore: '{{ route('google-calendar.events.store') }}',
  eventsBase: '{{ url('/extensions/google-calendar/events') }}',
  stats: '{{ route('google-calendar.stats') }}',
  sync: '{{ route('google-calendar.sync') }}',
};

window.GCAL_BOOTSTRAP = {
  connected: @json((bool) $connected),
  selectedCalendarId: @json($token?->selected_calendar_id),
  timezone: @json(config('google-calendar.defaults.timezone', 'UTC')),
  includeHolidays: true,
};

document.addEventListener('DOMContentLoaded', function () {
  if (window.GoogleCalendarModule) {
    window.GoogleCalendarModule.boot(window.GCAL_BOOTSTRAP);
  }

  @if(session('success'))
  Toast.success('Success', @json(session('success')));
  @endif

  @if(session('error'))
  Toast.error('Error', @json(session('error')));
  @endif
});
</script>
@endpush
