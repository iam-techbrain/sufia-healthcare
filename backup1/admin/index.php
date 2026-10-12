<?php
session_start();
require_once __DIR__ . '/db.php';

// Security: Enforce admin session
if (!isset($_SESSION['sufia_admin_logged_in']) || $_SESSION['sufia_admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$pdo = getDbConnection();
$resume_dir = __DIR__ . '/../files/resume/';

$feedback = '';

// Handle Status Change
if (isset($_GET['action']) && $_GET['action'] === 'status' && isset($_GET['id']) && isset($_GET['val'])) {
    $target_id  = trim($_GET['id']);
    $new_status = trim($_GET['val']);
    $valid_statuses = ['New', 'In Review', 'Shortlisted', 'Rejected', 'Interviewed'];

    if (in_array($new_status, $valid_statuses)) {
        $stmt = $pdo->prepare("UPDATE career_applications SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $target_id]);
        header("Location: career-reports.php?msg=status_updated");
        exit;
    }
}

// Handle Delete Application
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = trim($_GET['id']);
    
    // Find resume filename first
    $stmt = $pdo->prepare("SELECT resume_file FROM career_applications WHERE id = ?");
    $stmt->execute([$del_id]);
    $file_to_del = $stmt->fetchColumn();

    if ($file_to_del && file_exists($resume_dir . $file_to_del)) {
        @unlink($resume_dir . $file_to_del);
    }

    $del_stmt = $pdo->prepare("DELETE FROM career_applications WHERE id = ?");
    $del_stmt->execute([$del_id]);

    header("Location: career-reports.php?msg=deleted");
    exit;
}

// Handle Export CSV
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Sufia_Career_Applications_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Ref ID', 'Name', 'Email', 'Phone', 'Position', 'Experience', 'Qualification', 'City', 'Status', 'Applied At', 'Resume File', 'Message']);

    $stmt = $pdo->query("SELECT * FROM career_applications ORDER BY applied_at DESC");
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['id'] ?? '',
            $row['name'] ?? '',
            $row['email'] ?? '',
            $row['phone'] ?? '',
            $row['position'] ?? '',
            $row['experience'] ?? '',
            $row['qualification'] ?? '',
            $row['city'] ?? '',
            $row['status'] ?? '',
            $row['applied_at'] ?? '',
            $row['resume_file'] ?? '',
            $row['message'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}

// Messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'status_updated') $feedback = "Candidate status updated successfully.";
    if ($_GET['msg'] === 'deleted') $feedback = "Application and resume file removed successfully.";
}

// Stats Calculation from SQLite
$total_apps  = $pdo->query("SELECT COUNT(*) FROM career_applications")->fetchColumn();
$new_count   = $pdo->query("SELECT COUNT(*) FROM career_applications WHERE status = 'New'")->fetchColumn();
$shortlisted = $pdo->query("SELECT COUNT(*) FROM career_applications WHERE status = 'Shortlisted'")->fetchColumn();
$today_count = $pdo->query("SELECT COUNT(*) FROM career_applications WHERE date(applied_at) = date('now', 'localtime')")->fetchColumn();

// Filtering & Search
$q_search   = trim($_GET['search'] ?? '');
$q_position = trim($_GET['position'] ?? '');
$q_status   = trim($_GET['status'] ?? '');

$sql = "SELECT * FROM career_applications WHERE 1=1";
$params = [];

if ($q_position !== '') {
    $sql .= " AND position = ?";
    $params[] = $q_position;
}

if ($q_status !== '') {
    $sql .= " AND status = ?";
    $params[] = $q_status;
}

if ($q_search !== '') {
    $sql .= " AND (name LIKE ? OR phone LIKE ? OR email LIKE ? OR id LIKE ? OR qualification LIKE ?)";
    $term = '%' . $q_search . '%';
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY applied_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$displayed_apps = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Career Applications &amp; Resume Reports | Sufia Healthcare Admin</title>
    <meta name="robots" content="noindex, nofollow" />

    <link rel="icon" type="image/x-icon" href="../assets/img/favicon.ico" />
    <link rel="stylesheet" href="../css/premium.css" />

    <style>
      :root {
        --admin-bg: #f4f7fb;
        --admin-card: #ffffff;
        --admin-border: #e2e8f0;
      }

      body.admin-body {
        background: var(--admin-bg);
        margin: 0;
        font-family: var(--font-body, "Inter", sans-serif);
        color: var(--ink, #0e1a2b);
        min-height: 100vh;
      }

      /* Admin Navigation Bar */
      .admin-navbar {
        background: var(--navy-950, #071a2e);
        color: #ffffff;
        padding: 14px 28px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
      }

      .admin-nav-brand {
        display: flex;
        align-items: center;
        gap: 14px;
        text-decoration: none;
        color: #ffffff;
      }

      .admin-nav-brand img {
        height: 38px;
        filter: brightness(0) invert(1);
      }

      .admin-nav-brand span {
        font-size: 0.95rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        background: rgba(255, 255, 255, 0.12);
        padding: 4px 10px;
        border-radius: 6px;
        color: var(--cyan-400, #3fc7bd);
      }

      .admin-nav-right {
        display: flex;
        align-items: center;
        gap: 18px;
      }

      .admin-user-pill {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
        color: #ccd7e6;
      }

      .admin-user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--cyan-600, #0a8f8a);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.8rem;
      }

      .admin-nav-btn {
        padding: 7px 14px;
        border-radius: 6px;
        font-size: 0.82rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
      }

      .btn-site {
        background: rgba(255, 255, 255, 0.12);
        color: #ffffff;
      }

      .btn-site:hover {
        background: rgba(255, 255, 255, 0.2);
      }

      .btn-logout {
        background: var(--coral-600, #d9482f);
        color: #ffffff;
      }

      .btn-logout:hover {
        background: #b53822;
      }

      /* Main Dashboard Container */
      .admin-container {
        max-width: 1360px;
        margin: 30px auto;
        padding: 0 24px;
      }

      .page-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 14px;
      }

      .page-header-row h1 {
        font-size: 1.6rem;
        color: var(--navy-950, #071a2e);
        margin: 0;
        font-weight: 800;
      }

      .header-actions {
        display: flex;
        gap: 12px;
      }

      /* KPI Cards */
      .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 28px;
      }

      .stat-card {
        background: #ffffff;
        border-radius: 14px;
        padding: 22px;
        border: 1px solid var(--admin-border);
        box-shadow: 0 2px 8px rgba(11, 37, 69, 0.04);
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      .stat-val {
        font-size: 1.85rem;
        font-weight: 800;
        color: var(--navy-950, #071a2e);
        line-height: 1.2;
      }

      .stat-label {
        font-size: 0.84rem;
        color: var(--slate, #647186);
        margin-top: 4px;
      }

      .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
      }

      /* Filter Bar */
      .filter-card {
        background: #ffffff;
        border-radius: 14px;
        padding: 18px 24px;
        border: 1px solid var(--admin-border);
        margin-bottom: 24px;
      }

      .filter-form {
        display: flex;
        gap: 14px;
        align-items: center;
        flex-wrap: wrap;
      }

      .filter-input {
        padding: 9px 12px;
        border: 1px solid var(--admin-border);
        border-radius: 8px;
        font-size: 0.88rem;
        font-family: inherit;
        min-width: 180px;
      }

      .filter-input.search-field {
        flex: 1;
        min-width: 220px;
      }

      /* Table Container */
      .table-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid var(--admin-border);
        box-shadow: 0 4px 16px rgba(11, 37, 69, 0.05);
        overflow: hidden;
      }

      .table-responsive {
        width: 100%;
        overflow-x: auto;
      }

      table.admin-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 0.88rem;
      }

      table.admin-table th {
        background: #f8fafc;
        padding: 14px 18px;
        color: var(--navy-900, #0b2545);
        font-weight: 700;
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-bottom: 1px solid var(--admin-border);
      }

      table.admin-table td {
        padding: 16px 18px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
      }

      table.admin-table tr:hover td {
        background: #fafcff;
      }

      /* Action Buttons */
      .btn-resume {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: #0eaba3;
        color: #ffffff;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        text-decoration: none;
        transition: 0.2s;
      }

      .btn-resume:hover {
        background: #088c85;
      }

      .btn-icon {
        background: none;
        border: 1px solid var(--admin-border);
        padding: 6px 10px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.82rem;
        transition: all 0.2s;
      }

      .btn-icon:hover {
        background: #f1f5f9;
      }

      .btn-delete {
        color: #b42318;
      }

      .btn-delete:hover {
        background: #fdecec;
        border-color: #f5c2c0;
      }

      /* Modal for candidate notes */
      .modal-backdrop {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(7, 26, 46, 0.6);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
      }

      .modal-card {
        background: #ffffff;
        border-radius: 16px;
        width: 90%;
        max-width: 520px;
        padding: 28px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
      }

      .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--admin-border);
      }

      @media (max-width: 1024px) {
        .stats-grid {
          grid-template-columns: repeat(2, 1fr);
        }
      }

      @media (max-width: 640px) {
        .stats-grid {
          grid-template-columns: 1fr;
        }
      }
    </style>
  </head>

  <body class="admin-body">
    <!-- Navbar -->
    <header class="admin-navbar">
      <a href="career-reports.php" class="admin-nav-brand">
        <img src="../assets/img/logo-sufia.png" alt="Sufia Healthcare" />
        <span>RECRUITMENT REPORTS</span>
      </a>

      <div class="admin-nav-right">
        <div class="admin-user-pill">
          <div class="admin-user-avatar">AD</div>
          <div>
            <strong><?= htmlspecialchars($_SESSION['sufia_admin_user'] ?? 'Admin') ?></strong>
          </div>
        </div>
        <a href="../careers.php" target="_blank" class="admin-nav-btn btn-site">🌐 View Careers Page</a>
        <a href="login.php?action=logout" class="admin-nav-btn btn-logout">Sign Out</a>
      </div>
    </header>

    <!-- Main Container -->
    <main class="admin-container">
      <?php if (!empty($feedback)): ?>
        <div style="background: #e8f7ee; border: 1px solid #b7e4c7; color: #187a3d; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px;">
          ✓ <?= htmlspecialchars($feedback) ?>
        </div>
      <?php endif; ?>

      <div class="page-header-row">
        <div>
          <h1>Career Applications Report</h1>
          <p style="margin: 4px 0 0; color: var(--slate, #647186); font-size: 0.9rem;">
            Real-time candidate submissions and resumes stored in <code>files/resume/</code> and <code>admin/database.sqlite</code>.
          </p>
        </div>

        <div class="header-actions">
          <a href="career-reports.php?action=export_csv" class="btn-site" style="padding: 10px 18px; border-radius: 8px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; background: #071a2e; color: #ffffff;">
            📥 Export to Excel/CSV
          </a>
        </div>
      </div>

      <!-- KPI Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div>
            <div class="stat-val"><?= $total_apps ?></div>
            <div class="stat-label">Total Applications</div>
          </div>
          <div class="stat-icon" style="background: #e3f6f3; color: #0a8f8a;">📋</div>
        </div>

        <div class="stat-card">
          <div>
            <div class="stat-val"><?= $new_count ?></div>
            <div class="stat-label">New / Unreviewed</div>
          </div>
          <div class="stat-icon" style="background: #e8f7ee; color: #187a3d;">✨</div>
        </div>

        <div class="stat-card">
          <div>
            <div class="stat-val"><?= $shortlisted ?></div>
            <div class="stat-label">Shortlisted Candidates</div>
          </div>
          <div class="stat-icon" style="background: #fff8e6; color: #b7791f;">⭐</div>
        </div>

        <div class="stat-card">
          <div>
            <div class="stat-val"><?= $today_count ?></div>
            <div class="stat-label">Received Today</div>
          </div>
          <div class="stat-icon" style="background: #eef2f6; color: #123564;">📅</div>
        </div>
      </div>

      <!-- Filter Bar -->
      <div class="filter-card">
        <form action="career-reports.php" method="GET" class="filter-form">
          <input
            type="text"
            name="search"
            class="filter-input search-field"
            placeholder="Search candidate by name, phone, email, qualification..."
            value="<?= htmlspecialchars($q_search) ?>"
          />

          <select name="position" class="filter-input">
            <option value="">All Positions</option>
            <option value="Staff Nurse - ICU / OT / Ward" <?= $q_position === 'Staff Nurse - ICU / OT / Ward' ? 'selected' : '' ?>>Staff Nurse</option>
            <option value="Resident Medical Officer (RMO)" <?= $q_position === 'Resident Medical Officer (RMO)' ? 'selected' : '' ?>>Resident Medical Officer (RMO)</option>
            <option value="OT & Anaesthesia Technician" <?= $q_position === 'OT & Anaesthesia Technician' ? 'selected' : '' ?>>OT & Anaesthesia Technician</option>
            <option value="Lab Technician (Pathology / Biochemistry)" <?= $q_position === 'Lab Technician (Pathology / Biochemistry)' ? 'selected' : '' ?>>Lab Technician</option>
            <option value="Pharmacist (B.Pharm / D.Pharm)" <?= $q_position === 'Pharmacist (B.Pharm / D.Pharm)' ? 'selected' : '' ?>>Pharmacist</option>
            <option value="General Application" <?= $q_position === 'General Application' ? 'selected' : '' ?>>General Application</option>
            <option value="Other / General Application" <?= $q_position === 'Other / General Application' ? 'selected' : '' ?>>Other Roles</option>
          </select>

          <select name="status" class="filter-input">
            <option value="">All Statuses</option>
            <option value="New" <?= $q_status === 'New' ? 'selected' : '' ?>>New</option>
            <option value="In Review" <?= $q_status === 'In Review' ? 'selected' : '' ?>>In Review</option>
            <option value="Shortlisted" <?= $q_status === 'Shortlisted' ? 'selected' : '' ?>>Shortlisted</option>
            <option value="Interviewed" <?= $q_status === 'Interviewed' ? 'selected' : '' ?>>Interviewed</option>
            <option value="Rejected" <?= $q_status === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
          </select>

          <button type="submit" style="padding: 9px 18px; background: #0eaba3; color: #ffffff; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">
            Apply Filter
          </button>

          <?php if (!empty($q_search) || !empty($q_position) || !empty($q_status)): ?>
            <a href="career-reports.php" style="font-size: 0.85rem; color: #b42318; text-decoration: underline; margin-left: 8px;">
              Reset Filters
            </a>
          <?php endif; ?>
        </form>
      </div>

      <!-- Applications Table -->
      <div class="table-card">
        <div class="table-responsive">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Ref ID &amp; Date</th>
                <th>Candidate Details</th>
                <th>Applied Position</th>
                <th>Experience &amp; Qual.</th>
                <th>Resume / CV</th>
                <th>Status</th>
                <th style="text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($displayed_apps)): ?>
                <tr>
                  <td colspan="7" style="text-align: center; padding: 40px; color: var(--slate, #647186);">
                    <div style="font-size: 2.2rem; margin-bottom: 8px;">📭</div>
                    <strong>No job applications found in SQLite database.</strong>
                    <div style="margin-top: 6px; font-size: 0.85rem;">Applications submitted on the careers page will automatically appear here.</div>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($displayed_apps as $app): ?>
                  <tr>
                    <td>
                      <code style="font-size: 0.78rem; font-weight: 700; color: #0b2545; background: #eef2f6; padding: 3px 6px; border-radius: 4px;">
                        <?= htmlspecialchars($app['id'] ?? 'N/A') ?>
                      </code>
                      <div style="font-size: 0.75rem; color: #647186; margin-top: 4px;">
                        <?= htmlspecialchars(date('d M Y, h:i A', strtotime($app['applied_at'] ?? 'now'))) ?>
                      </div>
                    </td>

                    <td>
                      <div style="font-weight: 700; color: #071a2e; font-size: 0.94rem;">
                        <?= htmlspecialchars($app['name'] ?? 'Candidate') ?>
                      </div>
                      <div style="font-size: 0.8rem; color: #647186;">
                        📞 <?= htmlspecialchars($app['phone'] ?? '') ?>
                      </div>
                      <div style="font-size: 0.8rem; color: #647186;">
                        ✉️ <?= htmlspecialchars($app['email'] ?? '') ?>
                      </div>
                      <?php if (!empty($app['city'])): ?>
                        <div style="font-size: 0.75rem; color: #0eaba3;">
                          📍 <?= htmlspecialchars($app['city']) ?>
                        </div>
                      <?php endif; ?>
                    </td>

                    <td>
                      <strong style="color: #0b2545;">
                        <?= htmlspecialchars($app['position'] ?? 'General Application') ?>
                      </strong>
                    </td>

                    <td>
                      <div style="font-size: 0.84rem;">
                        <strong>Exp:</strong> <?= htmlspecialchars($app['experience'] ?? 'Fresher') ?>
                      </div>
                      <div style="font-size: 0.8rem; color: #647186;">
                        <strong>Qual:</strong> <?= htmlspecialchars($app['qualification'] ?? 'N/A') ?>
                      </div>
                    </td>

                    <td>
                      <?php if (!empty($app['resume_file']) && file_exists($resume_dir . $app['resume_file'])): ?>
                        <a href="../files/resume/<?= urlencode($app['resume_file']) ?>" target="_blank" class="btn-resume">
                          📄 Download CV
                        </a>
                        <div style="font-size: 0.72rem; color: #647186; margin-top: 3px;">
                          <?= htmlspecialchars(substr($app['resume_file'], 0, 20)) ?>...
                        </div>
                      <?php else: ?>
                        <span style="font-size: 0.78rem; color: #b42318;">No file in files/resume/</span>
                      <?php endif; ?>
                    </td>

                    <td>
                      <select onchange="updateStatus('<?= htmlspecialchars($app['id']) ?>', this.value)" style="padding: 4px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; border: 1px solid var(--admin-border); background: #ffffff;">
                        <option value="New" <?= ($app['status'] ?? '') === 'New' ? 'selected' : '' ?>>New</option>
                        <option value="In Review" <?= ($app['status'] ?? '') === 'In Review' ? 'selected' : '' ?>>In Review</option>
                        <option value="Shortlisted" <?= ($app['status'] ?? '') === 'Shortlisted' ? 'selected' : '' ?>>Shortlisted</option>
                        <option value="Interviewed" <?= ($app['status'] ?? '') === 'Interviewed' ? 'selected' : '' ?>>Interviewed</option>
                        <option value="Rejected" <?= ($app['status'] ?? '') === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                      </select>
                    </td>

                    <td style="text-align: right; white-space: nowrap;">
                      <button type="button" class="btn-icon" title="View Message / Cover Note" onclick="viewMessage('<?= htmlspecialchars(addslashes($app['name'] ?? 'Candidate')) ?>', '<?= htmlspecialchars(addslashes($app['message'] ?? 'No message')) ?>')">
                        💬 Note
                      </button>
                      <button type="button" class="btn-icon btn-delete" title="Delete Application" onclick="confirmDelete('<?= htmlspecialchars($app['id']) ?>')">
                        🗑️
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- Message Modal -->
    <div id="noteModal" class="modal-backdrop" onclick="closeModal()">
      <div class="modal-card" onclick="event.stopPropagation()">
        <div class="modal-header">
          <h3 id="modalTitle" style="margin: 0; font-size: 1.15rem; color: #071a2e;">Candidate Note</h3>
          <button type="button" onclick="closeModal()" style="border: none; background: none; font-size: 1.3rem; cursor: pointer;">&times;</button>
        </div>
        <div id="modalBody" style="font-size: 0.92rem; line-height: 1.6; color: #33465e; white-space: pre-wrap; background: #f8fafc; padding: 16px; border-radius: 8px;"></div>
        <div style="margin-top: 18px; text-align: right;">
          <button type="button" onclick="closeModal()" style="padding: 8px 16px; border-radius: 6px; background: #071a2e; color: #ffffff; border: none; cursor: pointer; font-weight: 600;">Close</button>
        </div>
      </div>
    </div>

    <script>
      function updateStatus(id, val) {
        window.location.href = 'career-reports.php?action=status&id=' + encodeURIComponent(id) + '&val=' + encodeURIComponent(val);
      }

      function confirmDelete(id) {
        if (confirm('Are you sure you want to permanently delete application ' + id + '? This will also remove the uploaded resume.')) {
          window.location.href = 'career-reports.php?action=delete&id=' + encodeURIComponent(id);
        }
      }

      function viewMessage(name, message) {
        document.getElementById('modalTitle').textContent = 'Cover Note - ' + name;
        document.getElementById('modalBody').textContent = message || 'No additional remarks provided by candidate.';
        document.getElementById('noteModal').style.display = 'flex';
      }

      function closeModal() {
        document.getElementById('noteModal').style.display = 'none';
      }
    </script>
  </body>
</html>
