<?php
// Sufia Healthcare - Common Header for PHP pages
$current_page = basename($_SERVER['PHP_SELF'] ?? '');
?>
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
      <!--<span>Mon–Sat · 8:00 AM – 9:00 PM</span>-->
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
      <a href="index.html" class="<?= ($current_page === 'index.html' || $current_page === '' || $current_page === 'index.php') ? 'active' : '' ?>">Home</a>
      <a href="about.html" class="<?= ($current_page === 'about.html') ? 'active' : '' ?>">About</a>
      <a href="doctors.html" class="<?= ($current_page === 'doctors.html') ? 'active' : '' ?>">Doctors</a>
      <a href="departments.html" class="<?= ($current_page === 'departments.html' || $current_page === 'specialities.html') ? 'active' : '' ?>">Departments</a>
      <a href="emergency.html" class="<?= ($current_page === 'emergency.html') ? 'active' : '' ?>" style="color: var(--coral-600); font-weight: 700">Emergency</a>
      <a href="contact.html" class="<?= ($current_page === 'contact.html') ? 'active' : '' ?>">Contact</a>
      <a href="careers.php" class="<?= (strpos($current_page, 'career') !== false) ? 'active' : '' ?>">Career</a>
    </nav>
    <div class="nav-cta">
      <a href="contact.html" class="btn btn-cyan btn-sm">Book Appointment</a>
      <button class="burger" aria-label="Toggle Navigation Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</header>
