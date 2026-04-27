@extends('google-drive::layouts.drive')

@section('title', 'Google Drive')

@section('gd_breadcrumb')
  <a href="{{ route('marketplace.index') }}">Applications</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Google Drive</span>
@endsection

@section('gd_content')
<div class="page-header">
  <div class="page-header-left">
    <h1>Google Drive</h1>
    <p>Manage tenant files, folders, sharing, and trash directly from Google Drive.</p>
  </div>
  <div class="page-header-actions">
    @if(!$storageReady)
      <button class="btn btn-warning" disabled><i class="fas fa-database"></i> Migration Required</button>
    @elseif(!$extensionActive)
      <a href="{{ route('marketplace.show', 'google-drive') }}" class="btn btn-primary"><i class="fas fa-store"></i> Activate from Marketplace</a>
    @elseif($connected)
      @if(!empty($dropboxInstalled))
      <a href="{{ route('dropbox.index') }}" class="btn btn-secondary"><i class="fab fa-dropbox"></i> Ouvrir Dropbox</a>
      @endif
      <button class="btn btn-secondary" id="gdRefreshBtn"><i class="fas fa-rotate"></i> Refresh</button>
      <button class="btn btn-secondary" id="gdTrashBtn"><i class="fas fa-trash"></i> Trash</button>
      <button class="btn btn-primary" id="gdCreateFolderBtn" data-modal-open="gdFolderModal"><i class="fas fa-folder-plus"></i> New Folder</button>
      <label class="btn btn-primary" for="gdUploadInput"><i class="fas fa-upload"></i> Upload</label>
      <input type="file" id="gdUploadInput" style="display:none;">
      <button class="btn btn-danger" id="gdDisconnectBtn"><i class="fas fa-link-slash"></i> Disconnect</button>
    @else
      <a href="{{ route('google-drive.oauth.connect') }}" class="btn btn-primary"><i class="fab fa-google-drive"></i> Connect Google Drive</a>
    @endif
  </div>
</div>

@if(!$storageReady)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-database"></i><h3>Database Migration Required</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Google Drive tables are missing. Run migration before using this module.</p>
    <div style="background:var(--surface-2);border:1px solid var(--c-ink-05);border-radius:var(--r-sm);padding:10px 12px;font-family:monospace;font-size:12px;color:var(--c-ink-80);">php artisan migrate</div>
  </div>
</div>
@elseif(!$extensionActive)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-lock"></i><h3>Extension Not Activated</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Google Drive is installed but not activated for this tenant. Activate from Marketplace first.</p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="{{ route('marketplace.show', 'google-drive') }}" class="btn btn-primary"><i class="fas fa-store"></i> Open Application Page</a>
      <a href="{{ route('marketplace.index') }}" class="btn btn-secondary"><i class="fas fa-puzzle-piece"></i> Browse Applications</a>
    </div>
  </div>
</div>
@elseif(!$connected)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fab fa-google-drive"></i><h3>Google Drive Connection</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">This tenant has not connected Google Drive yet. Connect OAuth to access all file manager features.</p>
    <a href="{{ route('google-drive.oauth.connect') }}" class="btn btn-primary"><i class="fab fa-google"></i> Connect Now</a>
  </div>
</div>
@else
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-hard-drive"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gdUsedStorage">0 GB</div>
      <div class="stat-label">Used Storage</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-database"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gdTotalStorage">0 GB</div>
      <div class="stat-label">Total Storage</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-folder-open"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gdCurrentFolderName">Root</div>
      <div class="stat-label">Current Folder</div>
    </div>
  </div>
</div>

@if(!empty($dropboxInstalled))
<div class="info-card" style="margin-bottom:20px;">
  <div class="info-card-header"><i class="fab fa-dropbox"></i><h3>Dropbox est aussi disponible</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Si certains documents doivent plutot vivre dans Dropbox, ouvrez l'autre application de stockage pour utiliser ce second espace.</p>
    <a href="{{ route('dropbox.index') }}" class="btn btn-secondary"><i class="fab fa-dropbox"></i> Basculer vers Dropbox</a>
  </div>
</div>
@endif

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Files</span>
    <span class="table-count" id="gdCount">0 result(s)</span>
    <div class="table-spacer"></div>

    <div class="table-search">
      <i class="fas fa-search"></i>
      <input type="text" id="gdSearchInput" placeholder="Search file/folder...">
    </div>
    <button class="btn btn-ghost btn-sm" id="gdBackBtn"><i class="fas fa-arrow-left"></i> Back</button>
  </div>

  <table class="crm-table">
    <thead>
      <tr>
        <th>Name</th>
        <th>Type</th>
        <th>Size</th>
        <th>Modified</th>
        <th style="text-align:right;padding-right:20px;">Actions</th>
      </tr>
    </thead>
    <tbody id="gdFilesTableBody"></tbody>
  </table>
</div>
@endif

<div class="modal-overlay" id="gdFolderModal">
  <div class="modal modal-sm">
    <div class="modal-header">
      <div class="modal-title">Create Folder</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Folder Name</label>
        <input type="text" class="form-control" id="gdFolderName" maxlength="500" placeholder="My Folder">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Cancel</button>
      <button class="btn btn-primary" id="gdSaveFolderBtn"><i class="fas fa-check"></i> Save</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="gdTrashModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Trash</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <table class="crm-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Modified</th>
            <th style="text-align:right;padding-right:20px;">Actions</th>
          </tr>
        </thead>
        <tbody id="gdTrashTableBody"></tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button class="btn btn-danger" id="gdEmptyTrashBtn"><i class="fas fa-trash-can"></i> Empty Trash</button>
      <button class="btn btn-secondary" data-modal-close>Close</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
window.GDRIVE_ROUTES = {
  connect: '{{ route('google-drive.oauth.connect') }}',
  disconnect: '{{ route('google-drive.oauth.disconnect') }}',
  filesData: '{{ route('google-drive.files.data') }}',
  stats: '{{ route('google-drive.stats') }}',
  createFolder: '{{ route('google-drive.folders.store') }}',
  upload: '{{ route('google-drive.files.upload') }}',
  trashData: '{{ route('google-drive.trash.data') }}',
  emptyTrash: '{{ route('google-drive.trash.empty') }}',
  search: '{{ route('google-drive.search') }}',
  fileBase: '{{ url('/extensions/google-drive/files') }}',
};

window.GDRIVE_BOOTSTRAP = {
  connected: @json((bool) $connected),
};

document.addEventListener('DOMContentLoaded', function () {
  if (window.GoogleDriveModule) {
    window.GoogleDriveModule.boot(window.GDRIVE_BOOTSTRAP);
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
