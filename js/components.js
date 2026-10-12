/**
 * Sufia Healthcare - Dynamic Header & Footer Component Loader
 * Automatically includes modular header and footer into all HTML pages.
 * Supports both HTTP/HTTPS fetch and local offline fallback.
 */

(function () {
  const HEADER_TEMPLATE = `
<!-- ================= TOP BAR ================= -->
<div class="topbar">
  <div class="wrap">
    <a href="emergency.html" class="tb-emergency">
      <span class="tb-pulse"></span>
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
      </svg>
      24×7 Emergency Helpline: +91 70918 41444 / +91 70918 45777
    </a>
    <div class="tb-right">
      <span>Mon–Sat · 8:00 AM – 9:00 PM</span>
      <a href="contact.html">Locations & Directions</a>
      <div class="language-switcher">
        <label for="siteLanguage" class="sr-only">Language</label>
        <select id="siteLanguage" class="language-select" aria-label="Select website language">
          <option value="en">English</option>
          <option value="hi">हिंदी</option>
        </select>
      </div>
    </div>
  </div>
</div>

<!-- ================= HEADER ================= -->
<header class="sf-header">
  <div class="wrap nav-row">
    <a href="index.html" class="brand" aria-label="Sufia Healthcare Homepage">
      <img src="assets/img/logo-sufia.png" alt="Sufia Healthcare - Multi-Specialty Hospital" />
    </a>
    <nav class="main-nav">
      <a href="index.html" data-nav="home">Home</a>
      <a href="about.html" data-nav="about">About</a>
      <a href="doctors.html" data-nav="doctors">Doctors</a>
      <a href="departments.html" data-nav="departments">Departments</a>
      <a href="index.html#packages" data-nav="packages">Health Packages</a>
      <a href="emergency.html" data-nav="emergency" style="color: var(--coral-600); font-weight: 700">Emergency</a>
      <a href="contact.html" data-nav="contact">Contact</a>
      <a href="careers.php" data-nav="careers">Career</a>
    </nav>
    <div class="nav-cta">
      <a href="contact.html" class="btn btn-cyan btn-sm">Book Appointment</a>
      <button class="burger" aria-label="Toggle Navigation Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</header>
`;

  const FOOTER_TEMPLATE = `
<!-- ================= FOOTER ================= -->
<footer class="sf-footer">
  <div class="wrap">
    <div class="footer-grid">
      <div class="footer-brand">
        <div class="footer-brand-img">
          <img src="assets/img/logo-sufia.png" alt="Sufia Healthcare" />
        </div>
        <p style="margin-bottom: 10px">
          Sufia Super Speciality Hospital is dedicated to delivering high-quality, accessible, and reliable medical services across Siwan &amp; Saran region.
        </p>
        <div style="font-size: 0.83rem; color: #a9b8cc; display: grid; gap: 4px;">
          <div>📍 Hussaini More, Bindusar, Siwan, Bihar 841227</div>
          <div>✉️ info@sufiahealthcare.com</div>
          <div>📞 +91 70918 41444 / +91 70918 45777</div>
        </div>
      </div>
      <div class="footer-col">
        <h5>Patient Care</h5>
        <ul>
          <li><a href="index.html">Home</a></li>
          <li><a href="about.html">About Us</a></li>
          <li><a href="doctors.html">Find a Doctor</a></li>
          <li><a href="departments.html">Departments &amp; Scope</a></li>
          <li><a href="contact.html">Book Appointment</a></li>
          <li><a href="careers.php">Careers</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h5>Portal Access</h5>
        <ul>
          <li><a href="patient-portal.html">Patient Portal</a></li>
          <li><a href="admin/login.php">Admin Login</a></li>
          <li><a href="privacy-policy.html">Privacy Policy</a></li>
          <li><a href="terms-conditions.html">Terms &amp; Conditions</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h5>Emergency &amp; Contact</h5>
        <ul>
          <li><a href="emergency.html">24x7 Ambulance</a></li>
          <li><a href="emergency.html">Trauma Care Center</a></li>
          <li><a href="contact.html">Hospital Location</a></li>
          <li><a href="contact.html">Enquiry Desk</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h5>Location : Sufia Memorial Hospital</h5>
        <iframe
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d916230.243349504!2d83.14606817812499!3d26.22839709999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3992fc054a98385b%3A0x2f0715d2b6ddf3fb!2sSufia%20Memorial%20Hospital!5e0!3m2!1sen!2sin!4v1787139858578!5m2!1sen!2sin"
          width="280" height="130" style="border: 0; border-radius: 14px; box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);"
          allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
      </div>
    </div>
    <div class="footer-bottom">
      <div>
        <span>© 2026 Sufia Healthcare. All rights reserved.</span>
        <span class="agency-credit">· Designed &amp; Developed by <a href="https://digiritual.com" target="_blank" rel="noopener noreferrer">Digi Ritual</a></span>
      </div>
      <div>
        <span>Sufia Healthcare Super Specialty Hospital</span>
        <a href="privacy-policy.html">Privacy Policy</a>
        <a href="terms-conditions.html">Terms &amp; Conditions</a>
      </div>
    </div>
  </div>
</footer>

<!-- Mobile sticky booking bar -->
<div class="mobile-book-bar">
  <a href="doctors.html" class="btn btn-outline">Find Doctor</a>
  <a href="contact.html" class="btn btn-cyan">Book Appointment</a>
</div>
`;

  async function loadHtml(url, fallback) {
    try {
      const res = await fetch(url);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return await res.text();
    } catch (e) {
      return fallback;
    }
  }

  function setActiveNavLink() {
    const rawPath = window.location.pathname.toLowerCase();
    const pageName = rawPath.split('/').pop() || 'index.html';
    const navLinks = document.querySelectorAll('.main-nav a');

    navLinks.forEach((link) => {
      const href = (link.getAttribute('href') || '').toLowerCase();
      const dataNav = (link.getAttribute('data-nav') || '').toLowerCase();

      let isMatch = false;
      if (pageName === '' || pageName === 'index.html') {
        if (dataNav === 'home' || href === 'index.html') isMatch = true;
      } else if (pageName.includes('about')) {
        if (dataNav === 'about' || href.includes('about')) isMatch = true;
      } else if (pageName.includes('doctor')) {
        if (dataNav === 'doctors' || href.includes('doctor')) isMatch = true;
      } else if (pageName.includes('department') || pageName.includes('specialit')) {
        if (dataNav === 'departments' || href.includes('department') || href.includes('specialit')) isMatch = true;
      } else if (pageName.includes('emergency')) {
        if (dataNav === 'emergency' || href.includes('emergency')) isMatch = true;
      } else if (pageName.includes('contact') || pageName.includes('appointment')) {
        if (dataNav === 'contact' || href.includes('contact')) isMatch = true;
      } else if (pageName.includes('career')) {
        if (dataNav === 'careers' || href.includes('career')) isMatch = true;
      }

      if (isMatch) {
        link.classList.add('active');
      } else {
        link.classList.remove('active');
      }
    });
  }

  function setupMobileMenu() {
    const burger = document.querySelector('.burger');
    const nav = document.querySelector('.main-nav');
    if (!burger || !nav) return;

    const closeMenu = () => {
      nav.classList.remove('is-open');
      burger.classList.remove('is-open');
      burger.setAttribute('aria-expanded', 'false');
    };

    burger.setAttribute('aria-expanded', 'false');
    burger.onclick = (e) => {
      e.stopPropagation();
      const open = nav.classList.toggle('is-open');
      burger.classList.toggle('is-open', open);
      burger.setAttribute('aria-expanded', String(open));
    };

    nav.querySelectorAll('a').forEach((a) => a.addEventListener('click', closeMenu));
    document.addEventListener('click', (e) => {
      if (!nav.contains(e.target) && !burger.contains(e.target)) closeMenu();
    });
    window.addEventListener('resize', () => {
      if (window.innerWidth > 900) closeMenu();
    });
  }

  function syncLanguage() {
    const langSelect = document.getElementById('siteLanguage');
    if (!langSelect) return;
    const storedLang = localStorage.getItem('sufia_site_language') || 'en';
    langSelect.value = storedLang;
    langSelect.addEventListener('change', (e) => {
      const selected = e.target.value;
      localStorage.setItem('sufia_site_language', selected);
      if (typeof window.applySiteLanguage === 'function') {
        window.applySiteLanguage(selected);
      } else {
        // Trigger event for language.js if present
        document.documentElement.lang = selected;
        const combo = document.querySelector('.goog-te-combo');
        if (combo) {
          combo.value = selected === 'hi' ? 'hi' : 'en';
          combo.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }
    });
  }

  async function init() {
    const headerEl = document.getElementById('site-header');
    if (headerEl) {
      const html = await loadHtml('includes/header.html', HEADER_TEMPLATE);
      headerEl.innerHTML = html;
      setActiveNavLink();
      setupMobileMenu();
      syncLanguage();
    }

    const footerEl = document.getElementById('site-footer');
    if (footerEl) {
      const html = await loadHtml('includes/footer.html', FOOTER_TEMPLATE);
      footerEl.innerHTML = html;
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
