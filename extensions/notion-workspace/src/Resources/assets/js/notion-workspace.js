'use strict';

const NotionWorkspaceModule = (() => {
  const state = {
    scope: 'all',
    search: '',
    pages: [],
    selectedPageId: window.NOTION_BOOTSTRAP?.initialPageId || null,
    selectedPage: null,
    draggingPageId: null,
    quill: null,
  };

  function boot() {
    bindEvents();
    bindIconPicker();
    bindCoverPalette();
    initQuill();
    loadTree(true);
  }

  function bindEvents() {
    let timer = null;
    const searchInput = document.getElementById('notionSearchInput');
    searchInput?.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(() => {
        state.search = searchInput.value.trim();
        loadTree(false);
      }, 260);
    });

    document.querySelectorAll('[data-notion-scope]').forEach((btn) => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('[data-notion-scope]').forEach((b) => b.classList.remove('active'));
        btn.classList.add('active');
        state.scope = btn.dataset.notionScope || 'all';
        loadTree(false);
      });
    });

    document.getElementById('notionCreateRootBtn')?.addEventListener('click', () => {
      populateParentOptions('notionCreateParentId', true);
      setField('notionCreateParentId', state.selectedPageId || '');
      setField('notionCreateTitle', '');
    });

    document.getElementById('notionCreateSubmitBtn')?.addEventListener('click', createPage);
    document.getElementById('notionSaveBtn')?.addEventListener('click', savePage);
    document.getElementById('notionFavoriteBtn')?.addEventListener('click', toggleFavorite);
    document.getElementById('notionDuplicateBtn')?.addEventListener('click', duplicatePage);
    document.getElementById('notionArchiveBtn')?.addEventListener('click', archivePage);

    document.getElementById('notionMoveBtn')?.addEventListener('click', () => {
      populateParentOptions('notionMoveParentId', true, state.selectedPageId);
      setField('notionMoveParentId', state.selectedPage?.parent_id || '');
      setField('notionMoveSortOrder', state.selectedPage?.sort_order || 0);
    });
    document.getElementById('notionMoveSubmitBtn')?.addEventListener('click', movePage);

    document.getElementById('notionShareBtn')?.addEventListener('click', openShareModal);
    document.getElementById('notionShareAddRowBtn')?.addEventListener('click', () => addShareRow());
    document.getElementById('notionShareSubmitBtn')?.addEventListener('click', saveShares);

    document.getElementById('notionShareRows')?.addEventListener('click', (e) => {
      const removeBtn = e.target.closest('[data-share-remove]');
      if (removeBtn) removeBtn.closest('.notion-share-row')?.remove();
    });

  }

  async function loadTree(selectPageIfNeeded = false) {
    const tree = document.getElementById('notionTree');
    if (tree) tree.innerHTML = '<div class="skeleton" style="height:30px;"></div><div class="skeleton" style="height:30px;"></div>';

    const { ok, data } = await Http.get(window.NOTION_ROUTES.treeData, {
      search: state.search,
      scope: state.scope,
    });

    if (!ok || !data.success) {
      Toast.error('Erreur', data.message || 'Impossible de charger les pages.');
      if (tree) tree.innerHTML = '';
      return;
    }

    state.pages = data.data || [];
    renderTree();

    if (selectPageIfNeeded && state.selectedPageId) {
      loadPage(state.selectedPageId);
      return;
    }

    if (!state.selectedPageId && state.pages.length) {
      state.selectedPageId = state.pages[0].id;
      loadPage(state.selectedPageId);
    }
  }

  function renderTree() {
    const tree = document.getElementById('notionTree');
    if (!tree) return;

    if (!state.pages.length) {
      tree.innerHTML = '<div style="font-size:12px;color:var(--c-ink-40);padding:6px;">Aucune page.</div>';
      return;
    }

    const ids = new Set(state.pages.map((p) => Number(p.id)));

    const byParent = new Map();
    state.pages.forEach((page) => {
      const pid = page.parent_id ? Number(page.parent_id) : 0;
      const key = pid && ids.has(pid) ? pid : 0; // attach "orphans" to root for scoped views
      if (!byParent.has(key)) byParent.set(key, []);
      byParent.get(key).push(page);
    });

    const nodes = [];
    const walk = (parentId, depth) => {
      const children = byParent.get(parentId) || [];
      children.forEach((page) => {
        nodes.push(renderTreeNode(page, depth));
        walk(page.id, depth + 1);
      });
    };
    walk(0, 0);

    tree.innerHTML = nodes.join('');

    tree.querySelectorAll('[data-page-id]').forEach((node) => {
      node.addEventListener('click', () => {
        const id = parseInt(node.dataset.pageId, 10);
        if (!Number.isNaN(id)) {
          state.selectedPageId = id;
          loadPage(id);
        }
      });
    });

    wireTreeDnD(tree);
  }

  function renderTreeNode(page, depth) {
    const iconHtml = renderIcon(page.icon);
    const active = Number(page.id) === Number(state.selectedPageId) ? 'active' : '';
    const leftPadding = 8 + (depth * 14);
    const favorite = page.is_favorite ? '<i class="fas fa-star" style="color:#f59e0b;"></i>' : '';

    return `
      <div class="notion-node ${active}" data-page-id="${page.id}" draggable="true" style="padding-left:${leftPadding}px;">
        <div class="notion-node-icon">${iconHtml}</div>
        <div class="notion-node-title">${esc(page.title || '')}</div>
        <div class="notion-node-meta">${favorite}</div>
      </div>
    `;
  }

  async function loadPage(pageId) {
    if (!pageId) return;

    const { ok, data } = await Http.get(`${window.NOTION_ROUTES.pagesBase}/${pageId}`);
    if (!ok || !data.success) {
      Toast.error('Erreur', data.message || 'Impossible de charger la page.');
      return;
    }

    state.selectedPage = data.data;
    state.selectedPageId = data.data.id;
    fillEditor(data.data);
    highlightSelectedNode();
  }

  function fillEditor(page) {
    const empty = document.getElementById('notionEmptyState');
    const form = document.getElementById('notionEditorForm');
    if (empty) empty.style.display = 'none';
    if (form) form.style.display = '';

    setField('notionPageId', page.id || '');
    setField('notionTitle', page.title || '');
    setField('notionIcon', page.icon || '');
    setIconPreview(page.icon || '');
    setField('notionCoverColor', page.cover_color || '');
    setField('notionVisibility', page.visibility || 'private');
    setField('notionClientId', page.client_id || '');
    setField('notionIsTemplate', page.is_template ? '1' : '0');
    setField('notionIsFavorite', page.is_favorite ? '1' : '0');
    setField('notionContentText', page.content_text || '');
    setQuillHtml(page.content_text || '');

    setText('notionPagePath', `${page.title || ''} - ${page.visibility || 'private'}`);
    setText('notionEditorInfo', `Derniere modification: ${formatDateTime(page.last_edited_at || page.updated_at)} par ${page.last_edited_by_name || '-'}`);
  }

  async function createPage() {
    const btn = document.getElementById('notionCreateSubmitBtn');
    const title = (getField('notionCreateTitle') || '').trim();
    if (!title) {
      Toast.warning('Validation', 'Le titre est obligatoire.');
      return;
    }

    const payload = {
      title,
      parent_id: nullable(getField('notionCreateParentId')),
      visibility: 'private',
    };

    if (btn) CrmForm.setLoading(btn, true);
    const res = await Http.post(`${window.NOTION_ROUTES.pagesBase}`, payload);
    if (btn) CrmForm.setLoading(btn, false);

    if (!res.ok) {
      Toast.error('Erreur', res.data?.message || 'Creation impossible.');
      return;
    }

    Toast.success('Succes', res.data?.message || 'Page creee.');
    Modal.close(document.getElementById('notionPageModal'));
    await loadTree(false);

    if (res.data?.data?.id) {
      state.selectedPageId = res.data.data.id;
      loadPage(state.selectedPageId);
    }
  }

  async function savePage() {
    if (!state.selectedPageId) {
      Toast.warning('Info', 'Aucune page selectionnee.');
      return;
    }

    const payload = {
      title: (getField('notionTitle') || '').trim(),
      icon: nullable(getField('notionIcon')),
      cover_color: nullable(getField('notionCoverColor')),
      visibility: getField('notionVisibility') || 'private',
      client_id: nullable(getField('notionClientId')),
      is_template: getField('notionIsTemplate') === '1',
      is_favorite: getField('notionIsFavorite') === '1',
      content_text: getQuillHtml(),
    };
    setField('notionContentText', payload.content_text || '');

    if (!payload.title) {
      Toast.warning('Validation', 'Le titre est obligatoire.');
      return;
    }

    const btn = document.getElementById('notionSaveBtn');
    if (btn) CrmForm.setLoading(btn, true);
    const res = await Http.put(`${window.NOTION_ROUTES.pagesBase}/${state.selectedPageId}`, payload);
    if (btn) CrmForm.setLoading(btn, false);

    if (!res.ok) {
      Toast.error('Erreur', res.data?.message || 'Enregistrement impossible.');
      return;
    }

    Toast.success('Succes', res.data?.message || 'Page enregistree.');
    await loadTree(false);
    await loadPage(state.selectedPageId);
  }

  async function toggleFavorite() {
    if (!state.selectedPageId) return;

    const res = await patch(`${window.NOTION_ROUTES.pagesBase}/${state.selectedPageId}/favorite`, {});
    if (!res.ok) {
      Toast.error('Erreur', res.data?.message || 'Action impossible.');
      return;
    }

    Toast.success('Succes', res.data?.message || 'Favori mis a jour.');
    await loadTree(false);
    await loadPage(state.selectedPageId);
  }

  async function duplicatePage() {
    if (!state.selectedPageId) return;
    const res = await Http.post(`${window.NOTION_ROUTES.pagesBase}/${state.selectedPageId}/duplicate`, {});
    if (!res.ok) {
      Toast.error('Erreur', res.data?.message || 'Duplication impossible.');
      return;
    }

    Toast.success('Succes', res.data?.message || 'Page dupliquee.');
    await loadTree(false);
    if (res.data?.data?.id) {
      state.selectedPageId = res.data.data.id;
      loadPage(state.selectedPageId);
    }
  }

  async function archivePage() {
    if (!state.selectedPageId) return;

    Modal.confirm({
      title: 'Archiver cette page ?',
      message: 'La page sera deplacee dans les archives.',
      confirmText: 'Archiver',
      type: 'danger',
      onConfirm: async () => {
        const res = await Http.delete(`${window.NOTION_ROUTES.pagesBase}/${state.selectedPageId}`);
        if (!res.ok) {
          Toast.error('Erreur', res.data?.message || 'Archivage impossible.');
          return;
        }
        Toast.success('Succes', res.data?.message || 'Page archivee.');
        state.selectedPageId = null;
        state.selectedPage = null;
        hideEditor();
        loadTree(false);
      },
    });
  }

  async function movePage() {
    if (!state.selectedPageId) return;

    const payload = {
      parent_id: nullable(getField('notionMoveParentId')),
      sort_order: Number(getField('notionMoveSortOrder') || 0),
    };

    const btn = document.getElementById('notionMoveSubmitBtn');
    if (btn) CrmForm.setLoading(btn, true);
    const res = await patch(`${window.NOTION_ROUTES.pagesBase}/${state.selectedPageId}/move`, payload);
    if (btn) CrmForm.setLoading(btn, false);

    if (!res.ok) {
      Toast.error('Erreur', res.data?.message || 'Deplacement impossible.');
      return;
    }

    Toast.success('Succes', res.data?.message || 'Page deplacee.');
    Modal.close(document.getElementById('notionMoveModal'));
    await loadTree(false);
    await loadPage(state.selectedPageId);
  }

  function openShareModal() {
    if (!state.selectedPage) {
      Toast.warning('Info', 'Selectionnez une page.');
      return;
    }

    const rows = document.getElementById('notionShareRows');
    if (!rows) return;
    rows.innerHTML = '';

    const shares = Array.isArray(state.selectedPage.shares) ? state.selectedPage.shares : [];
    if (!shares.length) addShareRow();
    else shares.forEach((share) => addShareRow(share));
  }

  function addShareRow(share = null) {
    const rows = document.getElementById('notionShareRows');
    if (!rows) return;

    const users = Array.isArray(window.NOTION_BOOTSTRAP?.users) ? window.NOTION_BOOTSTRAP.users : [];

    const row = document.createElement('div');
    row.className = 'notion-share-row';
    row.innerHTML = `
      <select class="form-control" data-share-user>
        ${users.map((user) => `<option value="${user.id}" ${String(share?.user_id || '') === String(user.id) ? 'selected' : ''}>${esc(user.name)} (${esc(user.email)})</option>`).join('')}
      </select>
      <select class="form-control" data-share-edit>
        <option value="1" ${(share?.can_edit ?? true) ? 'selected' : ''}>Edit: Oui</option>
        <option value="0" ${(share?.can_edit ?? true) ? '' : 'selected'}>Edit: Non</option>
      </select>
      <select class="form-control" data-share-comment>
        <option value="1" ${(share?.can_comment ?? true) ? 'selected' : ''}>Comment: Oui</option>
        <option value="0" ${(share?.can_comment ?? true) ? '' : 'selected'}>Comment: Non</option>
      </select>
      <select class="form-control" data-share-share>
        <option value="1" ${(share?.can_share ?? false) ? 'selected' : ''}>Share: Oui</option>
        <option value="0" ${(share?.can_share ?? false) ? '' : 'selected'}>Share: Non</option>
      </select>
      <button class="btn btn-danger" type="button" data-share-remove><i class="fas fa-trash"></i></button>
    `;
    rows.appendChild(row);
  }

  async function saveShares() {
    if (!state.selectedPageId) return;

    const rows = Array.from(document.querySelectorAll('#notionShareRows .notion-share-row'));
    const shares = rows.map((row) => ({
      user_id: Number(row.querySelector('[data-share-user]')?.value || 0),
      can_edit: row.querySelector('[data-share-edit]')?.value === '1',
      can_comment: row.querySelector('[data-share-comment]')?.value === '1',
      can_share: row.querySelector('[data-share-share]')?.value === '1',
    })).filter((share) => share.user_id > 0);

    const btn = document.getElementById('notionShareSubmitBtn');
    if (btn) CrmForm.setLoading(btn, true);
    const res = await Http.put(`${window.NOTION_ROUTES.pagesBase}/${state.selectedPageId}/shares`, { shares });
    if (btn) CrmForm.setLoading(btn, false);

    if (!res.ok) {
      Toast.error('Erreur', res.data?.message || 'Partage impossible.');
      return;
    }

    Toast.success('Succes', res.data?.message || 'Partages mis a jour.');
    Modal.close(document.getElementById('notionShareModal'));
    await loadPage(state.selectedPageId);
  }

  function populateParentOptions(selectId, includeRoot, excludePageId = null) {
    const select = document.getElementById(selectId);
    if (!select) return;

    const previous = select.value;
    const root = includeRoot ? '<option value="">Racine</option>' : '';

    const options = state.pages
      .filter((page) => Number(page.id) !== Number(excludePageId || 0))
      .map((page) => `<option value="${page.id}">${esc(page.title)}</option>`)
      .join('');

    select.innerHTML = root + options;
    if (previous) select.value = previous;
  }

  function highlightSelectedNode() {
    document.querySelectorAll('.notion-node').forEach((node) => {
      node.classList.toggle('active', Number(node.dataset.pageId) === Number(state.selectedPageId));
    });
  }

  function hideEditor() {
    const empty = document.getElementById('notionEmptyState');
    const form = document.getElementById('notionEditorForm');
    if (empty) empty.style.display = '';
    if (form) form.style.display = 'none';
    setText('notionPagePath', 'Aucune page selectionnee');
    setText('notionEditorInfo', 'Aucune page chargee');
    setQuillHtml('');
    setField('notionContentText', '');
  }

  async function patch(url, payload) {
    const res = await fetch(url, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: JSON.stringify(payload || {}),
    });

    const data = await res.json().catch(() => ({}));
    return { ok: res.ok, status: res.status, data };
  }

  function nullable(value) {
    return value === '' || value === null || typeof value === 'undefined' ? null : value;
  }

  function setField(id, value) {
    const el = document.getElementById(id);
    if (el) el.value = value ?? '';
  }

  function getField(id) {
    const el = document.getElementById(id);
    return el ? el.value : '';
  }

  function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = String(value ?? '');
  }

  function esc(value) {
    const d = document.createElement('div');
    d.textContent = value || '';
    return d.innerHTML;
  }

  function formatDateTime(value) {
    if (!value) return '-';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return String(value);
    return d.toLocaleString('fr-FR');
  }

  function initQuill() {
    const el = document.getElementById('notionQuillEditor');
    const toolbar = document.getElementById('notionQuillToolbar');
    if (!el || !window.Quill) return;

    state.quill = new window.Quill(el, {
      theme: 'snow',
      modules: {
        toolbar: toolbar || true,
        history: { delay: 800, maxStack: 200, userOnly: true },
      },
      placeholder: 'Votre contenu...',
    });
  }

  function setQuillHtml(html) {
    if (!state.quill) return;
    state.quill.clipboard.dangerouslyPasteHTML(html || '');
  }

  function getQuillHtml() {
    if (!state.quill) return '';
    const html = (state.quill.root?.innerHTML || '').trim();
    if (html === '<p><br></p>') return '';
    return html;
  }

  function bindIconPicker() {
    const grid = document.getElementById('notionIconGrid');
    const search = document.getElementById('notionIconSearch');
    if (!grid) return;

    const icons = [
      'fa-file-lines', 'fa-book', 'fa-bookmark', 'fa-bolt', 'fa-briefcase', 'fa-bug', 'fa-calendar', 'fa-chart-line',
      'fa-check', 'fa-circle-info', 'fa-circle-question', 'fa-code', 'fa-comments', 'fa-credit-card', 'fa-diagram-project',
      'fa-envelope', 'fa-flask', 'fa-folder', 'fa-gauge', 'fa-gift', 'fa-globe', 'fa-graduation-cap', 'fa-heart',
      'fa-house', 'fa-lightbulb', 'fa-link', 'fa-list-check', 'fa-list-ul', 'fa-lock', 'fa-magnifying-glass', 'fa-message',
      'fa-microchip', 'fa-money-bill', 'fa-note-sticky', 'fa-palette', 'fa-paperclip', 'fa-pen', 'fa-people-group',
      'fa-phone', 'fa-puzzle-piece', 'fa-rocket', 'fa-scale-balanced', 'fa-shield-halved', 'fa-star', 'fa-tag',
      'fa-thumbtack', 'fa-triangle-exclamation', 'fa-user', 'fa-users', 'fa-wand-magic-sparkles', 'fa-warehouse',
      'fa-wrench',
    ];

    const render = (term) => {
      const t = String(term || '').trim().toLowerCase();
      const current = getField('notionIcon') || '';
      const filtered = icons.filter((ic) => t === '' || ic.toLowerCase().includes(t));
      grid.innerHTML = filtered.map((ic) => `
        <div class="notion-icon-item ${ic === current ? 'active' : ''}" data-icon="${ic}" title="${ic}">
          <i class="fas ${ic}"></i>
        </div>
      `).join('');
    };

    grid.addEventListener('click', (e) => {
      const item = e.target.closest('[data-icon]');
      if (!item) return;
      const icon = item.getAttribute('data-icon') || '';
      setField('notionIcon', icon);
      setIconPreview(icon);
      render(search ? search.value : '');
      Modal.close(document.getElementById('notionIconModal'));
    });

    search?.addEventListener('input', () => render(search.value));
    render('');
  }

  function bindCoverPalette() {
    const btn = document.getElementById('notionCoverPickBtn');
    const palette = document.getElementById('notionCoverPalette');
    const swatch = document.getElementById('notionCoverSwatch');
    const custom = document.getElementById('notionCoverColorCustom');
    if (!btn || !palette || !swatch || !custom) return;

    const set = (color) => {
      const c = String(color || '').trim();
      setField('notionCoverColor', c);
      swatch.style.background = c || '#2563eb';
      custom.value = c && c.startsWith('#') ? c : '#2563eb';
    };

    btn.addEventListener('click', () => {
      palette.style.display = palette.style.display === 'none' ? '' : 'none';
    });

    palette.addEventListener('click', (e) => {
      const chip = e.target.closest('[data-color]');
      if (!chip) return;
      set(chip.getAttribute('data-color') || '');
      palette.style.display = 'none';
    });

    custom.addEventListener('change', () => set(custom.value));

    document.addEventListener('click', (e) => {
      if (e.target.closest('#notionCoverPicker') || e.target.closest('#notionCoverPalette')) return;
      palette.style.display = 'none';
    });

    set(getField('notionCoverColor') || '#2563eb');
  }

  function setIconPreview(icon) {
    const el = document.getElementById('notionIconPreview');
    if (!el) return;
    el.innerHTML = renderIcon(icon);
  }

  function renderIcon(icon) {
    const v = String(icon || '').trim();
    if (v.startsWith('fa-')) return `<i class="fas ${esc(v)}"></i>`;
    if (v) return esc(v);
    return '<i class="fas fa-file-lines"></i>';
  }

  function wireTreeDnD(tree) {
    tree.querySelectorAll('.notion-node[data-page-id]').forEach((node) => {
      node.addEventListener('dragstart', (e) => {
        const id = parseInt(node.dataset.pageId, 10);
        if (Number.isNaN(id)) return;
        state.draggingPageId = id;
        node.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', String(id));
      });

      node.addEventListener('dragend', () => {
        state.draggingPageId = null;
        node.classList.remove('dragging');
        tree.querySelectorAll('.notion-node.drop-target').forEach((n) => n.classList.remove('drop-target'));
      });

      node.addEventListener('dragover', (e) => {
        e.preventDefault();
        node.classList.add('drop-target');
        e.dataTransfer.dropEffect = 'move';
      });

      node.addEventListener('dragleave', () => node.classList.remove('drop-target'));

      node.addEventListener('drop', async (e) => {
        e.preventDefault();
        node.classList.remove('drop-target');
        const from = parseInt(e.dataTransfer.getData('text/plain'), 10);
        const toParent = parseInt(node.dataset.pageId, 10);
        if (Number.isNaN(from) || Number.isNaN(toParent) || from === toParent) return;
        await movePageByDnD(from, toParent);
      });
    });

    tree.addEventListener('dragover', (e) => {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
    });

    tree.addEventListener('drop', async (e) => {
      const onNode = e.target.closest('.notion-node[data-page-id]');
      if (onNode) return;
      const from = parseInt(e.dataTransfer.getData('text/plain'), 10);
      if (Number.isNaN(from)) return;
      await movePageByDnD(from, null);
    });
  }

  async function movePageByDnD(pageId, parentId) {
    const page = state.pages.find((p) => Number(p.id) === Number(pageId));
    if (!page) return;

    const siblings = state.pages.filter((p) => Number(p.parent_id || 0) === Number(parentId || 0));
    const maxSort = siblings.reduce((acc, p) => Math.max(acc, Number(p.sort_order || 0)), 0);

    const res = await patch(`${window.NOTION_ROUTES.pagesBase}/${pageId}/move`, {
      parent_id: parentId,
      sort_order: maxSort + 1,
    });

    if (!res.ok || !res.data?.success) {
      Toast.error('Erreur', res.data?.message || 'Deplacement impossible.');
      return;
    }

    Toast.success('Succes', res.data?.message || 'Page deplacee.');
    await loadTree(false);
    if (state.selectedPageId) await loadPage(state.selectedPageId);
  }

  return { boot };
})();

window.NotionWorkspaceModule = NotionWorkspaceModule;
document.addEventListener('DOMContentLoaded', () => {
  window.NotionWorkspaceModule?.boot();
});
