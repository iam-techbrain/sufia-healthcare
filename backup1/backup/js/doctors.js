/** Doctors page filtering behaviour only. Doctor content stays in doctors.html. */
document.addEventListener('DOMContentLoaded', () => {
  const speciality = document.getElementById('selectSpeciality');
  const doctor = document.getElementById('selectDoctor');
  speciality?.addEventListener('change', filterDoctors);
  doctor?.addEventListener('change', filterDoctors);
});

function filterDoctors() {
  const deptVal = document.getElementById('selectSpeciality')?.value || 'all';
  const docVal = document.getElementById('selectDoctor')?.value || 'all';
  document.querySelectorAll('.doctor-card-mgm').forEach(card => {
    const matchDept = deptVal === 'all' || (card.dataset.dept || '').toLowerCase().includes(deptVal.toLowerCase());
    const matchDoc = docVal === 'all' || card.dataset.id === docVal;
    card.hidden = !(matchDept && matchDoc);
  });
}

function resetDoctorFilters() {
  const speciality = document.getElementById('selectSpeciality');
  const doctor = document.getElementById('selectDoctor');
  if (speciality) speciality.value = 'all';
  if (doctor) doctor.value = 'all';
  filterDoctors();
}
