/**
 * SUFIA HEALTHCARE - World Class Hospital Portal Engine & Slot Booking Engine
 */

const MGM_APP = {
  doctors: [
    {
      id: "doc-1",
      name: "Dr. Nilesh Mishra",
      dept: "Orthopaedics & Spine",
      qual: "Senior Consultant - Orthopaedics | DNB, DO, MBBS",
      exp: "20+ Yrs",
      fee: "₹900",
      rating: "4.9 ★★★★★",
      branches: [
        { name: "SUFIA HEALTHCARE Nelson Manickam Road, Chennai", slots: 36, next: "Tomorrow 09:00 AM" },
        { name: "SUFIA HEALTHCARE Alwarpet, Chennai", slots: 18, next: "Tomorrow 02:30 PM" }
      ],
      image: "https://images.unsplash.com/photo-1622253692010-333f2da6031d?auto=format&fit=crop&w=600&q=80"
    },
    {
      id: "doc-2",
      name: "Dr. Vineesh Mathur",
      dept: "Spine Surgery",
      qual: "Director - Spine Surgery Unit | MS (Ortho), FRCS",
      exp: "38+ Yrs",
      fee: "₹1,500",
      rating: "5.0 ★★★★★",
      branches: [
        { name: "SUFIA HEALTHCARE Nelson Manickam Road, Chennai", slots: 24, next: "Tomorrow 10:20 AM" }
      ],
      image: "https://images.unsplash.com/photo-1537368910025-700350fe46c7?auto=format&fit=crop&w=600&q=80"
    },
    {
      id: "doc-3",
      name: "Dr. Vipin Chand Tyagi",
      dept: "Orthopaedics & Joint Replacement",
      qual: "Senior Director - Joint Replacement & Arthroscopy",
      exp: "27+ Yrs",
      fee: "₹1,500",
      rating: "4.8 ★★★★★",
      branches: [
        { name: "SUFIA HEALTHCARE Alwarpet, Chennai", slots: 42, next: "Tomorrow 12:30 PM" }
      ],
      image: "https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?auto=format&fit=crop&w=600&q=80"
    },
    {
      id: "doc-4",
      name: "Dr. Madan Mohan B",
      dept: "Cardiac Sciences",
      qual: "Senior Consultant - Interventional Cardiology",
      exp: "25+ Yrs",
      fee: "₹1,200",
      rating: "4.9 ★★★★★",
      branches: [
        { name: "SUFIA HEALTHCARE Nelson Manickam Road, Chennai", slots: 15, next: "Today 04:00 PM" }
      ],
      image: "https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&w=600&q=80"
    },
    {
      id: "doc-5",
      name: "Dr. Arcot Jaishree Gajaraj",
      dept: "Obstetrics & Gynaecology",
      qual: "Senior Director - Obstetrics & High Risk Pregnancy | FRCS, FRCOG",
      exp: "35+ Yrs",
      fee: "₹1,500",
      rating: "5.0 ★★★★★",
      branches: [
        { name: "SUFIA HEALTHCARE Alwarpet, Chennai", slots: 28, next: "Tomorrow 11:00 AM" }
      ],
      image: "https://images.unsplash.com/photo-1594824813566-88855ce75341?auto=format&fit=crop&w=600&q=80"
    },
    {
      id: "doc-6",
      name: "Dr. Prashanth R",
      dept: "Heart & Lung Transplant",
      qual: "Director - Organ Transplant & ECMO Specialist | MCh (CTVS)",
      exp: "22+ Yrs",
      fee: "₹2,000",
      rating: "5.0 ★★★★★",
      branches: [
        { name: "SUFIA HEALTHCARE Nelson Manickam Road, Chennai", slots: 12, next: "Today 05:30 PM" }
      ],
      image: "https://images.unsplash.com/photo-1582750433449-648ed127bb54?auto=format&fit=crop&w=600&q=80"
    },
    {
      id: "doc-7",
      name: "Dr. Meenakshi Sundaram",
      dept: "Neurosurgery & Spine",
      qual: "Senior Consultant - Brain & Micro Neurosurgery | DNB (Neuro)",
      exp: "24+ Yrs",
      fee: "₹1,400",
      rating: "4.9 ★★★★★",
      branches: [
        { name: "SUFIA HEALTHCARE Nelson Manickam Road, Chennai", slots: 20, next: "Tomorrow 09:30 AM" }
      ],
      image: "https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80"
    },
    {
      id: "doc-8",
      name: "Dr. Rajesh Kumar Sharma",
      dept: "Oncology & Cancer Care",
      qual: "Chief Surgical Oncologist | MS, MCh (Surgical Oncology)",
      exp: "30+ Yrs",
      fee: "₹1,800",
      rating: "4.9 ★★★★★",
      branches: [
        { name: "SUFIA HEALTHCARE Alwarpet, Chennai", slots: 16, next: "Tomorrow 03:00 PM" }
      ],
      image: "https://images.unsplash.com/photo-1622253692010-333f2da6031d?auto=format&fit=crop&w=600&q=80"
    }
  ],

  STORAGE_KEY: "MGM_APPOINTMENTS_DB",

  getAppointments: function () {
    const data = localStorage.getItem(this.STORAGE_KEY);
    return data ? JSON.parse(data) : [];
  },

  saveAppointment: function (appointmentData) {
    const appointments = this.getAppointments();
    appointments.unshift(appointmentData);
    localStorage.setItem(this.STORAGE_KEY, JSON.stringify(appointments));
    return appointmentData;
  },

  findAppointment: function (query) {
    const appointments = this.getAppointments();
    const cleanQuery = query.trim().toUpperCase();
    return appointments.filter(app =>
      app.refId.toUpperCase() === cleanQuery || app.phone.includes(query.trim())
    );
  }
};

// Global Initialization
document.addEventListener("DOMContentLoaded", () => {
  initEmergencyModal();
  initLiveSearchOverlay();
  initMobileMenu();
});

function initMobileMenu() {
  const burger = document.querySelector('.burger');
  const nav = document.querySelector('.main-nav');
  if (!burger || !nav) return;

  burger.addEventListener('click', (e) => {
    e.stopPropagation();
    const isFlex = nav.style.display === 'flex';
    if (isFlex) {
      nav.style.display = 'none';
    } else {
      nav.style.display = 'flex';
      nav.style.cssText = 'display:flex;flex-direction:column;position:absolute;top:100%;left:0;right:0;background:#ffffff;padding:20px 28px;border-bottom:1px solid var(--line);box-shadow:0 12px 24px rgba(11,26,44,0.12);z-index:1000;gap:14px;';
    }
  });
}

function mountFloatingQuickWidget() {
  return;
}

// Real-Time Search Autocomplete & Live Filtering
function initLiveSearchOverlay() {
  const searchInput = document.getElementById("homeSearchInput") || document.getElementById("doctorSearchExplore");
  if (!searchInput) return;

  const wrapper = searchInput.parentElement;
  let resultsBox = document.getElementById("globalSearchResultsDropdown");

  if (!resultsBox && wrapper) {
    resultsBox = document.createElement("div");
    resultsBox.id = "globalSearchResultsDropdown";
    resultsBox.className = "search-results-dropdown";
    wrapper.appendChild(resultsBox);
  }

  searchInput.addEventListener("input", (e) => {
    const val = e.target.value.toLowerCase().trim();
    if (val.length < 2) {
      if (resultsBox) resultsBox.classList.remove("active");
      return;
    }

    const matchedDoctors = MGM_APP.doctors.filter(d =>
      d.name.toLowerCase().includes(val) ||
      d.dept.toLowerCase().includes(val) ||
      d.qual.toLowerCase().includes(val)
    );

    if (matchedDoctors.length === 0) {
      resultsBox.innerHTML = `
        <div style="padding: 16px; text-align: center; color: #64748b; font-size: 0.9rem;">
          No specialist doctor found for "<strong>${val}</strong>". Try searching for Cardiac, Ortho, Spine, or Gynaec.
        </div>
      `;
    } else {
      resultsBox.innerHTML = matchedDoctors.map(doc => `
        <div class="search-result-item" onclick="selectSearchResult('${doc.id}')">
          <img src="${doc.image}" class="search-result-avatar" alt="${doc.name}">
          <div>
            <div class="search-result-title">${doc.name}</div>
            <div class="search-result-sub">${doc.qual} • <span style="color: #008080; font-weight:700;">${doc.dept}</span></div>
          </div>
          <div style="margin-left: auto;">
            <span style="background: #e6f5f5; color: #008080; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">
              ${doc.fee}
            </span>
          </div>
        </div>
      `).join('');
    }

    resultsBox.classList.add("active");
  });

  // Close results when clicking outside
  document.addEventListener("click", (e) => {
    if (wrapper && !wrapper.contains(e.target) && resultsBox) {
      resultsBox.classList.remove("active");
    }
  });
}

function selectSearchResult(docId) {
  openDoctorBookingDrawer(docId);
}

// Medanta-style Booking Drawer Modal Popup with Date/Time Slot Selection
function openDoctorBookingDrawer(docId, mode = 'in-hospital', selectedDate = 'Today', selectedTime = '09:00 AM - 10:00 AM') {
  const doc = MGM_APP.doctors.find(d => d.id === docId) || MGM_APP.doctors[0];

  let drawer = document.getElementById("doctorBookingDrawer");
  if (!drawer) {
    drawer = document.createElement("div");
    drawer.id = "doctorBookingDrawer";
    drawer.className = "booking-drawer-modal";
    document.body.appendChild(drawer);
  }

  drawer.innerHTML = `
    <div class="drawer-content">
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px;">
        <div style="display: flex; gap: 16px; align-items: center;">
          <img src="${doc.image}" style="width: 76px; height: 76px; border-radius: 16px; object-fit: cover; border: 3px solid #008080; box-shadow: 0 4px 12px rgba(0,128,128,0.2);" alt="${doc.name}">
          <div>
            <h3 style="font-size: 1.3rem; color: #0f172a; margin-bottom: 2px;">${doc.name}</h3>
            <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 4px;">${doc.qual}</div>
            <div style="display: flex; gap: 10px; font-size: 0.8rem; font-weight: 700;">
              <span style="color: #008080;">Exp: ${doc.exp}</span>
              <span style="color: #64748b;">•</span>
              <span style="color: #f59e0b;">${doc.rating}</span>
              <span style="color: #64748b;">•</span>
              <span style="color: #0f172a;">Fee: ${doc.fee}</span>
            </div>
          </div>
        </div>
        <button onclick="closeDrawer()" style="background: #f1f5f9; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">&times;</button>
      </div>

      <!-- Mode Tabs (In-Hospital vs Video) -->
      <div class="drawer-tabs">
        <div class="drawer-tab-btn ${mode === 'in-hospital' ? 'active' : ''}" onclick="switchDrawerTab('${doc.id}', 'in-hospital')">
          🏥 In-Hospital Consultation
        </div>
        <div class="drawer-tab-btn ${mode === 'video' ? 'active' : ''}" onclick="switchDrawerTab('${doc.id}', 'video')">
          📹 Online Video Consultation
        </div>
      </div>

      <!-- Date Selector Tabs -->
      <div style="font-weight: 800; font-size: 0.95rem; margin-bottom: 12px; color: #0f172a;">1. Select Preferred Consultation Date:</div>
      <div class="slot-day-selector">
        <div class="slot-day-card ${selectedDate === 'Today' ? 'active' : ''}" onclick="selectDrawerDate('${doc.id}', '${mode}', 'Today')">
          <div class="slot-day-name">Today</div>
          <div class="slot-day-count">12 Slots Free</div>
        </div>
        <div class="slot-day-card ${selectedDate === 'Tomorrow' ? 'active' : ''}" onclick="selectDrawerDate('${doc.id}', '${mode}', 'Tomorrow')">
          <div class="slot-day-name">Tomorrow</div>
          <div class="slot-day-count">36 Slots Free</div>
        </div>
        <div class="slot-day-card ${selectedDate === 'Day After' ? 'active' : ''}" onclick="selectDrawerDate('${doc.id}', '${mode}', 'Day After')">
          <div class="slot-day-name">Day After</div>
          <div class="slot-day-count">28 Slots Free</div>
        </div>
      </div>

      <!-- Time Slots Grid -->
      <div style="font-weight: 800; font-size: 0.95rem; margin-bottom: 12px; color: #0f172a;">2. Select Available Time Slot:</div>
      <div class="time-slots-grid">
        <button class="time-slot-btn ${selectedTime === '09:00 AM - 10:00 AM' ? 'selected' : ''}" onclick="selectTimeSlot(this, '${doc.id}', '${mode}', '${selectedDate}', '09:00 AM - 10:00 AM')">🌅 09:00 AM</button>
        <button class="time-slot-btn ${selectedTime === '10:30 AM - 11:30 AM' ? 'selected' : ''}" onclick="selectTimeSlot(this, '${doc.id}', '${mode}', '${selectedDate}', '10:30 AM - 11:30 AM')">🌅 10:30 AM</button>
        <button class="time-slot-btn ${selectedTime === '12:00 PM - 01:00 PM' ? 'selected' : ''}" onclick="selectTimeSlot(this, '${doc.id}', '${mode}', '${selectedDate}', '12:00 PM - 01:00 PM')">☀️ 12:00 PM</button>
        <button class="time-slot-btn ${selectedTime === '02:30 PM - 03:30 PM' ? 'selected' : ''}" onclick="selectTimeSlot(this, '${doc.id}', '${mode}', '${selectedDate}', '02:30 PM - 03:30 PM')">☀️ 02:30 PM</button>
        <button class="time-slot-btn ${selectedTime === '04:30 PM - 05:30 PM' ? 'selected' : ''}" onclick="selectTimeSlot(this, '${doc.id}', '${mode}', '${selectedDate}', '04:30 PM - 05:30 PM')">🌆 04:30 PM</button>
        <button class="time-slot-btn ${selectedTime === '06:00 PM - 07:00 PM' ? 'selected' : ''}" onclick="selectTimeSlot(this, '${doc.id}', '${mode}', '${selectedDate}', '06:00 PM - 07:00 PM')">🌙 06:00 PM</button>
      </div>

      <!-- Branch Availability List -->
      <div style="font-weight: 800; font-size: 0.95rem; margin-bottom: 12px; color: #0f172a;">3. Choose Hospital Branch & Reserve:</div>
      
      ${doc.branches.map(branch => `
        <div class="branch-slot-box" onclick="proceedToBookingForm('${doc.id}', '${encodeURIComponent(branch.name)}', '${mode}', '${selectedDate}', '${selectedTime}')">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <div class="branch-name">🏥 ${branch.name}</div>
            <span style="background: #dcfce7; color: #166534; font-size: 0.78rem; font-weight: 800; padding: 4px 12px; border-radius: 20px;">
              ${branch.slots} Slots Free
            </span>
          </div>
          <div class="branch-avail">⚡ Next Available: <strong>${branch.next}</strong></div>
          <div style="margin-top: 12px; text-align: right;">
            <span style="color: #008080; font-weight: 800; font-size: 0.88rem;">Proceed to Patient Details &rarr;</span>
          </div>
        </div>
      `).join('')}

    </div>
  `;

  drawer.classList.add("active");
}

function selectDrawerDate(docId, mode, date) {
  openDoctorBookingDrawer(docId, mode, date);
}

function selectTimeSlot(btn, docId, mode, date, time) {
  const allSlots = document.querySelectorAll(".time-slot-btn");
  allSlots.forEach(s => s.classList.remove("selected"));
  btn.classList.add("selected");
  openDoctorBookingDrawer(docId, mode, date, time);
}

function switchDrawerTab(docId, mode) {
  openDoctorBookingDrawer(docId, mode);
}

function closeDrawer() {
  const drawer = document.getElementById("doctorBookingDrawer");
  if (drawer) {
    drawer.classList.remove("active");
  }
}

function proceedToBookingForm(docId, branchName, mode, date = 'Today', time = '09:00 AM - 10:00 AM') {
  closeDrawer();
  window.location.href = `appointment.html?doctor=${docId}&branch=${branchName}&mode=${mode}&date=${date}&time=${encodeURIComponent(time)}`;
}

function initEmergencyModal() {
  const emergencyBtns = document.querySelectorAll(".trigger-emergency");
  emergencyBtns.forEach(btn => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      alert("🚨 SUFIA HEALTHCARE 24/7 EMERGENCY & AMBULANCE DISPATCH HOTLINE: +91 44 4200 4200\n\nCentral Command Center active. Ambulances equipped with Cardiac ACLS and ICU support en route.");
    });
  });
}
