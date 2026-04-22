@extends('notion-workspace::layouts.notion')

@section('title', 'Notion Workspace')

@section('notion_breadcrumb')
  <a href="{{ route('marketplace.index') }}">Applications</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Notion Workspace</span>
@endsection

@section('notion_content')
<div class="page-header">
  <div class="page-header-left">
    <h1>Notion Workspace</h1>
    <p>Base de connaissances, wiki equipe et docs clients dans votre CRM.</p>
  </div>
  <div class="page-header-actions">
    <button class="btn btn-primary" id="notionCreateRootBtn" data-modal-open="notionPageModal">
      <i class="fas fa-plus"></i> Nouvelle page
    </button>
  </div>
</div>

<div class="notion-shell">
  <aside class="notion-sidebar">
    <div class="notion-sidebar-head">
      <div class="table-search">
        <i class="fas fa-search"></i>
        <input type="text" id="notionSearchInput" placeholder="Rechercher une page...">
      </div>

      <div class="notion-tabs">
        <button type="button" class="btn btn-ghost active" data-notion-scope="all">Tout</button>
        <button type="button" class="btn btn-ghost" data-notion-scope="favorites">Favoris</button>
        <button type="button" class="btn btn-ghost" data-notion-scope="templates">Templates</button>
        <button type="button" class="btn btn-ghost" data-notion-scope="archived">Archives</button>
      </div>
    </div>

    <div class="notion-tree" id="notionTree"></div>
  </aside>

  <section class="notion-editor-wrap">
    <div class="notion-editor-toolbar">
      <div class="notion-page-meta">
        <span id="notionPagePath">Aucune page selectionnee</span>
      </div>

      <div class="notion-toolbar-actions">
        <button class="btn btn-ghost" id="notionFavoriteBtn"><i class="fas fa-star"></i> Favori</button>
        <button class="btn btn-ghost" id="notionDuplicateBtn"><i class="fas fa-copy"></i> Dupliquer</button>
        <button class="btn btn-ghost" id="notionMoveBtn" data-modal-open="notionMoveModal"><i class="fas fa-up-down-left-right"></i> Deplacer</button>
        <button class="btn btn-ghost" id="notionShareBtn" data-modal-open="notionShareModal"><i class="fas fa-share-nodes"></i> Partager</button>
        <button class="btn btn-danger" id="notionArchiveBtn"><i class="fas fa-box-archive"></i> Archiver</button>
      </div>
    </div>

    <div class="notion-editor-content">
      <div class="notion-empty" id="notionEmptyState">
        <div class="table-empty-icon"><i class="fas fa-file-lines"></i></div>
        <h3>Selectionnez une page</h3>
        <p>Creez une page pour commencer votre documentation.</p>
      </div>

      <form id="notionEditorForm" style="display:none;">
        <input type="hidden" id="notionPageId" name="page_id">

        <div class="row">
          <div class="col-8">
            <div class="form-group">
              <label class="form-label">Titre <span class="required">*</span></label>
              <input type="text" class="form-control" id="notionTitle" name="title" maxlength="220">
            </div>
          </div>

          <div class="col-2">
            <div class="form-group">
              <label class="form-label">Icone</label>
	              <div class="notion-icon-picker">
	                <button type="button" class="btn btn-secondary" id="notionIconPickBtn" data-modal-open="notionIconModal">
	                  <span class="notion-icon-preview" id="notionIconPreview"><i class="fas fa-file-lines"></i></span>
	                  <span>Choisir</span>
	                </button>
	                <input type="hidden" id="notionIcon" name="icon" maxlength="50">
	              </div>
	            </div>
	          </div>

          <div class="col-2">
            <div class="form-group">
              <label class="form-label">Couleur</label>
              <div class="color-picker" id="notionCoverPicker" data-target="notionCoverColor">
                <button type="button" class="btn btn-secondary color-picker-btn" id="notionCoverPickBtn">
                  <span class="color-swatch" id="notionCoverSwatch" style="background:#2563eb;"></span>
                  <span>Palette</span>
                </button>
                <input type="hidden" class="form-control" id="notionCoverColor" name="cover_color" maxlength="20">
                <input type="color" class="color-custom" id="notionCoverColorCustom" value="#2563eb" title="Couleur personnalisee">
              </div>
              <div class="color-palette" id="notionCoverPalette" style="display:none;">
                @foreach(['#2563eb','#0891b2','#7c3aed','#db2777','#ef4444','#f59e0b','#16a34a','#111827','#64748b','#ffffff'] as $c)
                  <button type="button" class="color-chip" data-color="{{ $c }}" style="background:{{ $c }};"></button>
                @endforeach
              </div>
            </div>
          </div>

          <div class="col-4">
            <div class="form-group">
              <label class="form-label">Visibilite</label>
              <select class="form-control" id="notionVisibility" name="visibility">
                @foreach($visibilities as $key => $label)
                  <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="col-4">
            <div class="form-group">
              <label class="form-label">Client lie</label>
              <select class="form-control" id="notionClientId" name="client_id">
                <option value="">Aucun</option>
                @foreach($clients as $client)
                  <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="col-2">
            <div class="form-group">
              <label class="form-label">Template</label>
              <select class="form-control" id="notionIsTemplate" name="is_template">
                <option value="0">Non</option>
                <option value="1">Oui</option>
              </select>
            </div>
          </div>

          <div class="col-2">
            <div class="form-group">
              <label class="form-label">Favori</label>
              <select class="form-control" id="notionIsFavorite" name="is_favorite">
                <option value="0">Non</option>
                <option value="1">Oui</option>
              </select>
            </div>
          </div>

          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Contenu</label>
              <div class="notion-quill-wrap">
                <div id="notionQuillToolbar" class="notion-quill-toolbar">
                  <span class="ql-formats">
                    <select class="ql-header">
                      <option selected></option>
                      <option value="1"></option>
                      <option value="2"></option>
                      <option value="3"></option>
                    </select>
                  </span>
                  <span class="ql-formats">
                    <button class="ql-bold"></button>
                    <button class="ql-italic"></button>
                    <button class="ql-underline"></button>
                    <button class="ql-strike"></button>
                  </span>
                  <span class="ql-formats">
                    <select class="ql-color"></select>
                    <select class="ql-background"></select>
                  </span>
                  <span class="ql-formats">
                    <button class="ql-blockquote"></button>
                    <button class="ql-code-block"></button>
                  </span>
                  <span class="ql-formats">
                    <button class="ql-list" value="ordered"></button>
                    <button class="ql-list" value="bullet"></button>
                    <button class="ql-indent" value="-1"></button>
                    <button class="ql-indent" value="+1"></button>
                  </span>
                  <span class="ql-formats">
                    <button class="ql-link"></button>
                    <button class="ql-clean"></button>
                  </span>
                </div>
                <div id="notionQuillEditor" class="notion-quill-editor"></div>
                <textarea class="form-control" id="notionContentText" name="content_text" rows="22" style="display:none;"></textarea>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>

    <div class="notion-editor-footer">
      <div id="notionEditorInfo">Aucune page chargee</div>
      <button class="btn btn-primary" id="notionSaveBtn"><i class="fas fa-floppy-disk"></i> Enregistrer</button>
    </div>
  </section>
</div>

<div class="modal-overlay" id="notionPageModal">
  <div class="modal modal-md">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-file-circle-plus"></i></div>
      <div>
        <div class="modal-title">Nouvelle page</div>
        <div class="modal-subtitle">Creer une page enfant ou racine.</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <form id="notionCreateForm">
        <div class="form-group">
          <label class="form-label">Titre <span class="required">*</span></label>
          <input type="text" class="form-control" id="notionCreateTitle" name="title" maxlength="220" required>
        </div>
        <div class="form-group">
          <label class="form-label">Parent</label>
          <select class="form-control" id="notionCreateParentId" name="parent_id">
            <option value="">Racine</option>
          </select>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Annuler</button>
      <button class="btn btn-primary" id="notionCreateSubmitBtn"><i class="fas fa-check"></i> Creer</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="notionMoveModal">
  <div class="modal modal-md">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-up-down-left-right"></i></div>
      <div>
        <div class="modal-title">Deplacer la page</div>
        <div class="modal-subtitle">Choisir un parent et position.</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <form id="notionMoveForm">
        <div class="form-group">
          <label class="form-label">Nouveau parent</label>
          <select class="form-control" id="notionMoveParentId" name="parent_id">
            <option value="">Racine</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Ordre</label>
          <input type="number" class="form-control" id="notionMoveSortOrder" name="sort_order" min="0" value="0">
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Annuler</button>
      <button class="btn btn-primary" id="notionMoveSubmitBtn"><i class="fas fa-check"></i> Deplacer</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="notionShareModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-share-nodes"></i></div>
      <div>
        <div class="modal-title">Partager la page</div>
        <div class="modal-subtitle">Definir les droits pour les utilisateurs du tenant.</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div class="notion-share-grid" id="notionShareRows"></div>
      <button class="btn btn-ghost" type="button" id="notionShareAddRowBtn"><i class="fas fa-plus"></i> Ajouter un acces</button>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Annuler</button>
      <button class="btn btn-primary" id="notionShareSubmitBtn"><i class="fas fa-check"></i> Sauvegarder</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="notionIconModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-icons"></i></div>
      <div>
        <div class="modal-title">Choisir une icone</div>
        <div class="modal-subtitle">Selectionnez une icone pour la page.</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div class="table-search" style="margin-bottom:10px;">
        <i class="fas fa-search"></i>
        <input type="text" id="notionIconSearch" placeholder="Rechercher une icone...">
      </div>
      <div class="notion-icon-grid" id="notionIconGrid"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Fermer</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
window.NOTION_ROUTES = {
  index: '{{ route('notion-workspace.index') }}',
  treeData: '{{ route('notion-workspace.tree.data') }}',
  pagesBase: '{{ url('/extensions/notion-workspace/pages') }}',
};

window.NOTION_BOOTSTRAP = {
  initialPageId: @json($initialPageId),
  users: @json($users->values()->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])),
};
</script>
@endpush



