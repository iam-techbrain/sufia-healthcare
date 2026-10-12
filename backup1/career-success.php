<?php
session_start();

$ref_id = filter_input(INPUT_GET, 'ref', FILTER_SANITIZE_SPECIAL_CHARS);
$stored = $_SESSION['career_submitted'] ?? null;

$name  = $stored['name'] ?? 'Candidate';
$email = $stored['email'] ?? '';
$phone = $stored['phone'] ?? '';

if (empty($ref_id) && !empty($stored['ref_id'])) {
    $ref_id = $stored['ref_id'];
}

if (empty($ref_id)) {
    $ref_id = 'SUF-CAR-' . date('ymd') . '-VERIFIED';
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Application Submitted Successfully | Sufia Healthcare</title>
    <meta name="robots" content="noindex, follow" />

    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico" />
    <link rel="stylesheet" href="css/premium.css" />

    <style>
      .success-section {
        padding: 70px 0 100px;
        background: radial-gradient(circle at 50% 20%, #f0faf9 0%, #f5f8fa 100%);
        min-height: 70vh;
        display: flex;
        align-items: center;
      }

      .success-card {
        max-width: 680px;
        margin: 0 auto;
        background: #ffffff;
        border-radius: var(--radius-lg, 24px);
        padding: 48px 42px;
        text-align: center;
        border: 1px solid var(--line, #e3e9ef);
        box-shadow: 0 16px 45px rgba(11, 37, 69, 0.08);
      }

      .success-icon-wrap {
        width: 84px;
        height: 84px;
        border-radius: 50%;
        background: #e8f7ee;
        color: #187a3d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.8rem;
        margin: 0 auto 24px;
        box-shadow: 0 0 0 12px rgba(24, 122, 61, 0.08);
        animation: pulseCheck 2s infinite ease-in-out;
      }

      @keyframes pulseCheck {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
      }

      .success-card h1 {
        font-family: var(--font-display, "Fraunces", Georgia, serif);
        font-size: 1.85rem;
        color: var(--navy-950, #071a2e);
        margin: 0 0 12px;
      }

      .success-card p.lead-text {
        font-size: 1rem;
        line-height: 1.65;
        color: var(--ink-soft, #33465e);
        margin: 0 0 28px;
      }

      .ref-box {
        background: var(--mist, #f5f8fa);
        border: 1px dashed var(--cyan-400, #3fc7bd);
        border-radius: 14px;
        padding: 18px 24px;
        margin-bottom: 28px;
        display: inline-block;
        width: 100%;
        box-sizing: border-box;
      }

      .ref-label {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 700;
        color: var(--slate, #647186);
        margin-bottom: 6px;
      }

      .ref-code {
        font-family: var(--font-mono, "IBM Plex Mono", monospace);
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--cyan-600, #0a8f8a);
        letter-spacing: 0.05em;
      }

      .next-steps-card {
        text-align: left;
        background: #fafcfd;
        border: 1px solid var(--line, #e3e9ef);
        border-radius: 14px;
        padding: 22px 26px;
        margin-bottom: 32px;
      }

      .next-steps-card h4 {
        margin: 0 0 14px;
        font-size: 0.95rem;
        color: var(--navy-950, #071a2e);
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .steps-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 12px;
      }

      .step-item {
        display: flex;
        gap: 12px;
        font-size: 0.88rem;
        color: var(--ink-soft, #33465e);
        line-height: 1.5;
      }

      .step-num {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: var(--cyan-100, #e3f6f3);
        color: var(--cyan-600, #0a8f8a);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
        flex-shrink: 0;
        margin-top: 2px;
      }

      .action-btns {
        display: flex;
        gap: 14px;
        justify-content: center;
        flex-wrap: wrap;
      }

      @media (max-width: 640px) {
        .success-card {
          padding: 32px 20px;
        }

        .success-card h1 {
          font-size: 1.5rem;
        }

        .ref-code {
          font-size: 1.15rem;
        }

        .action-btns {
          flex-direction: column;
        }

        .action-btns .btn {
          width: 100%;
        }
      }
    </style>
  </head>

  <body class="sf">
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
          <a href="index.html">Home</a>
          <a href="index.html#about">About</a>
          <a href="doctors.html">Doctors</a>
          <a href="specialities.html">Departments</a>
          <a href="index.html#packages">Health Packages</a>
          <a href="emergency.html" style="color: var(--coral-600); font-weight: 700">Emergency</a>
          <a href="contact.html">Contact</a>
        </nav>
        <div class="nav-cta">
          <a href="contact.html" class="btn btn-cyan btn-sm">Book Appointment</a>
          <button class="burger" aria-label="Toggle Navigation Menu">
            <span></span><span></span><span></span>
          </button>
        </div>
      </div>
    </header>

    <!-- ================= MAIN SUCCESS CONTENT ================= -->
    <main class="success-section">
      <div class="wrap" style="width: 100%;">
        <div class="success-card">
          <div class="success-icon-wrap">✓</div>

          <h1>Application Submitted!</h1>
          <p class="lead-text">
            Dear <strong><?= htmlspecialchars($name) ?></strong>, thank you for your application to join <strong>Sufia Healthcare Super Specialty Hospital</strong>. Your resume has been uploaded and stored securely.
          </p>

          <div class="ref-box">
            <div class="ref-label">Your Application Reference ID</div>
            <div class="ref-code"><?= htmlspecialchars($ref_id) ?></div>
            <div style="font-size: 0.78rem; color: #647186; margin-top: 4px;">
              Please save this ID for any future recruitment correspondence with our HR department.
            </div>
          </div>

          <div class="next-steps-card">
            <h4>
              <span>📋</span> What happens next?
            </h4>
            <div class="steps-list">
              <div class="step-item">
                <div class="step-num">1</div>
                <div><strong>Screening &amp; Review:</strong> Our Hospital HR team and Departmental Heads will evaluate your qualifications and experience.</div>
              </div>
              <div class="step-item">
                <div class="step-num">2</div>
                <div><strong>Shortlisting:</strong> If your profile matches our requirements, you will receive an interview call or WhatsApp message on your provided phone number.</div>
              </div>
              <div class="step-item">
                <div class="step-num">3</div>
                <div><strong>Campus Interview:</strong> Selected candidates will be invited for an interaction at our Bindusar, Siwan hospital campus.</div>
              </div>
            </div>
          </div>

          <div class="action-btns">
            <a href="index.html" class="btn btn-cyan" style="padding: 12px 24px; font-weight: 700;">
              ← Back to Homepage
            </a>
            <a href="specialities.html" class="btn btn-outline" style="padding: 12px 24px;">
              Explore Departments
            </a>
          </div>
        </div>
      </div>
    </main>

    <!-- ================= FOOTER ================= -->
    <footer class="sf-footer">
      <div class="wrap">
        <div class="footer-grid">
          <div class="footer-col brand-col">
            <img src="assets/img/logo-sufia.png" alt="Sufia Healthcare" style="height: 48px; filter: brightness(0) invert(1); margin-bottom: 16px" />
            <p style="font-size: 0.86rem; color: #8fa0b8; line-height: 1.6">
              Sufia Healthcare Super Specialty Hospital delivers comprehensive medical care with 24x7 emergency, trauma, modern diagnostics, and dedicated super specialists in Siwan, Bihar.
            </p>
            <div style="margin-top: 14px; font-size: 0.84rem; color: var(--cyan-400)">
              📍 Hussaini More, Bindusar, Siwan, Bihar - 841277
            </div>
          </div>
          <div class="footer-col">
            <h5>Quick Links</h5>
            <ul>
              <li><a href="index.html">Home</a></li>
              <li><a href="doctors.html">Find a Doctor</a></li>
              <li><a href="contact.html">Book Appointment</a></li>
              <li><a href="careers">Careers</a></li>
              <li><a href="specialities.html">Departments</a></li>
            </ul>
          </div>
          <div class="footer-col">
            <h5>Portal Access</h5>
            <ul>
              <li><a href="login.html">Patient Login</a></li>
              <li><a href="admin/login.php">Admin Login</a></li>
              <li><a href="login.html">Doctor OPD Portal</a></li>
              <li><a href="login.html">Staff Console</a></li>
            </ul>
          </div>
          <div class="footer-col">
            <h5>Emergency</h5>
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
              width="280" height="130"
              style="border: 0; border-radius: 14px; box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);"
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

    <script src="js/app.js"></script>
    <script src="js/language.js"></script>
  </body>
</html>
