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
    <?php include_once __DIR__ . '/includes/header.php'; ?>


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
            <a href="departments.html" class="btn btn-outline" style="padding: 12px 24px;">
              Explore Departments
            </a>
          </div>
        </div>
      </div>
    </main>

    <?php include_once __DIR__ . '/includes/footer.php'; ?>

    <script src="js/app.js"></script>
    <script src="js/language.js"></script>
  </body>
</html>
