@extends('google-sheets::layouts.sheets')

@section('title', 'Google Sheets')

@section('gs_breadcrumb')
  <a href="{{ route('marketplace.index') }}">Applications</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Google Sheets</span>
@endsection

@section('gs_content')
<div class="page-header">
  <div class="page-header-left">
    <h1>Google Sheets</h1>
    <p>Create, edit and manage your spreadsheets directly from Google Sheets.</p>
  </div>
  <div class="page-header-actions">
    @if(!$storageReady)
      <button class="btn btn-warning" disabled><i class="fas fa-database"></i> Migration Required</button>
    @elseif(!$extensionActive)
      <a href="{{ route('marketplace.show', 'google-sheets') }}" class="btn btn-primary"><i class="fas fa-store"></i> Activate from Marketplace</a>
    @elseif($connected)
      <button class="btn btn-secondary" id="gsRefreshBtn"><i class="fas fa-rotate"></i> Refresh</button>
      <button class="btn btn-primary" id="gsCreateBtn" data-modal-open="gsCreateModal"><i class="fas fa-plus"></i> New Spreadsheet</button>
      <button class="btn btn-danger" id="gsDisconnectBtn"><i class="fas fa-link-slash"></i> Disconnect</button>
    @else
      <a href="{{ route('google-sheets.oauth.connect') }}" class="btn btn-primary"><i class="fab fa-google"></i> Connect Google Sheets</a>
    @endif
  </div>
</div>

{{-- ── States ───────────────────────────────────────────────────────── --}}
@if(!$storageReady)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-database"></i><h3>Database Migration Required</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Google Sheets tables are missing. Run migration before using this module.</p>
    <div style="background:var(--surface-2);border:1px solid var(--c-ink-05);border-radius:var(--r-sm);padding:10px 12px;font-family:monospace;font-size:12px;color:var(--c-ink-80);">php artisan migrate</div>
  </div>
</div>

@elseif(!$extensionActive)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-lock"></i><h3>Extension Not Activated</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Google Sheets is installed but not activated for this tenant. Activate from Marketplace first.</p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="{{ route('marketplace.show', 'google-sheets') }}" class="btn btn-primary"><i class="fas fa-store"></i> Open Application Page</a>
      <a href="{{ route('marketplace.index') }}" class="btn btn-secondary"><i class="fas fa-puzzle-piece"></i> Browse Applications</a>
    </div>
  </div>
</div>

@elseif(!$connected)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-file-excel" style="color:#0f9d58;"></i><h3>Google Sheets Connection</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">
      This tenant has not connected Google Sheets yet. Connect via OAuth to access all spreadsheet features.
    </p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="{{ route('google-sheets.oauth.connect') }}" class="btn btn-primary"><i class="fab fa-google"></i> Connect Now</a>
      <a href="{{ route('marketplace.show', 'google-sheets') }}" class="btn btn-secondary"><i class="fas fa-store"></i> Open Marketplace</a>
    </div>
  </div>
</div>

@else
{{-- ── Connected state ─────────────────────────────────────────────────── --}}

<div class="row" style="align-items:flex-start;">

  {{-- Left: Account info --}}
  <div class="col-3">
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-user-circle"></i><h3>Connected Account</h3></div>
      <div class="info-card-body">
        @if($token?->google_avatar_url)
          <div style="text-align:center;margin-bottom:12px;">
            <img src="{{ $token->google_avatar_url }}" style="width:56px;height:56px;border-radius:50%;border:2px solid var(--c-ink-05);" alt="">
          </div>
        @endif
        <div class="info-row"><span class="info-row-label">Name</span><span class="info-row-value">{{ $token?->google_name ?? '—' }}</span></div>
        <div class="info-row"><span class="info-row-label">Email</span><span class="info-row-value" style="font-size:12px;">{{ $token?->google_email ?? '—' }}</span></div>
        <div class="info-row"><span class="info-row-label">Connected</span><span class="info-row-value">{{ $token?->connected_at?->format('d/m/Y H:i') ?? '—' }}</span></div>
        <div class="info-row"><span class="info-row-label">Last Sync</span><span class="info-row-value" id="gsLastSyncLabel">{{ $token?->last_sync_at?->format('d/m/Y H:i') ?? 'Never' }}</span></div>
      </div>
    </div>

    <div class="info-card">
      <div class="info-card-header"><i class="fas fa-chart-bar"></i><h3>Stats</h3></div>
      <div class="info-card-body">
        <div class="stat-card" style="margin-bottom:10px;padding:12px;">
          <div class="stat-icon" style="background:#0f9d5818;color:#0f9d58;"><i class="fas fa-file-excel"></i></div>
          <div class="stat-body">
            <div class="stat-value" id="gsStatSpreadsheets">0</div>
            <div class="stat-label">Spreadsheets</div>
          </div>
        </div>
        <div class="stat-card" style="padding:12px;">
          <div class="stat-icon" style="background:#4285f418;color:#4285f4;"><i class="fas fa-table"></i></div>
          <div class="stat-body">
            <div class="stat-value" id="gsStatSheets">0</div>
            <div class="stat-label">Total Sheets</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Right: Spreadsheets table --}}
  <div class="col-9">
    <div class="table-wrapper">
      <div class="table-header">
        <span class="table-title">Spreadsheets</span>
        <span class="table-count" id="gsCount">0 result(s)</span>
        <div class="table-spacer"></div>
        <div class="table-search">
          <i class="fas fa-search"></i>
          <input type="text" id="gsSearchInput" placeholder="Search spreadsheet…" autocomplete="off">
        </div>
      </div>

      <table class="crm-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Created</th>
            <th>Modified</th>
            <th>Visibility</th>
            <th style="text-align:right;padding-right:20px;">Actions</th>
          </tr>
        </thead>
        <tbody id="gsSpreadsheetsTableBody"></tbody>
      </table>
    </div>
  </div>

</div>
@endif

{{-- ── Modals ─────────────────────────────────────────────────────────── --}}

{{-- Create Spreadsheet --}}
<div class="modal-overlay" id="gsCreateModal">
  <div class="modal modal-sm">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:#0f9d5818;color:#0f9d58;"><i class="fas fa-file-excel"></i></div>
      <div><div class="modal-title">New Spreadsheet</div><div class="modal-subtitle">Create a new Google Sheet</div></div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Title <span class="required">*</span></label>
        <input type="text" class="form-control" id="gsSpreadsheetTitle" maxlength="500" placeholder="My Spreadsheet">
      </div>
      <div class="form-group">
        <label class="form-label">Initial sheet names <span class="hint">(comma-separated)</span></label>
        <input type="text" class="form-control" id="gsSheetTitles" placeholder="Sheet1, Sheet2, Summary">
        <span class="form-hint">Leave empty to start with a single default sheet.</span>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Cancel</button>
      <button class="btn btn-primary" id="gsSaveSpreadsheetBtn"><i class="fas fa-check"></i> Create</button>
    </div>
  </div>
</div>

{{-- Data View / Editor --}}
<div class="modal-overlay" id="gsDataModal">
  <div class="modal modal-xl" style="max-width:92vw;width:92vw;">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:#0f9d5818;color:#0f9d58;"><i class="fas fa-table-cells"></i></div>
      <div>
        <div class="modal-title" id="gsDataModalTitle">Spreadsheet</div>
        <div class="modal-subtitle">Read and write data in your spreadsheet</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body" style="padding:0;">

      {{-- Sheet tabs --}}
      <div style="border-bottom:1px solid var(--c-ink-05);padding:10px 20px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;background:var(--surface-1);">
        <div style="font-size:11.5px;font-weight:700;color:var(--c-ink-40);text-transform:uppercase;letter-spacing:.04em;flex-shrink:0;">Sheets</div>
        <div id="gsSheetTabsLoader" style="display:flex;gap:6px;flex-wrap:wrap;flex:1;"></div>
        <div style="display:flex;gap:6px;margin-left:auto;flex-shrink:0;">
          <input type="text" class="form-control" id="gsNewSheetTitle" placeholder="New sheet name…" style="width:160px;font-size:12.5px;padding:6px 10px;">
          <button class="btn btn-secondary btn-sm" id="gsAddSheetBtn" title="Add sheet"><i class="fas fa-plus"></i></button>
        </div>
      </div>

      {{-- Range toolbar --}}
      <div style="padding:12px 20px;border-bottom:1px solid var(--c-ink-05);display:flex;align-items:center;gap:10px;flex-wrap:wrap;background:var(--surface-0);">
        <div style="display:flex;align-items:center;gap:8px;flex:1;min-width:200px;">
          <label style="font-size:12px;font-weight:600;color:var(--c-ink-40);white-space:nowrap;">Range</label>
          <input type="text" class="form-control gs-range-input" id="gsRangeInput"
                 value="Sheet1!A1:Z50" placeholder="Sheet1!A1:Z50" style="max-width:220px;">
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
          <button class="btn btn-primary btn-sm" id="gsReadRangeBtn"><i class="fas fa-eye"></i> Read</button>
          <button class="btn btn-secondary btn-sm" id="gsWriteRangeBtn"><i class="fas fa-pen"></i> Write</button>
          <button class="btn btn-secondary btn-sm" id="gsAppendRowsBtn"><i class="fas fa-arrow-down"></i> Append</button>
          <button class="btn btn-ghost btn-sm" id="gsClearRangeBtn" style="color:var(--c-danger);"><i class="fas fa-eraser"></i> Clear</button>
        </div>
      </div>

      {{-- Data table --}}
      <div id="gsDataTableWrap" style="padding:16px 20px;min-height:200px;">
        <div style="text-align:center;padding:40px;color:var(--c-ink-40);">
          <i class="fas fa-table-cells" style="font-size:28px;margin-bottom:8px;display:block;opacity:.3;"></i>
          <p>Select a sheet and click <strong>Read</strong> to load data.</p>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Close</button>
    </div>
  </div>
</div>

{{-- Write Modal --}}
<div class="modal-overlay" id="gsWriteModal">
  <div class="modal modal-md">
    <div class="modal-header">
      <div class="modal-title">Write Data</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Range <span class="required">*</span></label>
        <input type="text" class="form-control gs-range-input" id="gsWriteRange" placeholder="Sheet1!A1">
        <span class="form-hint">e.g. <code>Sheet1!A1</code> or <code>Sheet1!B2:D5</code></span>
      </div>
      <div class="form-group">
        <label class="form-label">Data <span class="required">*</span></label>
        <textarea class="form-control" id="gsWriteData" rows="8"
                  placeholder="Paste tab-separated values (TSV). Each line = one row, tab = column separator.&#10;Example:&#10;Name&#9;Age&#9;City&#10;Alice&#9;30&#9;Paris"></textarea>
        <span class="form-hint">Tab-separated values (TSV). Copy-paste directly from Excel or Google Sheets.</span>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Cancel</button>
      <button class="btn btn-primary" id="gsSaveWriteBtn"><i class="fas fa-check"></i> Write Data</button>
    </div>
  </div>
</div>

{{-- Append Modal --}}
<div class="modal-overlay" id="gsAppendModal">
  <div class="modal modal-md">
    <div class="modal-header">
      <div class="modal-title">Append Rows</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Range <span class="required">*</span></label>
        <input type="text" class="form-control gs-range-input" id="gsAppendRange" placeholder="Sheet1!A:A">
        <span class="form-hint">Rows will be appended after the last row with data in this range.</span>
      </div>
      <div class="form-group">
        <label class="form-label">Data to append <span class="required">*</span></label>
        <textarea class="form-control" id="gsAppendData" rows="8"
                  placeholder="Tab-separated values. Each line is a new row.&#10;Bob&#9;25&#9;Lyon&#10;Carol&#9;42&#9;Marseille"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Cancel</button>
      <button class="btn btn-primary" id="gsSaveAppendBtn"><i class="fas fa-arrow-down"></i> Append Rows</button>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
window.GS_ROUTES = {
  connect:            '{{ route('google-sheets.oauth.connect') }}',
  disconnect:         '{{ route('google-sheets.oauth.disconnect') }}',
  spreadsheetsData:   '{{ route('google-sheets.spreadsheets.data') }}',
  createSpreadsheet:  '{{ route('google-sheets.spreadsheets.store') }}',
  stats:              '{{ route('google-sheets.stats') }}',
  spreadsheetBase:    '{{ url('/extensions/google-sheets/spreadsheets') }}',
};

window.GS_BOOTSTRAP = {
  connected: @json((bool) $connected),
};

document.addEventListener('DOMContentLoaded', function () {
  if (window.GoogleSheetsModule) {
    window.GoogleSheetsModule.boot(window.GS_BOOTSTRAP);
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