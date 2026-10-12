/** Patient portal behaviour only. Visible content lives in patient-portal.html. */
document.addEventListener('DOMContentLoaded', () => {
  initPortalSearch();
  checkUrlParamsForRef();
});

function clonePortalTemplate(id) {
  const template = document.getElementById(id);
  return template ? template.content.firstElementChild.cloneNode(true) : null;
}

function checkUrlParamsForRef() {
  const params = new URLSearchParams(window.location.search);
  const ref = params.get('ref') || params.get('query');
  if (!ref) return;
  const input = document.getElementById('portalSearchQuery');
  if (input) { input.value = ref; searchAppointments(ref); }
}

function initPortalSearch() {
  const form = document.getElementById('portalSearchForm');
  if (!form) return;
  form.addEventListener('submit', e => {
    e.preventDefault();
    searchAppointments(document.getElementById('portalSearchQuery').value.trim());
  });
}

function searchAppointments(query) {
  const host = document.getElementById('portalResultsContainer');
  if (!host) return;
  host.replaceChildren();
  if (!query) {
    const prompt = clonePortalTemplate('portalPromptTemplate');
    if (prompt) host.appendChild(prompt);
    return;
  }
  const results = MGM_APP.findAppointment(query);
  if (!results.length) {
    const empty = clonePortalTemplate('portalNotFoundTemplate');
    if (empty) {
      empty.querySelector('[data-field="query"]').textContent = query;
      host.appendChild(empty);
    }
    return;
  }
  results.forEach(app => host.appendChild(buildPortalResult(app)));
}

function buildPortalResult(app) {
  const card = clonePortalTemplate('portalResultTemplate');
  const set = (key, value) => { const el = card.querySelector(`[data-field="${key}"]`); if (el) el.textContent = value; };
  set('refId', app.refId);
  set('createdAt', app.createdAt);
  set('patient', `${app.patientName} (${app.gender || 'N/A'}, ${app.age || 'N/A'} yrs)`);
  set('phone', app.phone);
  set('doctorName', app.doctorName);
  set('dept', app.dept);
  set('datetime', `${app.date} at ${app.timeSlot}`);
  set('doctorFee', app.doctorFee);
  const status = card.querySelector('[data-field="status"]');
  if (status) {
    status.textContent = `● ${app.status}`;
    status.style.background = app.status === 'Cancelled' ? '#fee2e2' : '#dcfce7';
    status.style.color = app.status === 'Cancelled' ? '#991b1b' : '#166534';
  }
  card.querySelector('[data-action="print"]')?.addEventListener('click', () => printReceipt(app.refId));
  const cancel = card.querySelector('[data-action="cancel"]');
  if (cancel) {
    if (app.status === 'Cancelled') cancel.remove();
    else cancel.addEventListener('click', () => cancelAppointment(app.refId));
  }
  return card;
}

function printReceipt(refId) {
  const app = MGM_APP.findAppointment(refId)[0];
  if (app && typeof showConfirmationReceipt === 'function') showConfirmationReceipt(app);
  else window.print();
}

function cancelAppointment(refId) {
  const prompt = document.getElementById('portalResultsContainer')?.dataset.cancelPrompt || 'Are you sure you want to cancel this appointment?';
  if (!confirm(prompt)) return;
  const updated = MGM_APP.getAppointments().map(app => app.refId === refId ? { ...app, status: 'Cancelled' } : app);
  localStorage.setItem(MGM_APP.STORAGE_KEY, JSON.stringify(updated));
  searchAppointments(refId);
}
