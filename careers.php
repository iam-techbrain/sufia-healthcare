<?php
session_start();
require_once __DIR__ . '/admin/db.php';

// Folder path requested by user: files/resume/
$upload_dir = __DIR__ . '/files/resume/';

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$success_msg = '';
$error_msg   = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {
    $name          = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS));
    $email         = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $phone         = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS));
    $position      = trim(filter_input(INPUT_POST, 'position', FILTER_SANITIZE_SPECIAL_CHARS)) ?: 'General Application';
    $experience    = trim(filter_input(INPUT_POST, 'experience', FILTER_SANITIZE_SPECIAL_CHARS));
    $qualification = trim(filter_input(INPUT_POST, 'qualification', FILTER_SANITIZE_SPECIAL_CHARS));
    $city          = trim(filter_input(INPUT_POST, 'city', FILTER_SANITIZE_SPECIAL_CHARS));
    $message       = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS));

    // Basic Validations
    if (empty($name) || empty($email) || empty($phone)) {
        $error_msg = "Please fill in all mandatory fields (Name, Email, and Phone).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please provide a valid email address.";
    } elseif (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
        $error_msg = "Please upload your resume file (PDF, DOC, or DOCX up to 5MB).";
    } else {
        $file_tmp  = $_FILES['resume']['tmp_name'];
        $file_name = $_FILES['resume']['name'];
        $file_size = $_FILES['resume']['size'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_exts = ['pdf', 'doc', 'docx'];
        $max_size     = 5 * 1024 * 1024; // 5MB

        if (!in_array($file_ext, $allowed_exts)) {
            $error_msg = "Invalid file type. Only PDF, DOC, and DOCX documents are accepted.";
        } elseif ($file_size > $max_size) {
            $error_msg = "File size exceeds 5MB limit. Please upload a smaller file.";
        } else {
            // Generate clean unique filename inside files/resume/
            $safe_base     = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file_name, PATHINFO_FILENAME));
            $new_file_name = 'Resume_' . time() . '_' . substr(md5(uniqid()), 0, 8) . '.' . $file_ext;
            $destination   = $upload_dir . $new_file_name;
            $rel_path      = 'files/resume/' . $new_file_name;

            if (move_uploaded_file($file_tmp, $destination)) {
                $ref_id = 'SUF-CAR-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));

                try {
                    $pdo = getDbConnection();
                    $stmt = $pdo->prepare("
                        INSERT INTO career_applications 
                        (id, name, email, phone, position, experience, qualification, city, message, resume_file, resume_path, status, applied_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'New', datetime('now', 'localtime'))
                    ");
                    $stmt->execute([
                        $ref_id,
                        $name,
                        $email,
                        $phone,
                        $position,
                        $experience ?: 'Not Specified',
                        $qualification ?: 'Not Specified',
                        $city ?: 'Not Specified',
                        $message ?: 'No message provided',
                        $new_file_name,
                        $rel_path
                    ]);

                    $_SESSION['career_submitted'] = [
                        'ref_id' => $ref_id,
                        'name'   => $name,
                        'email'  => $email,
                        'phone'  => $phone
                    ];
                    header("Location: career-success?ref=" . urlencode($ref_id));
                    exit;
                } catch (Exception $e) {
                    $error_msg = "Database Error: " . $e->getMessage();
                }
            } else {
                $error_msg = "Failed to upload your resume file. Please try again.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Primary SEO -->
    <title>Careers & Job Openings | Sufia Healthcare Super Specialty Hospital</title>
    <meta
      name="description"
      content="Join the healthcare team at Sufia Healthcare Super Specialty Hospital, Siwan. Explore medical, nursing, paramedical, and administrative career opportunities."
    />
    <meta
      name="keywords"
      content="Sufia Healthcare careers, hospital jobs Siwan, doctor vacancies Bihar, nursing jobs Siwan, medical staff recruitment"
    />
    <meta name="author" content="Sufia Healthcare Super Specialty Hospital" />
    <meta name="robots" content="index, follow, max-image-preview:large" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico" />
    <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png" />

    <!-- CSS -->
    <link rel="stylesheet" href="css/premium.css" />

    <style>
      .careers-section {
        padding: 50px 0 80px;
        background: var(--mist, #f5f8fa);
      }

      .careers-layout {
        max-width: 820px;
        margin: 0 auto;
      }

      /* Application Form Panel */
      .form-panel {
        background: #ffffff;
        border-radius: var(--radius-md, 16px);
        padding: 42px 48px;
        border: 1px solid var(--line, #e3e9ef);
        box-shadow: var(--shadow-sm, 0 4px 20px rgba(11, 37, 69, 0.07));
      }

      .form-panel h2 {
        font-family: var(--font-display, "Fraunces", Georgia, serif);
        font-size: 1.5rem;
        color: var(--navy-950, #071a2e);
        margin: 0 0 8px;
      }

      .form-group {
        margin-bottom: 18px;
      }

      .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
      }

      .form-group label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--navy-900, #0b2545);
        margin-bottom: 6px;
      }

      .form-group label span.req {
        color: var(--coral-600, #d9482f);
      }

      .form-control {
        width: 100%;
        padding: 11px 14px;
        font-size: 0.92rem;
        font-family: inherit;
        border: 1px solid var(--line, #e3e9ef);
        border-radius: 8px;
        background: #ffffff;
        color: var(--ink, #0e1a2b);
        transition: border-color 0.2s;
      }

      .form-control:focus {
        border-color: var(--cyan-500, #0eaba3);
        outline: none;
        box-shadow: 0 0 0 3px rgba(14, 171, 163, 0.15);
      }

      .file-upload-box {
        border: 2px dashed var(--line, #e3e9ef);
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        background: var(--mist, #f5f8fa);
        cursor: pointer;
        transition: border-color 0.2s;
      }

      .file-upload-box:hover {
        border-color: var(--cyan-500, #0eaba3);
        background: var(--cyan-50, #f1fbf9);
      }

      .file-upload-box input[type="file"] {
        display: none;
      }

      .upload-label {
        cursor: pointer;
        display: block;
      }

      .upload-icon {
        font-size: 1.8rem;
        margin-bottom: 6px;
      }

      .upload-text {
        font-size: 0.88rem;
        font-weight: 600;
        color: var(--navy-900, #0b2545);
      }

      .upload-hint {
        font-size: 0.78rem;
        color: var(--slate, #647186);
        margin-top: 4px;
      }

      .file-selected-name {
        margin-top: 8px;
        font-size: 0.82rem;
        color: var(--cyan-600, #0a8f8a);
        font-weight: 600;
      }

      /* Alert Notifications */
      .alert {
        padding: 14px 18px;
        border-radius: 10px;
        font-size: 0.92rem;
        margin-bottom: 22px;
        line-height: 1.5;
      }

      .alert-success {
        background: #e8f7ee;
        border: 1px solid #b7e4c7;
        color: #187a3d;
      }

      .alert-danger {
        background: #fdecec;
        border: 1px solid #f5c2c0;
        color: #b42318;
      }

      .benefits-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-top: 30px;
      }

      .benefit-box {
        background: #ffffff;
        border: 1px solid var(--line, #e3e9ef);
        border-radius: 12px;
        padding: 20px;
        text-align: center;
      }

      .benefit-box h4 {
        margin: 10px 0 6px;
        font-size: 0.98rem;
        color: var(--navy-950, #071a2e);
      }

      .benefit-box p {
        margin: 0;
        font-size: 0.82rem;
        color: var(--slate, #647186);
      }

      @media (max-width: 960px) {
        .careers-layout {
          grid-template-columns: 1fr;
        }

        .form-panel {
          padding: 24px 18px;
        }

        .form-row {
          grid-template-columns: 1fr;
        }

        .benefits-grid {
          grid-template-columns: 1fr;
        }
      }
    </style>
  </head>

  <body class="sf">
    <?php include_once __DIR__ . '/includes/header.php'; ?>


    <!-- ================= PAGE HERO ================= -->
    <section class="page-hero">
      <div class="wrap text-center">
        <span class="eyebrow" style="background: rgba(14, 171, 163, 0.2); color: var(--cyan-400)">CAREERS AT SUFIA HEALTHCARE · GROW WITH US</span>
        <h1>Build a Meaningful Healthcare Career</h1>
        <p class="lead" style="margin: 14px auto 0; max-width: 760px">
          Join our collaborative team of clinicians, nursing specialists, technologists, and administrative leaders delivering NABH-guided medical care in Siwan.
        </p>

        <div class="hero-feature-chips">
          <span class="hero-chip">🩺 50+ Doctors &amp; Specialists</span>
          <span class="hero-chip">🏥 3 Modular OTs &amp; Critical Care</span>
          <span class="hero-chip">📈 Clinical Training &amp; Growth</span>
          <span class="hero-chip">🛡️ Professional NABH Environment</span>
        </div>

        <div style="margin-top: 24px; display: flex; gap: 14px; justify-content: center; flex-wrap: wrap;">
          <a href="#apply-form" class="btn btn-cyan" style="padding: 12px 24px;">
            📝 Submit Your Resume / CV ↓
          </a>
          <a href="mailto:info@sufiahealthcare.com" class="btn btn-outline" style="padding: 12px 22px; color: #fff; border-color: rgba(255, 255, 255, 0.35);">
            ✉️ Email HR: info@sufiahealthcare.com
          </a>
        </div>
      </div>
    </section>

    <!-- ================= MAIN CAREERS CONTAINER ================= -->
    <main class="careers-section">
      <div class="wrap">
        <?php if (!empty($success_msg)): ?>
          <div class="alert alert-success">
            <?= $success_msg ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
          <div class="alert alert-danger">
            <strong>Notice:</strong> <?= htmlspecialchars($error_msg) ?>
          </div>
        <?php endif; ?>

        <div class="careers-layout">
          <!-- Job Application Form -->
          <div id="apply-form" class="form-panel">
            <h2>Apply Online / Submit Resume</h2>
            <p style="font-size: 0.88rem; color: var(--slate, #647186); margin: 0 0 24px;">
              Fill out the form below and upload your resume in PDF, DOC, or DOCX format (Max 5MB).
            </p>

            <form action="" method="POST" enctype="multipart/form-data">
              <div class="form-group">
                <label for="name">Full Name <span class="req">*</span></label>
                <input type="text" id="name" name="name" class="form-control" required placeholder="Enter your full name" />
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="email">Email Address <span class="req">*</span></label>
                  <input type="email" id="email" name="email" class="form-control" required placeholder="Enter your email address" />
                </div>
                <div class="form-group">
                  <label for="phone">Phone Number <span class="req">*</span></label>
                  <input type="tel" id="phone" name="phone" class="form-control" required placeholder="Enter your phone number" />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="qualification">Highest Qualification</label>
                  <input type="text" id="qualification" name="qualification" class="form-control" placeholder="e.g. B.Sc Nursing, MBBS, DMLT" />
                </div>
                <div class="form-group">
                  <label for="experience">Total Experience</label>
                  <select id="experience" name="experience" class="form-control">
                    <option value="Fresher">Fresher (0 Years)</option>
                    <option value="1 - 2 Years">1 – 2 Years</option>
                    <option value="3 - 5 Years">3 – 5 Years</option>
                    <option value="5+ Years">5+ Years</option>
                    <option value="10+ Years">10+ Years</option>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label for="city">Current Location / City</label>
                <input type="text" id="city" name="city" class="form-control" placeholder="Enter your current location" />
              </div>

              <div class="form-group">
                <label>Upload Resume / CV <span class="req">*</span></label>
                <div class="file-upload-box" onclick="document.getElementById('resume').click()">
                  <label class="upload-label" for="resume">
                    <div class="upload-icon">📄</div>
                    <div class="upload-text">Click to choose or drop your resume</div>
                    <div class="upload-hint">Accepted formats: PDF, DOC, DOCX (Max size: 5MB)</div>
                    <div id="fileNameDisplay" class="file-selected-name">No file selected yet</div>
                  </label>
                  <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx" required onchange="handleFileSelect(this)" />
                </div>
              </div>

              <div class="form-group">
                <label for="message">Cover Note / Additional Remarks</label>
                <textarea id="message" name="message" class="form-control" rows="3" placeholder="Tell us briefly about your clinical interests or background..."></textarea>
              </div>

              <button type="submit" name="submit_application" class="btn btn-cyan btn-block" style="width: 100%; padding: 14px; font-weight: 700; font-size: 1rem; margin-top: 10px;">
                    Submit Job Application &amp; Resume
              </button>
            </form>
          </div>
        </div>

        <!-- Working at Sufia Healthcare Benefits -->
        <div class="benefits-grid">
          <div class="benefit-box">
            <div style="font-size: 1.8rem;">🏥</div>
            <h4>Modern Infrastructure</h4>
            <p>State-of-the-art modular OTs, 24x7 ICU, advanced CT scan &amp; diagnostic labs.</p>
          </div>
          <div class="benefit-box">
            <div style="font-size: 1.8rem;">📈</div>
            <h4>Career Growth</h4>
            <p>Continuous clinical training, hands-on exposure with super specialists, and career advancement.</p>
          </div>
          <div class="benefit-box">
            <div style="font-size: 1.8rem;">🤝</div>
            <h4>Supportive Culture</h4>
            <p>Ethical patient-first environment, fair compensation, and professional respect for all staff.</p>
          </div>
        </div>
      </div>
    </main>

    <?php include_once __DIR__ . '/includes/footer.php'; ?>

    <script src="js/app.js"></script>
    <script src="js/language.js"></script>
    <script>
      function handleFileSelect(input) {
        const display = document.getElementById('fileNameDisplay');
        if (input.files && input.files[0]) {
          const file = input.files[0];
          display.innerHTML = '📎 Selected: <strong>' + file.name + '</strong> (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
          display.style.color = '#187a3d';
        } else {
          display.innerHTML = 'No file selected yet';
          display.style.color = 'var(--cyan-600)';
        }
      }
    </script>
  </body>
</html>
