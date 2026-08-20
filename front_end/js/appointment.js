/**
 * SUFIA HEALTHCARE - Appointment Booking Engine & Slip Generation
 */

document.addEventListener("DOMContentLoaded", () => {
  initAppointmentForm();
  checkUrlParamsForDoctor();
});

function checkUrlParamsForDoctor() {
  const urlParams = new URLSearchParams(window.location.search);
  const docId = urlParams.get('doctor');
  const dept = urlParams.get('dept');
  const mode = urlParams.get('mode');
  const time = urlParams.get('time');

  if (mode) {
    const modeSelect = document.getElementById("consultationMode");
    if (modeSelect) {
      if (mode.toLowerCase().includes('video')) {
        modeSelect.value = "Online Video Consultation";
      } else {
        modeSelect.value = "In-Person Hospital Visit";
      }
    }
  }

  if (dept) {
    const deptSelect = document.getElementById("bookingDept");
    if (deptSelect) {
      deptSelect.value = dept;
      filterDoctorsByDepartment(dept);
    }
  }

  if (docId) {
    const docSelect = document.getElementById("bookingDoctor");
    if (docSelect) {
      docSelect.value = docId;
    }
  }

  if (time) {
    const timeSelect = document.getElementById("bookingTime");
    if (timeSelect) {
      const decodedTime = decodeURIComponent(time);
      for (let opt of timeSelect.options) {
        if (opt.value.includes(decodedTime) || decodedTime.includes(opt.value)) {
          timeSelect.value = opt.value;
          break;
        }
      }
    }
  }
}

function initAppointmentForm() {
  const form = document.getElementById("appointmentBookingForm");
  const deptSelect = document.getElementById("bookingDept");
  const docSelect = document.getElementById("bookingDoctor");

  if (deptSelect && docSelect) {
    populateDoctorsDropdown(deptSelect.value);

    deptSelect.addEventListener("change", (e) => {
      filterDoctorsByDepartment(e.target.value);
    });
  }

  if (form) {
    const dateInput = document.getElementById("bookingDate");
    if (dateInput) {
      const today = new Date().toISOString().split('T')[0];
      dateInput.min = today;
      dateInput.value = today;
    }

    form.addEventListener("submit", handleBookingSubmit);
  }
}

function populateDoctorsDropdown(selectedDept = "all") {
  const docSelect = document.getElementById("bookingDoctor");
  if (!docSelect) return;

  docSelect.innerHTML = `<option value="">-- Select Specialist Doctor --</option>`;

  const filtered = selectedDept === "all" || !selectedDept
    ? MGM_APP.doctors
    : MGM_APP.doctors.filter(d => d.dept.toLowerCase().includes(selectedDept.toLowerCase()));

  filtered.forEach(doc => {
    const opt = document.createElement("option");
    opt.value = doc.id;
    opt.textContent = `${doc.name} (${doc.dept}) - ${doc.fee}`;
    docSelect.appendChild(opt);
  });
}

function filterDoctorsByDepartment(dept) {
  populateDoctorsDropdown(dept);
}

function handleBookingSubmit(e) {
  e.preventDefault();

  const patientName = document.getElementById("patientName").value.trim();
  const phone = document.getElementById("patientPhone").value.trim();
  const email = document.getElementById("patientEmail").value.trim();
  const age = document.getElementById("patientAge").value.trim();
  const gender = document.getElementById("patientGender").value;
  const dept = document.getElementById("bookingDept").value;
  const docId = document.getElementById("bookingDoctor").value;
  const date = document.getElementById("bookingDate").value;
  const timeSlot = document.getElementById("bookingTime").value;
  const mode = document.getElementById("consultationMode").value;
  const notes = document.getElementById("patientNotes") ? document.getElementById("patientNotes").value : "";

  if (!patientName || !phone || !date || !timeSlot || !docId) {
    alert("Please fill in all required fields marked with *");
    return;
  }

  const selectedDoctor = MGM_APP.doctors.find(d => d.id === docId) || {
    name: "Senior Specialist Consultant",
    dept: dept,
    fee: "₹1,200",
    branches: [{ name: "SUFIA HEALTHCARE Nelson Manickam Road, Chennai" }]
  };

  const refId = "MGM-" + Math.floor(100000 + Math.random() * 900000);

  const newAppointment = {
    refId,
    patientName,
    phone,
    email,
    age,
    gender,
    dept: selectedDoctor.dept || dept,
    doctorName: selectedDoctor.name,
    doctorFee: selectedDoctor.fee,
    location: selectedDoctor.branches && selectedDoctor.branches[0] ? selectedDoctor.branches[0].name : "SUFIA HEALTHCARE Main Branch",
    date,
    timeSlot,
    mode,
    notes,
    status: "Confirmed",
    createdAt: new Date().toLocaleString()
  };

  MGM_APP.saveAppointment(newAppointment);

  showConfirmationReceipt(newAppointment);

  e.target.reset();
}

function showConfirmationReceipt(app) {
  let modal = document.getElementById("receiptModal");
  if (!modal) {
    modal = document.createElement("div");
    modal.id = "receiptModal";
    modal.className = "modal-backdrop active";
    document.body.appendChild(modal);
  }

  modal.innerHTML = `
    <div class="modal-content" style="border-top: 6px solid #008080;">
      <button class="modal-close" onclick="closeModal('receiptModal')">&times;</button>
      
      <div style="text-align: center; margin-bottom: 24px;">
        <div style="width: 64px; height: 64px; background: #e6f5f5; color: #008080; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2.2rem; margin-bottom: 12px; box-shadow: 0 4px 14px rgba(0,128,128,0.2);">
          ✓
        </div>
        <h2 style="color: #008080; margin-bottom: 4px; font-size: 1.8rem;">Appointment Confirmed!</h2>
        <p style="color: #64748b; font-size: 0.95rem;">Digital Appointment Slip generated for SUFIA HEALTHCARE.</p>
      </div>

      <div class="receipt-box">
        <div class="receipt-header">
          <div>
            <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #64748b; font-weight: 700;">Booking Reference Code</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #0f172a;">${app.refId}</div>
          </div>
          <div style="text-align: right;">
            <span style="background: #dcfce7; color: #166534; font-size: 0.82rem; font-weight: 800; padding: 6px 12px; border-radius: 20px;">
              ● ${app.status}
            </span>
          </div>
        </div>

        <div class="receipt-row">
          <span>Patient Name:</span>
          <strong>${app.patientName} (${app.gender || 'N/A'}, ${app.age || 'N/A'} Yrs)</strong>
        </div>
        <div class="receipt-row">
          <span>Contact Phone:</span>
          <strong>${app.phone}</strong>
        </div>
        <div class="receipt-row">
          <span>Specialist Doctor:</span>
          <strong style="color: #0f172a;">${app.doctorName}</strong>
        </div>
        <div class="receipt-row">
          <span>Department:</span>
          <strong>${app.dept}</strong>
        </div>
        <div class="receipt-row">
          <span>Consultation Mode:</span>
          <strong>${app.mode}</strong>
        </div>
        <div class="receipt-row">
          <span>Scheduled Date & Time:</span>
          <strong style="color: #008080;">${app.date} at ${app.timeSlot}</strong>
        </div>
        <div class="receipt-row">
          <span>Hospital Location:</span>
          <strong>${app.location}</strong>
        </div>
        <div class="receipt-row" style="margin-top: 12px; padding-top: 10px; border-top: 1px dashed #cbd5e1;">
          <span>Consultation Fee:</span>
          <strong style="font-size: 1.2rem; color: #008080;">${app.doctorFee}</strong>
        </div>
      </div>

      <div style="display: flex; gap: 12px; margin-top: 24px; flex-wrap: wrap;">
        <button class="btn btn-primary" onclick="window.print()" style="flex: 1;">
          🖨️ Print Digital Ticket
        </button>
        <a href="patient-portal.html?ref=${app.refId}" class="btn btn-outline" style="flex: 1; text-align: center;">
          📱 View in Patient Portal
        </a>
      </div>
    </div>
  `;

  modal.classList.add("active");
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove("active");
  }
}
