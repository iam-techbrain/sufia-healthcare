/**
 * SUFIA HEALTHCARE - behaviour only.
 * Visible page content and doctor data live in the HTML files.
 */

function readDoctorsFromPage() {
  return Array.from(
    document.querySelectorAll("#sufiaDoctorData .sufia-doctor-data"),
  ).map((el) => ({
    id: el.dataset.id,
    name: el.dataset.name,
    dept: el.dataset.dept,
    qual: el.dataset.qual,
    exp: el.dataset.exp,
    fee: el.dataset.fee,
    rating: el.dataset.rating,
    image: el.dataset.image,
    branches: Array.from(el.querySelectorAll(".sufia-branch-data")).map(
      (branch) => ({
        name: branch.dataset.name,
        slots: Number(branch.dataset.slots || 0),
        next: branch.dataset.next,
      }),
    ),
  }));
}

const MGM_APP = {
  doctors: [],
  STORAGE_KEY: "MGM_APPOINTMENTS_DB",
  getAppointments() {
    const data = localStorage.getItem(this.STORAGE_KEY);
    return data ? JSON.parse(data) : [];
  },
  saveAppointment(appointmentData) {
    const appointments = this.getAppointments();
    appointments.unshift(appointmentData);
    localStorage.setItem(this.STORAGE_KEY, JSON.stringify(appointments));
    return appointmentData;
  },
  findAppointment(query) {
    const appointments = this.getAppointments();
    const cleanQuery = query.trim().toUpperCase();
    return appointments.filter(
      (app) =>
        app.refId.toUpperCase() === cleanQuery ||
        app.phone.includes(query.trim()),
    );
  },
};

document.addEventListener("DOMContentLoaded", () => {
  MGM_APP.doctors = readDoctorsFromPage();
  initEmergencyModal();
  initLiveSearchOverlay();
  initMobileMenu();
  initBookingDrawerEvents();
});

function initMobileMenu() {
  const burger = document.querySelector(".burger");
  const nav = document.querySelector(".main-nav");
  if (!burger || !nav) return;
  const closeMenu = () => {
    nav.classList.remove("is-open");
    burger.classList.remove("is-open");
    burger.setAttribute("aria-expanded", "false");
  };
  burger.setAttribute("aria-expanded", "false");
  burger.addEventListener("click", (e) => {
    e.stopPropagation();
    const open = nav.classList.toggle("is-open");
    burger.classList.toggle("is-open", open);
    burger.setAttribute("aria-expanded", String(open));
  });
  nav
    .querySelectorAll("a")
    .forEach((a) => a.addEventListener("click", closeMenu));
  document.addEventListener("click", (e) => {
    if (!nav.contains(e.target) && !burger.contains(e.target)) closeMenu();
  });
  window.addEventListener("resize", () => {
    if (window.innerWidth > 900) closeMenu();
  });
}

function mountFloatingQuickWidget() {
  return;
}

function cloneTemplate(id) {
  const template = document.getElementById(id);
  return template ? template.content.firstElementChild.cloneNode(true) : null;
}

function initLiveSearchOverlay() {
  const searchInput =
    document.getElementById("homeSearchInput") ||
    document.getElementById("doctorSearchExplore");
  if (!searchInput) return;
  const wrapper = searchInput.parentElement;
  let resultsBox = document.getElementById("globalSearchResultsDropdown");
  if (!resultsBox) {
    resultsBox = document.createElement("div");
    resultsBox.id = "globalSearchResultsDropdown";
    resultsBox.className = "search-results-dropdown";
    wrapper.appendChild(resultsBox);
  }
  searchInput.addEventListener("input", (e) =>
    renderSearchResults(resultsBox, e.target.value),
  );
  document.addEventListener("click", (e) => {
    if (!wrapper.contains(e.target)) resultsBox.classList.remove("active");
  });
}

function renderSearchResults(resultsBox, value) {
  const val = value.toLowerCase().trim();
  resultsBox.replaceChildren();
  if (val.length < 2) {
    resultsBox.classList.remove("active");
    return;
  }
  const matched = MGM_APP.doctors.filter(
    (d) =>
      d.name.toLowerCase().includes(val) ||
      d.dept.toLowerCase().includes(val) ||
      d.qual.toLowerCase().includes(val),
  );
  if (!matched.length) {
    const empty = cloneTemplate("searchNoResultsTemplate");
    if (empty) {
      const query = empty.querySelector('[data-field="query"]');
      if (query) query.textContent = value.trim();
      resultsBox.appendChild(empty);
    }
  } else {
    matched.forEach((doc) => {
      const item = cloneTemplate("searchResultTemplate");
      if (!item) return;
      const image = item.querySelector('[data-field="image"]');
      image.src = doc.image;
      image.alt = doc.name;
      item.querySelector('[data-field="name"]').textContent = doc.name;
      item.querySelector('[data-field="qual"]').textContent = doc.qual;
      item.querySelector('[data-field="dept"]').textContent = doc.dept;
      item.querySelector('[data-field="fee"]').textContent = doc.fee;
      item.addEventListener("click", () => selectSearchResult(doc.id));
      resultsBox.appendChild(item);
    });
  }
  resultsBox.classList.add("active");
}

function selectSearchResult(docId) {
  openDoctorBookingDrawer(docId);
}

function initBookingDrawerEvents() {
  const drawer = document.getElementById("doctorBookingDrawer");
  if (!drawer) return;
  drawer
    .querySelector('[data-action="close-drawer"]')
    ?.addEventListener("click", closeDrawer);
  drawer
    .querySelectorAll("[data-mode]")
    .forEach((el) =>
      el.addEventListener("click", () =>
        switchDrawerTab(drawer.dataset.doctorId, el.dataset.mode),
      ),
    );
  drawer
    .querySelectorAll("[data-date]")
    .forEach((el) =>
      el.addEventListener("click", () =>
        selectDrawerDate(
          drawer.dataset.doctorId,
          drawer.dataset.mode,
          el.dataset.date,
        ),
      ),
    );
  drawer
    .querySelectorAll("[data-time]")
    .forEach((el) =>
      el.addEventListener("click", () =>
        selectTimeSlot(
          el,
          drawer.dataset.doctorId,
          drawer.dataset.mode,
          drawer.dataset.date,
          el.dataset.time,
        ),
      ),
    );
}

function openDoctorBookingDrawer(
  docId,
  mode = "in-hospital",
  selectedDate = "Today",
  selectedTime = "09:00 AM - 10:00 AM",
) {
  const doc = MGM_APP.doctors.find((d) => d.id === docId) || MGM_APP.doctors[0];
  const drawer = document.getElementById("doctorBookingDrawer");
  if (!doc || !drawer) return;
  drawer.dataset.doctorId = doc.id;
  drawer.dataset.mode = mode;
  drawer.dataset.date = selectedDate;
  drawer.dataset.time = selectedTime;
  const set = (key, value) => {
    const el = drawer.querySelector(`[data-drawer="${key}"]`);
    if (el) el.textContent = value;
  };
  const img = drawer.querySelector('[data-drawer="image"]');
  if (img) {
    img.src = doc.image;
    img.alt = doc.name;
  }
  set("name", doc.name);
  set("qual", doc.qual);
  set("exp", doc.exp);
  set("rating", doc.rating);
  set("fee", doc.fee);
  drawer
    .querySelectorAll("[data-mode]")
    .forEach((el) => el.classList.toggle("active", el.dataset.mode === mode));
  drawer
    .querySelectorAll("[data-date]")
    .forEach((el) =>
      el.classList.toggle("active", el.dataset.date === selectedDate),
    );
  drawer
    .querySelectorAll("[data-time]")
    .forEach((el) =>
      el.classList.toggle("selected", el.dataset.time === selectedTime),
    );
  renderBranches(drawer, doc, mode, selectedDate, selectedTime);
  drawer.classList.add("active");
  drawer.setAttribute("aria-hidden", "false");
}

function renderBranches(drawer, doc, mode, date, time) {
  const host = drawer.querySelector('[data-drawer="branches"]');
  if (!host) return;
  host.replaceChildren();
  doc.branches.forEach((branch) => {
    const row = cloneTemplate("branchSlotTemplate");
    if (!row) return;
    row.querySelector('[data-field="branch-name"]').textContent = branch.name;
    row.querySelector('[data-field="branch-slots"]').textContent = branch.slots;
    row.querySelector('[data-field="branch-next"]').textContent = branch.next;
    row.addEventListener("click", () =>
      proceedToBookingForm(
        doc.id,
        encodeURIComponent(branch.name),
        mode,
        date,
        time,
      ),
    );
    host.appendChild(row);
  });
}

function selectDrawerDate(docId, mode, date) {
  openDoctorBookingDrawer(docId, mode, date);
}
function selectTimeSlot(btn, docId, mode, date, time) {
  openDoctorBookingDrawer(docId, mode, date, time);
}
function switchDrawerTab(docId, mode) {
  openDoctorBookingDrawer(docId, mode);
}
function closeDrawer() {
  const drawer = document.getElementById("doctorBookingDrawer");
  if (drawer) {
    drawer.classList.remove("active");
    drawer.setAttribute("aria-hidden", "true");
  }
}
function proceedToBookingForm(
  docId,
  branchName,
  mode,
  date = "Today",
  time = "09:00 AM - 10:00 AM",
) {
  closeDrawer();
  window.location.href = `contact.html?doctor=${docId}&branch=${branchName}&mode=${mode}&date=${date}&time=${encodeURIComponent(time)}`;
}
function initEmergencyModal() {
  document.querySelectorAll(".trigger-emergency").forEach((btn) =>
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      const message = btn.dataset.emergencyMessage;
      if (message) alert(message);
    }),
  );
}
