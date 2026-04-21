'use strict';

const GoogleCalendarModule = (() => {
  const state = {
    connected: false,
    selectedCalendarId: null,
    timezone: 'UTC',
    page: 1,
    perPage: 20,
    search: '',
    from: '',
    to: '',
    includeHolidays: true,
    events: [],
    calendars: [],
    editingEvent: null,
    loadingEvents: false,
    debounceTimer: null,
  };

  function boot(bootstrap = {}) {
    state.connected = !!bootstrap.connected;
    state.selectedCalendarId = bootstrap.selectedCalendarId || null;
    state.timezone = bootstrap.timezone || 'UTC';
    state.includeHolidays = bootstrap.includeHolidays !== false;

    bindActions();

    const includeHolidays = document.getElementById('gcIncludeHolidays');
    if (includeHolidays) {
      includeHolidays.checked = state.includeHolidays;
    }

    if (!state.connected) {
      return;
    }

    loadStats();
    loadCalendars(true);
    loadEvents(true);
  }

  function bindActions() {
    const searchInput = document.getElementById('gcSearchInput');
    searchInput?.addEventListener('input', () => {
      clearTimeout(state.debounceTimer);
      state.debounceTimer = setTimeout(() => {
        state.search = searchInput.value.trim();
        state.page = 1;
        loadEvents(false);
      }, 300);
    });

    document.getElementById('gcFromDate')?.addEventListener('change', (e) => {
      state.from = e.target.value || '';
      state.page = 1;
      loadEvents(false);
    });

    document.getElementById('gcToDate')?.addEventListener('change', (e) => {
      state.to = e.target.value || '';
      state.page = 1;
      loadEvents(false);
    });

    document.getElementById('gcIncludeHolidays')?.addEventListener('change', (e) => {
      state.includeHolidays = !!e.target.checked;
      state.page = 1;
      loadEvents(false);
    });

    document.getElementById('gcResetFilters')?.addEventListener('click', () => {
      state.search = '';
      state.from = '';
      state.to = '';
      state.page = 1;

      const search = document.getElementById('gcSearchInput');
      const from = document.getElementById('gcFromDate');
      const to = document.getElementById('gcToDate');
      if (search) search.value = '';
      if (from) from.value = '';
      if (to) to.value = '';
      const includeHolidays = document.getElementById('gcIncludeHolidays');
      if (includeHolidays) {
        includeHolidays.checked = true;
      }
      state.includeHolidays = true;

      loadEvents(false);
    });

    document.getElementById('gcSyncBtn')?.addEventListener('click', syncNow);
    document.getElementById('gcDisconnectBtn')?.addEventListener('click', disconnect);

    document.getElementById('gcCreateEventBtn')?.addEventListener('click', () => {
      resetEventForm();
      state.editingEvent = null;
      setModalTitle('Create Event');
    });

    document.getElementById('gcSaveEventBtn')?.addEventListener('click', saveEvent);

    document.getElementById('gcEventsTableBody')?.addEventListener('click', (e) => {
      const editBtn = e.target.closest('[data-gc-edit]');
      if (editBtn) {
        const idx = parseInt(editBtn.dataset.gcEdit, 10);
        if (!Number.isNaN(idx)) {
          editEvent(idx);
        }
        return;
      }

      const delBtn = e.target.closest('[data-gc-delete]');
      if (delBtn) {
        const idx = parseInt(delBtn.dataset.gcDelete, 10);
        if (!Number.isNaN(idx)) {
          deleteEvent(idx);
        }
      }
    });
  }

  async function loadCalendars(refresh = false) {
    const { ok, data } = await Http.get(window.GCAL_ROUTES.calendarsData, { refresh: refresh ? 1 : 0 });

    if (!ok || !data.success) {
      Toast.error('Error', data.message || 'Unable to load calendars.');
      return;
    }

    state.calendars = data.data || [];

    if (!state.selectedCalendarId) {
      const selected = state.calendars.find((c) => c.is_selected) || state.calendars.find((c) => c.is_primary) || state.calendars[0];
      state.selectedCalendarId = selected ? selected.calendar_id : null;
    }

    renderCalendars();
  }

  function renderCalendars() {
    const wrap = document.getElementById('gcCalendarsList');
    if (!wrap) return;

    if (!state.calendars.length) {
      wrap.innerHTML = `
        <div class="table-empty" style="padding:24px 12px;">
          <div class="table-empty-icon"><i class="fas fa-calendar-xmark"></i></div>
          <h3>No calendars found</h3>
          <p>Run a sync after connecting Google Calendar.</p>
        </div>`;
      return;
    }

    wrap.innerHTML = state.calendars.map((calendar) => {
      const active = state.selectedCalendarId === calendar.calendar_id;
      const badge = calendar.is_primary ? '<span class="nav-badge" style="margin-left:8px;">Primary</span>' : '';

      return `
        <button class="gc-calendar-item ${active ? 'active' : ''}" data-calendar-id="${esc(calendar.calendar_id)}" type="button">
          <span class="gc-calendar-color" style="background:${esc(calendar.background_color || '#2563eb')};"></span>
          <span class="gc-calendar-name">${esc(calendar.summary || calendar.calendar_id)}</span>
          ${badge}
        </button>`;
    }).join('');

    wrap.querySelectorAll('[data-calendar-id]').forEach((btn) => {
      btn.addEventListener('click', () => selectCalendar(btn.dataset.calendarId));
    });
  }

  async function selectCalendar(calendarId) {
    if (!calendarId || calendarId === state.selectedCalendarId) return;

    const { ok, data } = await Http.post(window.GCAL_ROUTES.selectCalendar, { calendar_id: calendarId });

    if (!ok || !data.success) {
      Toast.error('Error', data.message || 'Unable to select calendar.');
      return;
    }

    state.selectedCalendarId = calendarId;
    renderCalendars();
    state.page = 1;

    Toast.success('Success', 'Calendar selected.');
    loadEvents(true);
  }

  async function loadEvents(refresh = false) {
    if (state.loadingEvents) return;
    state.loadingEvents = true;

    const tbody = document.getElementById('gcEventsTableBody');
    if (tbody) {
      tbody.innerHTML = skeletonRows(5, 6);
    }

    const { ok, data } = await Http.get(window.GCAL_ROUTES.eventsData, {
      calendar_id: state.selectedCalendarId || '',
      search: state.search,
      from: state.from,
      to: state.to,
      per_page: state.perPage,
      page: state.page,
      refresh: refresh ? 1 : 0,
      include_holidays: state.includeHolidays ? 1 : 0,
    });

    state.loadingEvents = false;

    if (!ok || !data.success) {
      Toast.error('Error', data.message || 'Unable to load events.');
      if (tbody) tbody.innerHTML = emptyRow('Unable to load events.');
      return;
    }

    state.events = data.data || [];

    renderEvents();
    renderPagination(data);

    const count = document.getElementById('gcCount');
    if (count) count.textContent = `${data.total || 0} result(s)`;

    const lastSync = document.getElementById('gcLastSyncLabel');
    if (refresh && lastSync) {
      lastSync.textContent = new Date().toLocaleString();
    }
  }

  function renderEvents() {
    const tbody = document.getElementById('gcEventsTableBody');
    if (!tbody) return;

    if (!state.events.length) {
      tbody.innerHTML = emptyRow('No events found for the selected filters.');
      return;
    }

    tbody.innerHTML = state.events.map((event, idx) => {
      const statusBadge = statusToBadge(event.status || 'confirmed');
      const calendarName = calendarLabel(event.calendar_id);
      const holidayBadge = event.is_holiday
        ? '<span class="badge badge-sent" style="margin-left:8px;">Holiday</span>'
        : '';

      return `
        <tr>
          <td>
            <div style="font-weight:var(--fw-medium);display:flex;align-items:center;gap:6px;flex-wrap:wrap;">${esc(event.summary || '(No title)')} ${holidayBadge}</div>
            ${event.location ? `<div style="font-size:12px;color:var(--c-ink-40);"><i class="fas fa-location-dot"></i> ${esc(event.location)}</div>` : ''}
          </td>
          <td>${esc(calendarName)}</td>
          <td>${esc(event.start_display || '-')}</td>
          <td>${esc(event.end_display || '-')}</td>
          <td>${statusBadge}</td>
          <td>
            <div class="row-actions" style="justify-content:flex-end;padding-right:4px;opacity:1;">
              ${event.html_link ? `<a href="${esc(event.html_link)}" target="_blank" rel="noopener" class="btn-icon" title="Open in Google"><i class="fas fa-arrow-up-right-from-square"></i></a>` : ''}
              <button class="btn-icon" data-gc-edit="${idx}" title="Edit"><i class="fas fa-pen"></i></button>
              <button class="btn-icon danger" data-gc-delete="${idx}" title="Delete"><i class="fas fa-trash"></i></button>
            </div>
          </td>
        </tr>`;
    }).join('');
  }

  function renderPagination(payload) {
    const wrap = document.getElementById('gcPaginationControls');
    const info = document.getElementById('gcPaginationInfo');
    if (!wrap) return;

    const currentPage = payload.current_page || 1;
    const lastPage = payload.last_page || 1;

    if (info) {
      info.textContent = `Showing ${payload.from || 0} to ${payload.to || 0} of ${payload.total || 0} event(s)`;
    }

    const pages = [];
    const start = Math.max(1, currentPage - 2);
    const end = Math.min(lastPage, currentPage + 2);

    for (let i = start; i <= end; i += 1) {
      pages.push(i);
    }

    wrap.innerHTML = `
      <button class="page-btn" ${currentPage <= 1 ? 'disabled' : ''} data-gc-page="${currentPage - 1}">
        <i class="fas fa-chevron-left"></i>
      </button>
      ${pages.map((p) => `<button class="page-btn ${p === currentPage ? 'active' : ''}" data-gc-page="${p}">${p}</button>`).join('')}
      <button class="page-btn" ${currentPage >= lastPage ? 'disabled' : ''} data-gc-page="${currentPage + 1}">
        <i class="fas fa-chevron-right"></i>
      </button>`;

    wrap.querySelectorAll('[data-gc-page]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const page = parseInt(btn.dataset.gcPage, 10);
        if (!Number.isNaN(page) && page > 0 && page !== state.page) {
          state.page = page;
          loadEvents(false);
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      });
    });
  }

  async function loadStats() {
    const { ok, data } = await Http.get(window.GCAL_ROUTES.stats);

    if (!ok || !data.success) {
      return;
    }

    const stats = data.data || {};

    setText('gcStatCalendars', stats.calendars_count || 0);
    setText('gcStatToday', stats.events_today || 0);
    setText('gcStatMonth', stats.events_this_month || 0);
    setText('gcStatNext', stats.events_next_30_days || 0);
    setText('gcStatHolidays', stats.holiday_events_this_year || 0);

    if (stats.last_sync_at) {
      setText('gcLastSyncLabel', new Date(stats.last_sync_at).toLocaleString());
    }
  }

  async function syncNow() {
    const btn = document.getElementById('gcSyncBtn');
    if (btn) CrmForm.setLoading(btn, true);

    const { ok, data } = await Http.post(window.GCAL_ROUTES.sync, {
      calendar_id: state.selectedCalendarId || null,
      from: state.from || null,
      to: state.to || null,
      include_holidays: state.includeHolidays ? 1 : 0,
    });

    if (btn) CrmForm.setLoading(btn, false);

    if (!ok || !data.success) {
      Toast.error('Error', data.message || 'Synchronization failed.');
      return;
    }

    Toast.success('Success', data.message || 'Synchronization completed.');

    await loadCalendars(false);
    await loadStats();
    await loadEvents(false);
  }

  async function disconnect() {
    Modal.confirm({
      title: 'Disconnect Google Calendar?',
      message: 'OAuth tokens will be removed for this tenant.',
      confirmText: 'Disconnect',
      type: 'danger',
      onConfirm: async () => {
        const { ok, data } = await Http.post(window.GCAL_ROUTES.disconnect, {});
        if (!ok || !data.success) {
          Toast.error('Error', data.message || 'Unable to disconnect.');
          return;
        }

        Toast.success('Disconnected', data.message || 'Google Calendar disconnected.');
        setTimeout(() => window.location.reload(), 700);
      },
    });
  }

  function editEvent(index) {
    const event = state.events[index];
    if (!event) return;

    state.editingEvent = event;
    setModalTitle('Edit Event');

    resetEventForm();

    setFieldValue('gcEventCalendarId', event.calendar_id || state.selectedCalendarId || '');
    setFieldValue('gcEventId', event.event_id || '');
    setFieldValue('gcSummary', event.summary || '');
    setFieldValue('gcLocation', event.location || '');
    setFieldValue('gcVisibility', event.visibility || 'default');
    setFieldValue('gcDescription', event.description || '');

    if (event.start_at) {
      setFieldValue('gcStartAt', toDateTimeLocal(event.start_at));
    }

    if (event.end_at) {
      setFieldValue('gcEndAt', toDateTimeLocal(event.end_at));
    }

    if (Array.isArray(event.attendees) && event.attendees.length) {
      const emails = event.attendees.map((att) => att.email).filter(Boolean);
      setFieldValue('gcAttendees', emails.join(', '));
    }

    Modal.open(document.getElementById('gcEventModal'));
  }

  async function deleteEvent(index) {
    const event = state.events[index];
    if (!event) return;

    Modal.confirm({
      title: 'Delete this event?',
      message: `Event "${event.summary || '(No title)'}" will be removed from Google Calendar.`,
      confirmText: 'Delete',
      type: 'danger',
      onConfirm: async () => {
        const url = `${window.GCAL_ROUTES.eventsBase}/${encodeURIComponent(event.calendar_id)}/${encodeURIComponent(event.event_id)}`;
        const { ok, data } = await Http.delete(url);

        if (!ok || !data.success) {
          Toast.error('Error', data.message || 'Unable to delete event.');
          return;
        }

        Toast.success('Deleted', data.message || 'Event deleted.');
        loadEvents(false);
        loadStats();
      },
    });
  }

  async function saveEvent() {
    const form = document.getElementById('gcEventForm');
    const btn = document.getElementById('gcSaveEventBtn');
    if (!form || !btn) return;

    clearFormErrors(form);

    const payload = {
      calendar_id: getFieldValue('gcEventCalendarId') || state.selectedCalendarId || '',
      summary: getFieldValue('gcSummary').trim(),
      start_at: getFieldValue('gcStartAt'),
      end_at: getFieldValue('gcEndAt'),
      location: getFieldValue('gcLocation').trim(),
      visibility: getFieldValue('gcVisibility'),
      reminder_minutes: getFieldValue('gcReminder') || null,
      attendees: getFieldValue('gcAttendees').trim(),
      description: getFieldValue('gcDescription').trim(),
      timezone: state.timezone,
    };

    const validationErrors = validatePayload(payload);
    if (Object.keys(validationErrors).length) {
      showFormErrors(form, validationErrors);
      Toast.error('Validation', 'Please correct form errors.');
      return;
    }

    CrmForm.setLoading(btn, true);

    let response;
    if (state.editingEvent) {
      const url = `${window.GCAL_ROUTES.eventsBase}/${encodeURIComponent(state.editingEvent.calendar_id)}/${encodeURIComponent(state.editingEvent.event_id)}`;
      response = await Http.put(url, payload);
    } else {
      response = await Http.post(window.GCAL_ROUTES.eventsStore, payload);
    }

    CrmForm.setLoading(btn, false);

    if (!response.ok) {
      if (response.status === 422 && response.data?.errors) {
        showFormErrors(form, response.data.errors);
      }

      Toast.error('Error', response.data?.message || 'Unable to save event.');
      return;
    }

    Toast.success('Success', response.data?.message || 'Event saved.');
    Modal.close(document.getElementById('gcEventModal'));

    state.editingEvent = null;
    loadEvents(false);
    loadStats();
  }

  function validatePayload(payload) {
    const errors = {};

    if (!payload.calendar_id) {
      errors.calendar_id = ['Please select a calendar first.'];
    }

    if (!payload.summary) {
      errors.summary = ['Title is required.'];
    }

    if (!payload.start_at) {
      errors.start_at = ['Start date is required.'];
    }

    if (!payload.end_at) {
      errors.end_at = ['End date is required.'];
    }

    if (payload.start_at && payload.end_at) {
      const start = new Date(payload.start_at);
      const end = new Date(payload.end_at);
      if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end <= start) {
        errors.end_at = ['End date must be after start date.'];
      }
    }

    if (payload.attendees) {
      const emails = payload.attendees.split(',').map((v) => v.trim()).filter(Boolean);
      const invalid = emails.find((email) => !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email));
      if (invalid) {
        errors.attendees = ['One or more attendee emails are invalid.'];
      }
    }

    return errors;
  }

  function resetEventForm() {
    const form = document.getElementById('gcEventForm');
    if (!form) return;

    clearFormErrors(form);
    form.reset();

    setFieldValue('gcEventCalendarId', state.selectedCalendarId || '');
    setFieldValue('gcEventId', '');
    setFieldValue('gcVisibility', 'default');

    const now = new Date();
    const plusHour = new Date(now.getTime() + 60 * 60 * 1000);

    setFieldValue('gcStartAt', toDateTimeLocal(now));
    setFieldValue('gcEndAt', toDateTimeLocal(plusHour));
  }

  function setModalTitle(text) {
    setText('gcEventModalTitle', text);
  }

  function statusToBadge(status) {
    const map = {
      confirmed: { cls: 'badge-paid', label: 'Confirmed' },
      tentative: { cls: 'badge-sent', label: 'Tentative' },
      cancelled: { cls: 'badge-cancelled', label: 'Cancelled' },
    };
    const cfg = map[status] || { cls: 'badge-draft', label: status || 'Unknown' };
    return `<span class="badge ${cfg.cls}">${esc(cfg.label)}</span>`;
  }

  function calendarLabel(calendarId) {
    const found = state.calendars.find((c) => c.calendar_id === calendarId);
    return found ? (found.summary || calendarId) : calendarId;
  }

  function skeletonRows(count, cols) {
    return Array.from({ length: count }, () => `<tr>${Array.from({ length: cols }, () => '<td><div class="skeleton" style="height:13px;"></div></td>').join('')}</tr>`).join('');
  }

  function emptyRow(message) {
    return `<tr><td colspan="6"><div class="table-empty"><div class="table-empty-icon"><i class="fas fa-calendar"></i></div><h3>No data</h3><p>${esc(message)}</p></div></td></tr>`;
  }

  function toDateTimeLocal(value) {
    const date = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(date.getTime())) return '';

    const offsetMs = date.getTimezoneOffset() * 60 * 1000;
    const local = new Date(date.getTime() - offsetMs);
    return local.toISOString().slice(0, 16);
  }

  function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = String(value);
  }

  function setFieldValue(id, value) {
    const el = document.getElementById(id);
    if (el) el.value = value;
  }

  function getFieldValue(id) {
    const el = document.getElementById(id);
    return el ? el.value : '';
  }

  function esc(value) {
    const div = document.createElement('div');
    div.textContent = value || '';
    return div.innerHTML;
  }

  function clearFormErrors(form) {
    form.querySelectorAll('.form-error').forEach((el) => el.remove());
    form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
  }

  function showFormErrors(form, errors) {
    Object.entries(errors).forEach(([field, messages]) => {
      const input = form.querySelector(`[name="${field}"]`) || mapFieldAlias(form, field);
      if (!input) return;

      input.classList.add('is-invalid');

      const error = document.createElement('div');
      error.className = 'form-error';
      error.textContent = Array.isArray(messages) ? messages[0] : messages;
      input.parentNode.appendChild(error);
    });
  }

  function mapFieldAlias(form, field) {
    if (field === 'calendar_id') {
      return form.querySelector('#gcSummary');
    }
    return null;
  }

  return {
    boot,
  };
})();

window.GoogleCalendarModule = GoogleCalendarModule;
