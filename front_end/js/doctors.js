/**
 * SUFIA HEALTHCARE - Doctors Search & Render Logic
 */

document.addEventListener("DOMContentLoaded", () => {
  renderDoctorsGrid(MGM_APP.doctors);
});

function filterDoctors() {
  const deptVal = document.getElementById("selectSpeciality") ? document.getElementById("selectSpeciality").value : "all";
  const docVal = document.getElementById("selectDoctor") ? document.getElementById("selectDoctor").value : "all";

  const filtered = MGM_APP.doctors.filter(doc => {
    const matchDept = deptVal === "all" || doc.dept.toLowerCase().includes(deptVal.toLowerCase());
    const matchDoc = docVal === "all" || doc.id === docVal;
    return matchDept && matchDoc;
  });

  renderDoctorsGrid(filtered);
}

function resetDoctorFilters() {
  if (document.getElementById("selectSpeciality")) document.getElementById("selectSpeciality").value = "all";
  if (document.getElementById("selectDoctor")) document.getElementById("selectDoctor").value = "all";
  renderDoctorsGrid(MGM_APP.doctors);
}

function renderDoctorsGrid(doctors) {
  const container = document.getElementById("doctorsGridContainer");
  if (!container) return;

  if (doctors.length === 0) {
    container.innerHTML = `
      <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: #fff; border-radius: 12px;">
        <h3 style="color: #304659;">No specialists found for selected criteria</h3>
        <button class="btn-gold" style="margin-top: 16px;" onclick="resetDoctorFilters()">Reset Filters</button>
      </div>
    `;
    return;
  }

  container.innerHTML = doctors.map(doc => `
    <div class="doctor-card-mgm">
      <img src="${doc.image}" alt="${doc.name}" class="doctor-avatar-circle">
      <h3>${doc.name}</h3>
      <div class="qual">${doc.qual}</div>
      <div class="dept">${doc.dept}</div>
      <button class="btn-gold" style="width: 100%;" onclick="openAppointmentTypeModal('${doc.id}')">
        Request Appointment
      </button>
    </div>
  `).join('');
}
