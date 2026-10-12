/** Appointment receipt behaviour only. Visible receipt content lives in HTML. */
function showConfirmationReceipt(app) {
  const modal = document.getElementById('receiptModal');
  if (!modal) { window.print(); return; }
  const set = (key, value) => { const el = modal.querySelector(`[data-receipt="${key}"]`); if (el) el.textContent = value; };
  set('refId', app.refId);
  set('status', `● ${app.status}`);
  set('patient', `${app.patientName} (${app.gender || 'N/A'}, ${app.age || 'N/A'} Yrs)`);
  set('phone', app.phone);
  set('doctorName', app.doctorName);
  set('dept', app.dept);
  set('mode', app.mode || 'In-Person Hospital Visit');
  set('datetime', `${app.date} at ${app.timeSlot}`);
  set('location', app.location);
  set('doctorFee', app.doctorFee);
  const link = modal.querySelector('[data-receipt="portalLink"]');
  if (link) link.href = `patient-portal.html?ref=${encodeURIComponent(app.refId)}`;
  modal.classList.add('active');
  modal.setAttribute('aria-hidden', 'false');
  modal.querySelector('[data-action="close-receipt"]')?.addEventListener('click', () => closeModal('receiptModal'), { once: true });
  modal.querySelector('[data-action="print-receipt"]')?.addEventListener('click', () => window.print(), { once: true });
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) { modal.classList.remove('active'); modal.setAttribute('aria-hidden', 'true'); }
}
