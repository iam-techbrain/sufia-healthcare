/**
 * SUFIA HEALTHCARE - Patient Portal & Tracking Logic
 */

document.addEventListener("DOMContentLoaded", () => {
  initPortalSearch();
  checkUrlParamsForRef();
});

function checkUrlParamsForRef() {
  const urlParams = new URLSearchParams(window.location.search);
  const ref = urlParams.get('ref') || urlParams.get('query');
  if (ref) {
    const input = document.getElementById("portalSearchQuery");
    if (input) {
      input.value = ref;
      searchAppointments(ref);
    }
  }
}

function initPortalSearch() {
  const form = document.getElementById("portalSearchForm");
  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const query = document.getElementById("portalSearchQuery").value.trim();
      searchAppointments(query);
    });
  }
}

function searchAppointments(query) {
  const resultsContainer = document.getElementById("portalResultsContainer");
  if (!resultsContainer) return;

  if (!query) {
    resultsContainer.innerHTML = `
      <div style="text-align: center; padding: 40px; background: #fff; border-radius: 16px;">
        <p style="color: #64748b;">Please enter your Reference ID (e.g. MGM-123456) or Registered Mobile Number.</p>
      </div>
    `;
    return;
  }

  const results = MGM_APP.findAppointment(query);

  if (results.length === 0) {
    resultsContainer.innerHTML = `
      <div style="text-align: center; padding: 50px; background: #fff; border-radius: 16px; border: 1px dashed #cbd5e1;">
        <div style="font-size: 2.5rem; margin-bottom: 10px;">📋</div>
        <h3 style="color: #0f172a; margin-bottom: 6px;">No appointment found for "${query}"</h3>
        <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 20px;">Please verify your Reference ID or book a new appointment below.</p>
        <a href="appointment.html" class="btn btn-primary">⚡ Book New Appointment</a>
      </div>
    `;
    return;
  }

  resultsContainer.innerHTML = results.map(app => `
    <div style="background: #fff; border-radius: var(--radius-lg); padding: 32px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); margin-bottom: 24px;">
      
      <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <div>
          <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Reference Code</span>
          <h3 style="font-size: 1.6rem; color: #007c89; margin: 0;">${app.refId}</h3>
        </div>

        <div style="text-align: right;">
          <span style="background: ${app.status === 'Cancelled' ? '#fee2e2' : '#dcfce7'}; color: ${app.status === 'Cancelled' ? '#991b1b' : '#166534'}; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 0.85rem;">
            ● ${app.status}
          </span>
          <div style="font-size: 0.8rem; color: #64748b; margin-top: 4px;">Booked on ${app.createdAt}</div>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; font-size: 0.95rem;">
        <div>
          <div style="color: #64748b; font-size: 0.85rem;">Patient Name</div>
          <strong>${app.patientName} (${app.gender || 'N/A'}, ${app.age || 'N/A'} yrs)</strong>
        </div>

        <div>
          <div style="color: #64748b; font-size: 0.85rem;">Contact Phone</div>
          <strong>${app.phone}</strong>
        </div>

        <div>
          <div style="color: #64748b; font-size: 0.85rem;">Doctor / Specialist</div>
          <strong style="color: #0f172a;">${app.doctorName}</strong>
        </div>

        <div>
          <div style="color: #64748b; font-size: 0.85rem;">Department</div>
          <strong>${app.dept}</strong>
        </div>

        <div>
          <div style="color: #64748b; font-size: 0.85rem;">Appointment Date & Time</div>
          <strong style="color: #007c89;">${app.date} at ${app.timeSlot}</strong>
        </div>

        <div>
          <div style="color: #64748b; font-size: 0.85rem;">Consultation Fee</div>
          <strong style="color: #007c89;">${app.doctorFee}</strong>
        </div>
      </div>

      <div style="display: flex; gap: 12px; border-top: 1px solid #f1f5f9; padding-top: 16px;">
        <button class="btn btn-primary" onclick="printReceipt('${app.refId}')">
          🖨️ Print Digital Ticket
        </button>
        ${app.status !== 'Cancelled' ? `
          <button class="btn btn-outline" style="color: #ef4444; border-color: #ef4444;" onclick="cancelAppointment('${app.refId}')">
            ❌ Cancel Appointment
          </button>
        ` : ''}
      </div>

    </div>
  `).join('');
}

function printReceipt(refId) {
  const app = MGM_APP.findAppointment(refId)[0];
  if (app && typeof showConfirmationReceipt === 'function') {
    showConfirmationReceipt(app);
  } else {
    window.print();
  }
}

function cancelAppointment(refId) {
  if (confirm(`Are you sure you want to cancel appointment reference ${refId}?`)) {
    const appointments = MGM_APP.getAppointments();
    const updated = appointments.map(app => {
      if (app.refId === refId) {
        return { ...app, status: 'Cancelled' };
      }
      return app;
    });
    localStorage.setItem(MGM_APP.STORAGE_KEY, JSON.stringify(updated));
    alert("Appointment has been marked as Cancelled.");
    searchAppointments(refId);
  }
}
