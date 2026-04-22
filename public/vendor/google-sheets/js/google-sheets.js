'use strict';

const GoogleSheetsModule = (() => {
  const state = {
    connected: false,
    spreadsheets: [],
    currentSpreadsheet: null,
    currentSheets: [],
    currentSheetTitle: '',
    currentRange: 'A1:Z100',
    search: '',
    debounceTimer: null,
    dataLoaded: false,
  };

  // ── Boot ───────────────────────────────────────────────────────────────

  function boot(bootstrap = {}) {
    state.connected = !!bootstrap.connected;
    bindActions();
    if (!state.connected) return;
    loadStats();
    loadSpreadsheets();
  }

  // ── Bind ───────────────────────────────────────────────────────────────

  function bindActions() {
    document.getElementById('gsRefreshBtn')?.addEventListener('click', () => {
      loadStats();
      loadSpreadsheets();
    });

    document.getElementById('gsDisconnectBtn')?.addEventListener('click', disconnect);
    document.getElementById('gsSaveSpreadsheetBtn')?.addEventListener('click', createSpreadsheet);

    document.getElementById('gsSearchInput')?.addEventListener('input', (e) => {
      clearTimeout(state.debounceTimer);
      state.debounceTimer = setTimeout(() => {
        state.search = (e.target.value || '').trim();
        if (state.search.length >= 2 || state.search.length === 0) {
          loadSpreadsheets();
        }
      }, 300);
    });

    document.getElementById('gsSpreadsheetsTableBody')?.addEventListener('click', handleSpreadsheetActions);
    document.getElementById('gsReadRangeBtn')?.addEventListener('click', readRange);
    document.getElementById('gsWriteRangeBtn')?.addEventListener('click', openWriteModal);
    document.getElementById('gsAppendRowsBtn')?.addEventListener('click', openAppendModal);
    document.getElementById('gsClearRangeBtn')?.addEventListener('click', clearRange);
    document.getElementById('gsSaveWriteBtn')?.addEventListener('click', writeRange);
    document.getElementById('gsSaveAppendBtn')?.addEventListener('click', appendRows);

    document.getElementById('gsAddSheetBtn')?.addEventListener('click', addSheet);
  }

  // ── Stats ──────────────────────────────────────────────────────────────

  async function loadStats() {
    const { ok, data } = await Http.get(window.GS_ROUTES.stats);
    if (!ok || !data.success) return;
    const s = data.data || {};
    setText('gsStatSpreadsheets', s.total_spreadsheets || 0);
    setText('gsStatSheets', s.total_sheets || 0);
    if (s.last_sync_at) setText('gsLastSyncLabel', new Date(s.last_sync_at).toLocaleString());
  }

  // ── Spreadsheets ───────────────────────────────────────────────────────

  async function loadSpreadsheets() {
    const tbody = document.getElementById('gsSpreadsheetsTableBody');
    if (tbody) tbody.innerHTML = skeletonRows(6, 5);

    const params = { search: state.search };
    const { ok, data } = await Http.get(window.GS_ROUTES.spreadsheetsData, params);

    if (!ok || !data.success) {
      if (tbody) tbody.innerHTML = emptyRow('Impossible de charger les feuilles de calcul.');
      Toast.error('Erreur', data.message || 'Impossible de charger les feuilles de calcul.');
      return;
    }

    state.spreadsheets = data.data?.spreadsheets || [];
    renderSpreadsheets();
    setText('gsCount', `${state.spreadsheets.length} résultat(s)`);
  }

  function renderSpreadsheets() {
    const tbody = document.getElementById('gsSpreadsheetsTableBody');
    if (!tbody) return;

    if (!state.spreadsheets.length) {
      tbody.innerHTML = emptyRow('Aucune feuille de calcul trouvée.');
      return;
    }

    tbody.innerHTML = state.spreadsheets.map((ss, idx) => {
      const modified = ss.modified_at ? new Date(ss.modified_at).toLocaleString() : '-';
      const created  = ss.created_at  ? new Date(ss.created_at).toLocaleString()  : '-';
      const shared   = ss.is_shared
        ? '<span style="background:#dbeafe;color:#1d4ed8;padding:2px 8px;border-radius:99px;font-size:10.5px;font-weight:600;">Partagé</span>'
        : '';

      return `
        <tr data-index="${idx}" class="gs-spreadsheet-row">
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:34px;height:34px;background:#0f9d5818;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-file-excel" style="color:#0f9d58;font-size:16px;"></i>
              </div>
              <div>
                <div style="font-weight:var(--fw-medium);color:var(--c-ink);">${esc(ss.title || 'Untitled')}</div>
                <div style="font-size:11.5px;color:var(--c-ink-40);font-family:monospace;">${esc(ss.spreadsheet_id)}</div>
              </div>
            </div>
          </td>
          <td>${esc(created)}</td>
          <td>${esc(modified)}</td>
          <td>${shared}</td>
          <td>
            <div class="row-actions" style="justify-content:flex-end;padding-right:4px;opacity:1;">
              ${ss.spreadsheet_url ? `<a href="${esc(ss.spreadsheet_url)}" target="_blank" rel="noopener" class="btn-icon" title="Ouvrir dans Google Sheets"><i class="fas fa-arrow-up-right-from-square"></i></a>` : ''}
              <button class="btn-icon" data-action="open" data-index="${idx}" title="Lire/éditer les données"><i class="fas fa-table-cells"></i></button>
              <button class="btn-icon" data-action="rename" data-index="${idx}" title="Renommer"><i class="fas fa-pen"></i></button>
              <button class="btn-icon" data-action="duplicate" data-index="${idx}" title="Dupliquer"><i class="fas fa-copy"></i></button>
              <button class="btn-icon danger" data-action="delete" data-index="${idx}" title="Supprimer"><i class="fas fa-trash"></i></button>
            </div>
          </td>
        </tr>`;
    }).join('');
  }

  function handleSpreadsheetActions(e) {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;

    const action = btn.dataset.action;
    const idx    = parseInt(btn.dataset.index, 10);
    const ss     = state.spreadsheets[idx];
    if (!ss) return;

    if (action === 'open')      openDataModal(ss);
    if (action === 'rename')    renameSpreadsheet(ss);
    if (action === 'duplicate') duplicateSpreadsheet(ss);
    if (action === 'delete')    deleteSpreadsheet(ss);
  }

  // ── Create spreadsheet ─────────────────────────────────────────────────

  async function createSpreadsheet() {
    const titleInput   = document.getElementById('gsSpreadsheetTitle');
    const sheetsInput  = document.getElementById('gsSheetTitles');
    const title        = (titleInput?.value || '').trim();

    if (!title) { Toast.error('Validation', 'Le titre est obligatoire.'); return; }

    const sheetTitles = (sheetsInput?.value || '')
      .split(',')
      .map(s => s.trim())
      .filter(Boolean);

    const { ok, data } = await Http.post(window.GS_ROUTES.createSpreadsheet, {
      title,
      sheet_titles: sheetTitles.length ? sheetTitles : ['Sheet1'],
    });

    if (!ok || !data.success) {
      Toast.error('Erreur', data.message || 'Impossible de créer la feuille.');
      return;
    }

    if (titleInput)  titleInput.value  = '';
    if (sheetsInput) sheetsInput.value = '';
    Modal.close(document.getElementById('gsCreateModal'));
    Toast.success('Succès', data.message || 'Feuille créée.');
    loadSpreadsheets();
    loadStats();
  }

  // ── Rename ─────────────────────────────────────────────────────────────

  async function renameSpreadsheet(ss) {
    const name = window.prompt('Nouveau titre', ss.title || '');
    if (!name || !name.trim()) return;

    const resp = await fetchWithMethod(
      `${window.GS_ROUTES.spreadsheetBase}/${encodeURIComponent(ss.spreadsheet_id)}/rename`,
      'PATCH',
      { title: name.trim() }
    );

    if (!resp.ok || !resp.data.success) {
      Toast.error('Erreur', resp.data.message || 'Impossible de renommer.');
      return;
    }
    Toast.success('Succès', resp.data.message || 'Feuille renommée.');
    loadSpreadsheets();
  }

  // ── Duplicate ──────────────────────────────────────────────────────────

  async function duplicateSpreadsheet(ss) {
    const name = window.prompt('Titre de la copie', `Copie de ${ss.title || ''}`) || '';
    const resp = await Http.post(
      `${window.GS_ROUTES.spreadsheetBase}/${encodeURIComponent(ss.spreadsheet_id)}/duplicate`,
      { title: name.trim() }
    );

    if (!resp.ok || !resp.data.success) {
      Toast.error('Erreur', resp.data.message || 'Impossible de dupliquer.');
      return;
    }
    Toast.success('Succès', resp.data.message || 'Feuille dupliquée.');
    loadSpreadsheets();
    loadStats();
  }

  // ── Delete ─────────────────────────────────────────────────────────────

  async function deleteSpreadsheet(ss) {
    Modal.confirm({
      title:       `Supprimer "${ss.title}" ?`,
      message:     'Cette feuille sera supprimée définitivement de Google Drive.',
      confirmText: 'Supprimer',
      type:        'danger',
      onConfirm:   async () => {
        const resp = await fetchWithMethod(
          `${window.GS_ROUTES.spreadsheetBase}/${encodeURIComponent(ss.spreadsheet_id)}`,
          'DELETE',
          {}
        );
        if (!resp.ok || !resp.data.success) {
          Toast.error('Erreur', resp.data.message || 'Impossible de supprimer.');
          return;
        }
        Toast.success('Supprimée', resp.data.message || 'Feuille supprimée.');
        loadSpreadsheets();
        loadStats();
      },
    });
  }

  // ── Data modal (read / write) ──────────────────────────────────────────

  async function openDataModal(ss) {
    state.currentSpreadsheet = ss;
    state.dataLoaded         = false;

    setText('gsDataModalTitle', ss.title || 'Feuille de calcul');

    // Charger les onglets
    const loaderWrap = document.getElementById('gsSheetTabsLoader');
    if (loaderWrap) loaderWrap.innerHTML = '<span style="font-size:12px;color:var(--c-ink-40);">Chargement des onglets…</span>';

    const { ok, data } = await Http.get(`${window.GS_ROUTES.spreadsheetBase}/${encodeURIComponent(ss.spreadsheet_id)}`);

    if (!ok || !data.success) {
      Toast.error('Erreur', data.message || 'Impossible de charger la feuille.');
      return;
    }

    state.currentSheets = data.data?.sheets || [];

    // Render tabs
    if (loaderWrap) {
      loaderWrap.innerHTML = state.currentSheets.map((sh, idx) => `
        <button class="gs-sheet-tab ${idx === 0 ? 'active' : ''}"
                data-sheet-title="${esc(sh.title)}"
                onclick="GoogleSheetsModule.selectSheet(this, '${esc(sh.title)}')"
                title="${esc(sh.title)}">
          <i class="fas fa-table" style="font-size:10px;"></i>
          ${esc(sh.title)}
          <span class="gs-sheet-tab-actions" style="display:flex;gap:3px;margin-left:4px;">
            <span style="font-size:9px;color:rgba(255,255,255,.7);cursor:pointer;" data-sheet-rename="${sh.sheet_id}" onclick="event.stopPropagation();GoogleSheetsModule.renameSheetPrompt(${sh.sheet_id},'${esc(sh.title)}')" title="Renommer"><i class="fas fa-pen"></i></span>
            <span style="font-size:9px;color:rgba(255,255,255,.7);cursor:pointer;" data-sheet-delete="${sh.sheet_id}" onclick="event.stopPropagation();GoogleSheetsModule.deleteSheetConfirm(${sh.sheet_id},'${esc(sh.title)}')" title="Supprimer"><i class="fas fa-times"></i></span>
          </span>
        </button>`).join('');
    }

    if (state.currentSheets.length > 0) {
      state.currentSheetTitle = state.currentSheets[0].title;
      const rangeInput = document.getElementById('gsRangeInput');
      if (rangeInput) rangeInput.value = `${state.currentSheetTitle}!A1:Z50`;
    }

    Modal.open(document.getElementById('gsDataModal'));
  }

  function selectSheet(btn, sheetTitle) {
    document.querySelectorAll('.gs-sheet-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    state.currentSheetTitle = sheetTitle;

    const rangeInput = document.getElementById('gsRangeInput');
    if (rangeInput) rangeInput.value = `${sheetTitle}!A1:Z50`;

    // Clear table
    const wrap = document.getElementById('gsDataTableWrap');
    if (wrap) wrap.innerHTML = `<div style="text-align:center;padding:40px;color:var(--c-ink-40);"><i class="fas fa-table-cells" style="font-size:28px;margin-bottom:8px;display:block;opacity:.3;"></i><p>Cliquez sur "Lire" pour charger les données de cet onglet.</p></div>`;
  }

  // ── Read range ─────────────────────────────────────────────────────────

  async function readRange() {
    const rangeInput = document.getElementById('gsRangeInput');
    const range      = (rangeInput?.value || '').trim();
    if (!range || !state.currentSpreadsheet) return;

    const wrap = document.getElementById('gsDataTableWrap');
    if (wrap) wrap.innerHTML = skeletonTable();

    const { ok, data } = await Http.get(
      `${window.GS_ROUTES.spreadsheetBase}/${encodeURIComponent(state.currentSpreadsheet.spreadsheet_id)}/values`,
      { range }
    );

    if (!ok || !data.success) {
      if (wrap) wrap.innerHTML = `<div style="text-align:center;padding:40px;color:var(--c-danger);">${esc(data.message || 'Erreur')}</div>`;
      Toast.error('Erreur', data.message || 'Impossible de lire la plage.');
      return;
    }

    const payload = data.data || {};
    renderDataTable(payload.values || [], payload.range || range);
  }

  function renderDataTable(values, range) {
    const wrap = document.getElementById('gsDataTableWrap');
    if (!wrap) return;

    if (!values.length) {
      wrap.innerHTML = `<div style="text-align:center;padding:40px;color:var(--c-ink-40);"><i class="fas fa-inbox" style="font-size:28px;opacity:.3;display:block;margin-bottom:8px;"></i><p>La plage <code>${esc(range)}</code> est vide.</p></div>`;
      return;
    }

    // Déterminer le nombre max de colonnes
    const maxCols = Math.max(...values.map(r => r.length));
    const colLetters = Array.from({ length: maxCols }, (_, i) => colLetter(i));

    const thead = `<tr>
      <th class="gs-col-header" style="width:36px;">#</th>
      ${colLetters.map(l => `<th class="gs-col-header">${esc(l)}</th>`).join('')}
    </tr>`;

    const tbody = values.map((row, rowIdx) => {
      const cells = Array.from({ length: maxCols }, (_, colIdx) => {
        const val = row[colIdx] ?? '';
        return `<td title="${esc(val)}">${val !== '' ? esc(val) : '<span class="gs-empty-cell">·</span>'}</td>`;
      }).join('');
      return `<tr><td class="gs-row-num">${rowIdx + 1}</td>${cells}</tr>`;
    }).join('');

    wrap.innerHTML = `
      <div class="gs-data-wrap">
        <table class="gs-data-table">
          <thead>${thead}</thead>
          <tbody>${tbody}</tbody>
        </table>
      </div>
      <div style="margin-top:10px;font-size:12px;color:var(--c-ink-40);">
        <i class="fas fa-info-circle"></i>
        Plage: <code>${esc(range)}</code> · ${values.length} ligne(s) · ${maxCols} colonne(s)
      </div>`;
  }

  // ── Write ──────────────────────────────────────────────────────────────

  function openWriteModal() {
    const rangeInput = document.getElementById('gsRangeInput');
    const wr = document.getElementById('gsWriteRange');
    if (wr && rangeInput) wr.value = rangeInput.value;
    Modal.open(document.getElementById('gsWriteModal'));
  }

  async function writeRange() {
    const rangeEl  = document.getElementById('gsWriteRange');
    const dataEl   = document.getElementById('gsWriteData');
    const range    = (rangeEl?.value || '').trim();
    const rawData  = (dataEl?.value || '').trim();

    if (!range || !rawData || !state.currentSpreadsheet) {
      Toast.error('Validation', 'La plage et les données sont obligatoires.');
      return;
    }

    const values = parseCsvData(rawData);
    if (!values.length) { Toast.error('Validation', 'Format de données invalide.'); return; }

    const resp = await fetchWithMethod(
      `${window.GS_ROUTES.spreadsheetBase}/${encodeURIComponent(state.currentSpreadsheet.spreadsheet_id)}/values`,
      'PUT',
      { range, values }
    );

    if (!resp.ok || !resp.data.success) {
      Toast.error('Erreur', resp.data.message || 'Impossible d’écrire les données.');
      return;
    }

    Modal.close(document.getElementById('gsWriteModal'));
    Toast.success('Écriture', `${resp.data.data?.updated_cells || 0} cellule(s) mises à jour.`);
    readRange();
  }

  // ── Append ─────────────────────────────────────────────────────────────

  function openAppendModal() {
    const rangeInput = document.getElementById('gsRangeInput');
    const ar = document.getElementById('gsAppendRange');
    if (ar && rangeInput) ar.value = rangeInput.value;
    Modal.open(document.getElementById('gsAppendModal'));
  }

  async function appendRows() {
    const rangeEl = document.getElementById('gsAppendRange');
    const dataEl  = document.getElementById('gsAppendData');
    const range   = (rangeEl?.value || '').trim();
    const rawData = (dataEl?.value || '').trim();

    if (!range || !rawData || !state.currentSpreadsheet) {
      Toast.error('Validation', 'La plage et les données sont obligatoires.');
      return;
    }

    const values = parseCsvData(rawData);
    if (!values.length) { Toast.error('Validation', 'Format de données invalide.'); return; }

    const { ok, data } = await Http.post(
      `${window.GS_ROUTES.spreadsheetBase}/${encodeURIComponent(state.currentSpreadsheet.spreadsheet_id)}/values/append`,
      { range, values }
    );

    if (!ok || !data.success) {
      Toast.error('Erreur', data.message || 'Impossible d’ajouter les lignes.');
      return;
    }

    Modal.close(document.getElementById('gsAppendModal'));
    Toast.success('Ajout', `${data.data?.updated_rows || 0} ligne(s) ajoutée(s).`);
    readRange();
  }

  // ── Clear range ────────────────────────────────────────────────────────

  async function clearRange() {
    const rangeInput = document.getElementById('gsRangeInput');
    const range      = (rangeInput?.value || '').trim();
    if (!range || !state.currentSpreadsheet) return;

    Modal.confirm({
      title:       `Vider la plage "${range}" ?`,
      message:     'Toutes les valeurs de cette plage seront supprimées.',
      confirmText: 'Vider',
      type:        'danger',
      onConfirm:   async () => {
        const resp = await fetchWithMethod(
          `${window.GS_ROUTES.spreadsheetBase}/${encodeURIComponent(state.currentSpreadsheet.spreadsheet_id)}/values`,
          'DELETE',
          { range }
        );
        if (!resp.ok || !resp.data.success) {
          Toast.error('Erreur', resp.data.message || 'Impossible de vider la plage.');
          return;
        }
        Toast.success('Plage vidée', resp.data.message || 'Plage vidée.');
        readRange();
      },
    });
  }

  // ── Sheet tab actions ──────────────────────────────────────────────────

  async function addSheet() {
    const nameInput = document.getElementById('gsNewSheetTitle');
    const name      = (nameInput?.value || '').trim();
    if (!name || !state.currentSpreadsheet) { Toast.error('Validation', 'Le titre de l’onglet est obligatoire.'); return; }

    const { ok, data } = await Http.post(
      `${window.GS_ROUTES.spreadsheetBase}/${encodeURIComponent(state.currentSpreadsheet.spreadsheet_id)}/sheets`,
      { title: name }
    );

    if (!ok || !data.success) {
      Toast.error('Erreur', data.message || 'Impossible d’ajouter un onglet.');
      return;
    }

    if (nameInput) nameInput.value = '';
    Toast.success('Ajout', data.message || 'Onglet ajouté.');
    // Refresh spreadsheet to reload tabs
    openDataModal(state.currentSpreadsheet);
  }

  function renameSheetPrompt(sheetId, currentTitle) {
    const name = window.prompt('Nouveau nom de l’onglet', currentTitle || '');
    if (!name || !name.trim() || !state.currentSpreadsheet) return;

    fetchWithMethod(
      `${window.GS_ROUTES.spreadsheetBase}/${encodeURIComponent(state.currentSpreadsheet.spreadsheet_id)}/sheets/${sheetId}/rename`,
      'PATCH',
      { title: name.trim() }
    ).then(resp => {
      if (!resp.ok || !resp.data.success) { Toast.error('Erreur', resp.data.message || 'Impossible de renommer.'); return; }
      Toast.success('Renommé', resp.data.message || 'Onglet renommé.');
      openDataModal(state.currentSpreadsheet);
    });
  }

  function deleteSheetConfirm(sheetId, title) {
    Modal.confirm({
      title:       `Supprimer l’onglet "${title}" ?`,
      message:     'Toutes les données de cet onglet seront perdues.',
      confirmText: 'Supprimer',
      type:        'danger',
      onConfirm:   async () => {
        const resp = await fetchWithMethod(
          `${window.GS_ROUTES.spreadsheetBase}/${encodeURIComponent(state.currentSpreadsheet.spreadsheet_id)}/sheets/${sheetId}`,
          'DELETE',
          {}
        );
        if (!resp.ok || !resp.data.success) { Toast.error('Erreur', resp.data.message || 'Impossible de supprimer.'); return; }
        Toast.success('Supprimé', resp.data.message || 'Onglet supprimé.');
        openDataModal(state.currentSpreadsheet);
      },
    });
  }

  // ── Disconnect ─────────────────────────────────────────────────────────

  async function disconnect() {
    Modal.confirm({
      title:       'Déconnecter Google Sheets ?',
      message:     'Les jetons OAuth seront supprimés pour ce tenant.',
      confirmText: 'Déconnecter',
      type:        'danger',
      onConfirm:   async () => {
        const { ok, data } = await Http.post(window.GS_ROUTES.disconnect, {});
        if (!ok || !data.success) { Toast.error('Erreur', data.message || 'Impossible de déconnecter Google Sheets.'); return; }
        Toast.success('Déconnecté', data.message || 'Google Sheets déconnecté.');
        setTimeout(() => window.location.reload(), 700);
      },
    });
  }

  // ── Helpers ────────────────────────────────────────────────────────────

  async function fetchWithMethod(url, method, payload) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type':   'application/json',
        'Accept':         'application/json',
        'X-CSRF-TOKEN':   token,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: ['GET', 'HEAD'].includes(method) ? undefined : JSON.stringify(payload || {}),
    });
    const data = await response.json().catch(() => ({}));
    return { ok: response.ok, status: response.status, data };
  }

  function parseCsvData(raw) {
    return raw.split('\n').map(line =>
      line.split('\t').map(cell => cell.replace(/\\n/g, '\n'))
    ).filter(row => row.some(c => c.trim() !== ''));
  }

  function colLetter(idx) {
    let result = '';
    let n = idx;
    while (n >= 0) {
      result = String.fromCharCode(65 + (n % 26)) + result;
      n = Math.floor(n / 26) - 1;
    }
    return result;
  }

  function skeletonRows(count, cols) {
    return Array.from({ length: count }, () =>
      `<tr>${Array.from({ length: cols }, () =>
        '<td><div class="skeleton" style="height:13px;"></div></td>'
      ).join('')}</tr>`
    ).join('');
  }

  function skeletonTable() {
    return `<div style="padding:20px;">${skeletonRows(8, 5)}</div>`;
  }

  function emptyRow(message) {
    return `<tr><td colspan="5"><div class="table-empty">
      <div class="table-empty-icon"><i class="fas fa-file-excel"></i></div>
      <h3>Aucune donnée</h3>
      <p>${esc(message)}</p>
    </div></td></tr>`;
  }

  function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = String(value);
  }

  function esc(value) {
    const div = document.createElement('div');
    div.textContent = value || '';
    return div.innerHTML;
  }

  return {
    boot,
    selectSheet,
    renameSheetPrompt,
    deleteSheetConfirm,
  };
})();

window.GoogleSheetsModule = GoogleSheetsModule;
