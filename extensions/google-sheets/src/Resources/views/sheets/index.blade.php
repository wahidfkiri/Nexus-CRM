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
    <p>Créez, éditez et gérez vos feuilles de calcul directement depuis Google Sheets.</p>
  </div>
  <div class="page-header-actions">
    @if(!$storageReady)
      <button class="btn btn-warning" disabled><i class="fas fa-database"></i> Migration requise</button>
    @elseif(!$extensionActive)
      <a href="{{ route('marketplace.show', 'google-sheets') }}" class="btn btn-primary"><i class="fas fa-store"></i> Activer depuis le Marketplace</a>
    @elseif($connected)
      <button class="btn btn-secondary" id="gsRefreshBtn"><i class="fas fa-rotate"></i> Actualiser</button>
      <button class="btn btn-primary" id="gsCreateBtn" data-modal-open="gsCreateModal"><i class="fas fa-plus"></i> Nouvelle feuille</button>
      <button class="btn btn-danger" id="gsDisconnectBtn"><i class="fas fa-link-slash"></i> Déconnecter</button>
    @else
      <a href="{{ route('google-sheets.oauth.connect') }}" class="btn btn-primary"><i class="fab fa-google"></i> Connecter Google Sheets</a>
    @endif
  </div>
</div>

{{-- ── States ───────────────────────────────────────────────────────── --}}
@if(!$storageReady)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-database"></i><h3>Migration base de données requise</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Les tables Google Sheets sont absentes. Lancez la migration avant d’utiliser ce module.</p>
    <div style="background:var(--surface-2);border:1px solid var(--c-ink-05);border-radius:var(--r-sm);padding:10px 12px;font-family:monospace;font-size:12px;color:var(--c-ink-80);">php artisan migrate</div>
  </div>
</div>

@elseif(!$extensionActive)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-lock"></i><h3>Application non activée</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Google Sheets est installé mais non activé pour ce tenant. Activez d’abord l’application dans le Marketplace.</p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="{{ route('marketplace.show', 'google-sheets') }}" class="btn btn-primary"><i class="fas fa-store"></i> Ouvrir la fiche application</a>
      <a href="{{ route('marketplace.index') }}" class="btn btn-secondary"><i class="fas fa-puzzle-piece"></i> Parcourir les applications</a>
    </div>
  </div>
</div>

@elseif(!$connected)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-file-excel" style="color:#0f9d58;"></i><h3>Connexion Google Sheets</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">
      Ce tenant n’a pas encore connecté Google Sheets. Connectez-vous via OAuth pour activer toutes les fonctionnalités.
    </p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="{{ route('google-sheets.oauth.connect') }}" class="btn btn-primary"><i class="fab fa-google"></i> Connecter maintenant</a>
      <a href="{{ route('marketplace.show', 'google-sheets') }}" class="btn btn-secondary"><i class="fas fa-store"></i> Ouvrir le Marketplace</a>
    </div>
  </div>
</div>

@else
{{-- ── Connected state ─────────────────────────────────────────────────── --}}

<div class="row" style="align-items:flex-start;">

  {{-- Left: Account info --}}
  <div class="col-3">
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-user-circle"></i><h3>Compte connecté</h3></div>
      <div class="info-card-body">
        @if($token?->google_avatar_url)
          <div style="text-align:center;margin-bottom:12px;">
            <img src="{{ $token->google_avatar_url }}" style="width:56px;height:56px;border-radius:50%;border:2px solid var(--c-ink-05);" alt="">
          </div>
        @endif
        <div class="info-row"><span class="info-row-label">Nom</span><span class="info-row-value">{{ $token?->google_name ?? '—' }}</span></div>
        <div class="info-row"><span class="info-row-label">Email</span><span class="info-row-value" style="font-size:12px;">{{ $token?->google_email ?? '—' }}</span></div>
        <div class="info-row"><span class="info-row-label">Connecté le</span><span class="info-row-value">{{ $token?->connected_at?->format('d/m/Y H:i') ?? '—' }}</span></div>
        <div class="info-row"><span class="info-row-label">Dernière synchro</span><span class="info-row-value" id="gsLastSyncLabel">{{ $token?->last_sync_at?->format('d/m/Y H:i') ?? 'Jamais' }}</span></div>
      </div>
    </div>

    <div class="info-card">
      <div class="info-card-header"><i class="fas fa-chart-bar"></i><h3>Statistiques</h3></div>
      <div class="info-card-body">
        <div class="stat-card" style="margin-bottom:10px;padding:12px;">
          <div class="stat-icon" style="background:#0f9d5818;color:#0f9d58;"><i class="fas fa-file-excel"></i></div>
          <div class="stat-body">
            <div class="stat-value" id="gsStatSpreadsheets">0</div>
            <div class="stat-label">Feuilles de calcul</div>
          </div>
        </div>
        <div class="stat-card" style="padding:12px;">
          <div class="stat-icon" style="background:#4285f418;color:#4285f4;"><i class="fas fa-table"></i></div>
          <div class="stat-body">
            <div class="stat-value" id="gsStatSheets">0</div>
            <div class="stat-label">Total onglets</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Right: Spreadsheets table --}}
  <div class="col-9">
    <div class="table-wrapper">
      <div class="table-header">
        <span class="table-title">Feuilles de calcul</span>
        <span class="table-count" id="gsCount">0 résultat(s)</span>
        <div class="table-spacer"></div>
        <div class="table-search">
          <i class="fas fa-search"></i>
          <input type="text" id="gsSearchInput" placeholder="Rechercher une feuille…" autocomplete="off">
        </div>
      </div>

      <table class="crm-table">
        <thead>
          <tr>
            <th>Nom</th>
            <th>Création</th>
            <th>Modification</th>
            <th>Visibilité</th>
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
      <div><div class="modal-title">Nouvelle feuille</div><div class="modal-subtitle">Créer une feuille Google Sheets</div></div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Titre <span class="required">*</span></label>
        <input type="text" class="form-control" id="gsSpreadsheetTitle" maxlength="500" placeholder="Ma feuille">
      </div>
      <div class="form-group">
        <label class="form-label">Noms d’onglets initiaux <span class="hint">(séparés par des virgules)</span></label>
        <input type="text" class="form-control" id="gsSheetTitles" placeholder="Feuil1, Feuil2, Résumé">
        <span class="form-hint">Laisser vide pour démarrer avec un seul onglet par défaut.</span>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Annuler</button>
      <button class="btn btn-primary" id="gsSaveSpreadsheetBtn"><i class="fas fa-check"></i> Créer</button>
    </div>
  </div>
</div>

{{-- Data View / Editor --}}
<div class="modal-overlay" id="gsDataModal">
  <div class="modal modal-xl" style="max-width:92vw;width:92vw;">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:#0f9d5818;color:#0f9d58;"><i class="fas fa-table-cells"></i></div>
      <div>
        <div class="modal-title" id="gsDataModalTitle">Feuille de calcul</div>
        <div class="modal-subtitle">Lire et écrire des données dans votre feuille</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body" style="padding:0;">

      {{-- Sheet tabs --}}
      <div style="border-bottom:1px solid var(--c-ink-05);padding:10px 20px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;background:var(--surface-1);">
        <div style="font-size:11.5px;font-weight:700;color:var(--c-ink-40);text-transform:uppercase;letter-spacing:.04em;flex-shrink:0;">Onglets</div>
        <div id="gsSheetTabsLoader" style="display:flex;gap:6px;flex-wrap:wrap;flex:1;"></div>
        <div style="display:flex;gap:6px;margin-left:auto;flex-shrink:0;">
          <input type="text" class="form-control" id="gsNewSheetTitle" placeholder="Nom du nouvel onglet…" style="width:160px;font-size:12.5px;padding:6px 10px;">
          <button class="btn btn-secondary btn-sm" id="gsAddSheetBtn" title="Ajouter un onglet"><i class="fas fa-plus"></i></button>
        </div>
      </div>

      {{-- Range toolbar --}}
      <div style="padding:12px 20px;border-bottom:1px solid var(--c-ink-05);display:flex;align-items:center;gap:10px;flex-wrap:wrap;background:var(--surface-0);">
        <div style="display:flex;align-items:center;gap:8px;flex:1;min-width:200px;">
          <label style="font-size:12px;font-weight:600;color:var(--c-ink-40);white-space:nowrap;">Plage</label>
          <input type="text" class="form-control gs-range-input" id="gsRangeInput"
                 value="Sheet1!A1:Z50" placeholder="Sheet1!A1:Z50" style="max-width:220px;">
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
          <button class="btn btn-primary btn-sm" id="gsReadRangeBtn"><i class="fas fa-eye"></i> Lire</button>
          <button class="btn btn-secondary btn-sm" id="gsWriteRangeBtn"><i class="fas fa-pen"></i> Écrire</button>
          <button class="btn btn-secondary btn-sm" id="gsAppendRowsBtn"><i class="fas fa-arrow-down"></i> Ajouter</button>
          <button class="btn btn-ghost btn-sm" id="gsClearRangeBtn" style="color:var(--c-danger);"><i class="fas fa-eraser"></i> Vider</button>
        </div>
      </div>

      {{-- Data table --}}
      <div id="gsDataTableWrap" style="padding:16px 20px;min-height:200px;">
        <div style="text-align:center;padding:40px;color:var(--c-ink-40);">
          <i class="fas fa-table-cells" style="font-size:28px;margin-bottom:8px;display:block;opacity:.3;"></i>
          <p>Sélectionnez un onglet et cliquez sur <strong>Lire</strong> pour charger les données.</p>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Fermer</button>
    </div>
  </div>
</div>

{{-- Write Modal --}}
<div class="modal-overlay" id="gsWriteModal">
  <div class="modal modal-md">
      <div class="modal-header">
      <div class="modal-title">Écrire des données</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Plage <span class="required">*</span></label>
        <input type="text" class="form-control gs-range-input" id="gsWriteRange" placeholder="Sheet1!A1">
        <span class="form-hint">Exemple: <code>Sheet1!A1</code> ou <code>Sheet1!B2:D5</code></span>
      </div>
      <div class="form-group">
        <label class="form-label">Données <span class="required">*</span></label>
        <textarea class="form-control" id="gsWriteData" rows="8"
                  placeholder="Collez des valeurs séparées par tabulation (TSV). Chaque ligne = une ligne, tab = séparateur de colonne.&#10;Exemple:&#10;Nom&#9;Age&#9;Ville&#10;Alice&#9;30&#9;Paris"></textarea>
        <span class="form-hint">Valeurs TSV. Copiez-collez directement depuis Excel ou Google Sheets.</span>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Annuler</button>
      <button class="btn btn-primary" id="gsSaveWriteBtn"><i class="fas fa-check"></i> Écrire</button>
    </div>
  </div>
</div>

{{-- Append Modal --}}
<div class="modal-overlay" id="gsAppendModal">
  <div class="modal modal-md">
    <div class="modal-header">
      <div class="modal-title">Ajouter des lignes</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Plage <span class="required">*</span></label>
        <input type="text" class="form-control gs-range-input" id="gsAppendRange" placeholder="Sheet1!A:A">
        <span class="form-hint">Les lignes seront ajoutées après la dernière ligne de cette plage.</span>
      </div>
      <div class="form-group">
        <label class="form-label">Données à ajouter <span class="required">*</span></label>
        <textarea class="form-control" id="gsAppendData" rows="8"
                  placeholder="Valeurs séparées par tabulation. Chaque ligne est une nouvelle ligne.&#10;Bob&#9;25&#9;Lyon&#10;Carol&#9;42&#9;Marseille"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Annuler</button>
      <button class="btn btn-primary" id="gsSaveAppendBtn"><i class="fas fa-arrow-down"></i> Ajouter les lignes</button>
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
  Toast.success('Succès', @json(session('success')));
  @endif

  @if(session('error'))
  Toast.error('Erreur', @json(session('error')));
  @endif
});
</script>
@endpush
