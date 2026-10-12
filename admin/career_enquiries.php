<?php
session_start();
require_once __DIR__ . '/db.php';

// Security: Enforce admin session
if (!isset($_SESSION['sufia_admin_logged_in']) || $_SESSION['sufia_admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$pdo = getDbConnection();
$current_user   = getCurrentAdminUser($pdo);
$active_sidebar = $current_user['sidebar_color'] ?? 'dark';
$active_header  = $current_user['header_color'] ?? 'white';
$active_footer  = $current_user['footer_color'] ?? 'white';
$total_users    = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
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
        header("Location: career_enquiries.php?msg=status_updated");
        exit;
    }
}

// Handle Delete Application
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = trim($_GET['id']);
    
    $stmt = $pdo->prepare("SELECT resume_file FROM career_applications WHERE id = ?");
    $stmt->execute([$del_id]);
    $file_to_del = $stmt->fetchColumn();

    if ($file_to_del && file_exists($resume_dir . $file_to_del)) {
        @unlink($resume_dir . $file_to_del);
    }

    $del_stmt = $pdo->prepare("DELETE FROM career_applications WHERE id = ?");
    $del_stmt->execute([$del_id]);

    header("Location: career_enquiries.php?msg=deleted");
    exit;
}

// Flash Messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'status_updated') $feedback = "Candidate status updated successfully.";
    if ($_GET['msg'] === 'deleted') $feedback = "Application record removed successfully.";
}

// Stats Calculation
$total_apps  = $pdo->query("SELECT COUNT(*) FROM career_applications")->fetchColumn();
$new_count   = $pdo->query("SELECT COUNT(*) FROM career_applications WHERE status = 'New'")->fetchColumn();
$shortlisted = $pdo->query("SELECT COUNT(*) FROM career_applications WHERE status = 'Shortlisted'")->fetchColumn();
$today_count = $pdo->query("SELECT COUNT(*) FROM career_applications WHERE date(applied_at) = date('now', 'localtime')")->fetchColumn();

// --- PHP Search, Filter & Pagination Logic ---
$search   = trim($_GET['search'] ?? '');
$status   = trim($_GET['status'] ?? '');
$position = trim($_GET['position'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset   = ($page - 1) * $per_page;

$where_clauses = [];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR position LIKE ? OR city LIKE ? OR id = ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = (int)$search;
}

if ($status !== '' && $status !== 'all') {
    $where_clauses[] = "status = ?";
    $params[] = $status;
}

if ($position !== '' && $position !== 'all') {
    $where_clauses[] = "position = ?";
    $params[] = $position;
}

$where_sql = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Handle Export CSV (Excel compatible)
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Sufia_Career_Applications_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // Add UTF-8 BOM so Microsoft Excel renders Hindi/special characters correctly
    fputs($output, "\xEF\xBB\xBF");

    // Build absolute base URL for downloadable resume files
    $is_https   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    $protocol   = $is_https ? "https://" : "http://";
    $host       = $_SERVER['HTTP_HOST'] ?? 'sufiahealthcare.in';
    $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $root_dir   = rtrim(preg_replace('#/admin/?$#i', '', $script_dir), '/');
    $base_url   = $protocol . $host . $root_dir;

    fputcsv($output, ['Ref ID', 'Name', 'Email', 'Phone', 'Position', 'Experience', 'Qualification', 'City', 'Status', 'Applied At', 'Resume File', 'Resume URL', 'Message']);

    $stmt = $pdo->prepare("SELECT * FROM career_applications $where_sql ORDER BY datetime(applied_at) DESC, rowid DESC");
    $stmt->execute($params);
    while ($row = $stmt->fetch()) {
        $resume_file = trim($row['resume_file'] ?? '');
        $resume_url  = '';
        if ($resume_file !== '') {
            $resume_url = $base_url . '/files/resume/' . rawurlencode(basename($resume_file));
        }

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
            $resume_file,
            $resume_url,
            $row['message'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}

// Count total matching records
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM career_applications $where_sql");
$count_stmt->execute($params);
$total_records = (int)$count_stmt->fetchColumn();

// Calculate total pages
$total_pages = max(1, (int)ceil($total_records / $per_page));
if ($page > $total_pages && $total_records > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}

// Fetch paginated applications strictly ordered by latest date and rowid DESC (newest first)
$data_stmt = $pdo->prepare("SELECT * FROM career_applications $where_sql ORDER BY datetime(applied_at) DESC, rowid DESC LIMIT $per_page OFFSET $offset");
$data_stmt->execute($params);
$all_apps = $data_stmt->fetchAll();

// Get unique positions list for position filter dropdown
$distinct_positions = $pdo->query("SELECT DISTINCT position FROM career_applications WHERE position IS NOT NULL AND position != '' ORDER BY position ASC")->fetchAll(PDO::FETCH_COLUMN);

// Pagination URL builder
function getPageUrl($p, $search, $status, $position) {
    $params = ['page' => $p];
    if ($search !== '') $params['search'] = $search;
    if ($status !== '' && $status !== 'all') $params['status'] = $status;
    if ($position !== '' && $position !== 'all') $params['position'] = $position;
    return 'career_enquiries.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Career Applications &amp; Resumes - Sufia Healthcare Admin</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/kaiadmin/favicon.ico" type="image/x-icon" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico" />
    <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png" />
  
    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
      WebFont.load({
        google: { families: ["Public Sans:300,400,500,600,700"] },
        custom: {
          families: [
            "Font Awesome 5 Solid",
            "Font Awesome 5 Regular",
            "Font Awesome 5 Brands",
            "simple-line-icons",
          ],
          urls: ["assets/css/fonts.min.css"],
        },
        active: function () {
          sessionStorage.fonts = true;
        },
      });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="assets/css/demo.css" />

    <!-- Dynamic User Theme Styles -->
    <?= renderThemeStyles($active_sidebar, $active_header, $active_footer) ?>

    <style>
      .btn-action-view {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        background: #eff6ff;
        color: #2563eb;
        border: 1px solid #dbeafe;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
      }
      .btn-action-view:hover {
        background: #2563eb;
        color: #ffffff !important;
        border-color: #2563eb;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
      }
      .btn-action-view i, .btn-action-delete i {
        transition: transform 0.2s ease;
      }
      .btn-action-view:hover i, .btn-action-delete:hover i {
        transform: scale(1.15);
      }
      .btn-action-delete {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fee2e2;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
      }
      .btn-action-delete:hover {
        background: #dc2626;
        color: #ffffff !important;
        border-color: #dc2626;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25);
      }
    </style>
  </head>
  <body>
    <div class="wrapper">
      <!-- Sidebar -->
      <div class="sidebar" data-background-color="<?= htmlspecialchars($active_sidebar) ?>">
        <div class="sidebar-logo">
          <!-- Logo Header -->
          <div class="logo-header" data-background-color="<?= htmlspecialchars($active_sidebar) ?>">
            <a href="index.php" class="logo">
              <img
                src="assets/img/logo-sufia.png"
                alt="navbar brand"
                class="navbar-brand"
                height="36"
                style="max-width: 170px; object-fit: contain;"
              />
            </a>
            <div class="nav-toggle">
              <button class="btn btn-toggle toggle-sidebar">
                <i class="gg-menu-right"></i>
              </button>
              <button class="btn btn-toggle sidenav-toggler">
                <i class="gg-menu-left"></i>
              </button>
            </div>
            <button class="topbar-toggler more">
              <i class="gg-more-vertical-alt"></i>
            </button>
          </div>
          <!-- End Logo Header -->
        </div>
        <div class="sidebar-wrapper scrollbar scrollbar-inner">
          <div class="sidebar-content">
            <ul class="nav nav-secondary">
              <li class="nav-item">
                <a href="index.php">
                  <i class="fas fa-home"></i>
                  <p>Dashboard</p>
                </a>
              </li>
              <li class="nav-section">
                <span class="sidebar-mini-icon">
                  <i class="fa fa-ellipsis-h"></i>
                </span>
                <h4 class="text-section">Management</h4>
              </li>
              <li class="nav-item">
                <a href="contact_enquiries.php">
                  <i class="fas fa-envelope"></i>
                  <p>Contact Enquiries</p>
                </a>
              </li>
              <li class="nav-item active">
                <a href="career_enquiries.php">
                  <i class="fas fa-user-md"></i>
                  <p>Career Applications</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="users.php">
                  <i class="fas fa-users"></i>
                  <p>Users List</p>
                </a>
              </li>
              <li class="nav-section">
                <span class="sidebar-mini-icon">
                  <i class="fa fa-ellipsis-h"></i>
                </span>
                <h4 class="text-section">Personal Settings</h4>
              </li>
              <li class="nav-item">
                <a href="profile.php">
                  <i class="fas fa-user-cog"></i>
                  <p>Profile &amp; Theme</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="../index.html" target="_blank">
                  <i class="fas fa-globe"></i>
                  <p>Live Website</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="login.php?action=logout">
                  <i class="fas fa-sign-out-alt"></i>
                  <p>Logout</p>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
      <!-- End Sidebar -->

      <div class="main-panel">
        <div class="main-header">
          <div class="main-header-logo">
            <!-- Logo Header -->
            <div class="logo-header" data-background-color="dark">
              <a href="index.php" class="logo">
                <img
                  src="assets/img/logo-sufia.png"
                  alt="navbar brand"
                  class="navbar-brand"
                  height="30"
                />
              </a>
              <div class="nav-toggle">
                <button class="btn btn-toggle toggle-sidebar">
                  <i class="gg-menu-right"></i>
                </button>
                <button class="btn btn-toggle sidenav-toggler">
                  <i class="gg-menu-left"></i>
                </button>
              </div>
              <button class="topbar-toggler more">
                <i class="gg-more-vertical-alt"></i>
              </button>
            </div>
            <!-- End Logo Header -->
          </div>
          <!-- Navbar Header -->
          <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
            <div class="container-fluid">
              <nav class="navbar navbar-header-left navbar-expand-lg navbar-form nav-search p-0 d-none d-lg-flex">
                <form method="GET" action="career_enquiries.php" class="d-flex align-items-center">
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <button type="submit" class="btn btn-search pe-1">
                        <i class="fa fa-search search-icon"></i>
                      </button>
                    </div>
                    <input
                      type="text"
                      name="search"
                      value="<?= htmlspecialchars($search) ?>"
                      placeholder="Search career applications..."
                      class="form-control"
                    />
                  </div>
                </form>
              </nav>

              <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                <li class="nav-item">
                  <span class="badge badge-success px-3 py-2">🟢 Careers Portal Active</span>
                </li>

                <li class="nav-item topbar-user dropdown hidden-caret ms-2">
                  <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                    <div class="avatar-sm">
                      <img src="assets/img/profile.jpg" alt="admin" class="avatar-img rounded-circle" />
                    </div>
                    <span class="profile-username">
                      <span class="op-7">Hi,</span>
                      <span class="fw-bold"><?= htmlspecialchars($current_user['name']) ?></span>
                    </span>
                  </a>
                  <ul class="dropdown-menu dropdown-user animated fadeIn">
                    <div class="dropdown-user-scroll scrollbar-outer">
                      <li>
                        <div class="user-box">
                          <div class="avatar-lg">
                            <img src="assets/img/profile.jpg" alt="profile" class="avatar-img rounded" />
                          </div>
                          <div class="u-text">
                            <h4><?= htmlspecialchars($current_user['name']) ?></h4>
                            <p class="text-muted"><?= htmlspecialchars($current_user['email'] ?: 'admin@sufiahealthcare.com') ?></p>
                            <a href="profile.php" class="btn btn-xs btn-secondary btn-sm">Edit Profile</a>
                          </div>
                        </div>
                      </li>
                      <li>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="profile.php"><i class="fas fa-palette me-2 text-primary"></i> Theme Colors</a>
                        <a class="dropdown-item" href="users.php"><i class="fas fa-users me-2 text-info"></i> Users List</a>
                        <a class="dropdown-item" href="contact_enquiries.php"><i class="fas fa-envelope me-2"></i> Patient Enquiries</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="login.php?action=logout"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
                      </li>
                    </div>
                  </ul>
                </li>
              </ul>
            </div>
          </nav>
          <!-- End Navbar -->
        </div>

        <div class="container">
          <div class="page-inner">
            <div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
              <div>
                <ul class="breadcrumbs mb-0" style="margin-left: 0; padding-left: 0; border-left: none;">
                  <li class="nav-home">
                    <a href="index.php"><i class="icon-home"></i></a>
                  </li>
                  <li class="separator"><i class="icon-arrow-right"></i></li>
                  <li class="nav-item"><a href="index.php">Dashboard</a></li>
                  <li class="separator"><i class="icon-arrow-right"></i></li>
                  <li class="nav-item"><a href="career_enquiries.php">Career Applications</a></li>
                </ul>
              </div>
              <div class="d-flex align-items-center gap-2">
                <a href="career_enquiries.php?action=export_csv<?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['action' => ''])) : '' ?>" class="btn btn-success btn-round btn-sm" title="Download Excel / CSV">
                  <i class="fas fa-file-excel me-1"></i> Export to Excel (CSV)
                </a>
              </div>
            </div>

            <?php if ($feedback): ?>
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>✓ Success!</strong> <?= htmlspecialchars($feedback) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>

            <!-- KPI Summary Cards -->
            <div class="row">
              <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-icon">
                        <div class="icon-big text-center icon-primary bubble-shadow-small">
                          <i class="fas fa-file-medical-alt"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">Total Resumes</p>
                          <h4 class="card-title"><?= number_format($total_apps) ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-icon">
                        <div class="icon-big text-center icon-warning bubble-shadow-small">
                          <i class="fas fa-hourglass-half"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">New / Unreviewed</p>
                          <h4 class="card-title"><?= number_format($new_count) ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-icon">
                        <div class="icon-big text-center icon-success bubble-shadow-small">
                          <i class="fas fa-user-check"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">Shortlisted</p>
                          <h4 class="card-title"><?= number_format($shortlisted) ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-icon">
                        <div class="icon-big text-center icon-secondary bubble-shadow-small">
                          <i class="far fa-calendar-check"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">Received Today</p>
                          <h4 class="card-title"><?= number_format($today_count) ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- KaiAdmin Datatable -->
            <div class="row">
              <div class="col-md-12">
                <div class="card card-round">
                  <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                      <div>
                        <h4 class="card-title mb-1">Candidate Job Applications</h4>
                        <small class="text-muted">Page <?= $page ?> of <?= $total_pages ?> · Total <?= number_format($total_records) ?> Candidates</small>
                      </div>
                      <div class="d-flex gap-2">
                        <a href="career_enquiries.php?action=export_csv<?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['action' => ''])) : '' ?>" class="btn btn-sm btn-outline-success btn-round" title="Download Excel / CSV">
                          <i class="fas fa-file-excel me-1"></i> Export Excel (CSV)
                        </a>
                      </div>
                    </div>
                  </div>
                  <div class="card-body">
                    <!-- PHP Filter Form -->
                    <form method="GET" action="career_enquiries.php" class="row g-2 mb-4 align-items-end p-3 rounded" style="background: #f8fafc; border: 1px solid #ebedf2;">
                      <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1"><i class="fas fa-search me-1"></i> Search Candidates</label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, phone, email, city, #ID..." value="<?= htmlspecialchars($search) ?>" />
                      </div>
                      <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1"><i class="fas fa-filter me-1"></i> Status</label>
                        <select name="status" class="form-select form-select-sm">
                          <option value="all" <?= ($status === '' || $status === 'all') ? 'selected' : '' ?>>All Statuses</option>
                          <option value="New" <?= ($status === 'New') ? 'selected' : '' ?>>New</option>
                          <option value="In Review" <?= ($status === 'In Review') ? 'selected' : '' ?>>In Review</option>
                          <option value="Shortlisted" <?= ($status === 'Shortlisted') ? 'selected' : '' ?>>Shortlisted</option>
                          <option value="Interviewed" <?= ($status === 'Interviewed') ? 'selected' : '' ?>>Interviewed</option>
                          <option value="Rejected" <?= ($status === 'Rejected') ? 'selected' : '' ?>>Rejected</option>
                        </select>
                      </div>
                      <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1"><i class="fas fa-briefcase me-1"></i> Position Applied</label>
                        <select name="position" class="form-select form-select-sm">
                          <option value="all" <?= ($position === '' || $position === 'all') ? 'selected' : '' ?>>All Positions</option>
                          <?php foreach ($distinct_positions as $pos): ?>
                            <option value="<?= htmlspecialchars($pos) ?>" <?= ($position === $pos) ? 'selected' : '' ?>><?= htmlspecialchars($pos) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                          <i class="fas fa-filter me-1"></i> Filter
                        </button>
                        <?php if ($search !== '' || ($status !== '' && $status !== 'all') || ($position !== '' && $position !== 'all')): ?>
                          <a href="career_enquiries.php" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                            <i class="fas fa-redo"></i>
                          </a>
                        <?php endif; ?>
                      </div>
                    </form>

                    <!-- Static Table Responsive -->
                    <div class="table-responsive">
                      <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                          <tr>
                            <th style="width: 140px;">Ref ID</th>
                            <th style="min-width: 200px;">Candidate Details</th>
                            <th style="min-width: 150px;">Qualification &amp; Exp</th>
                            <th style="min-width: 180px;">Position Applied</th>
                            <th style="min-width: 130px;">Change Status</th>
                            <th style="min-width: 130px;">Applied Date</th>
                            <th style="width: 90px;" class="text-center">Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php if (empty($all_apps)): ?>
                            <tr>
                              <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 text-secondary d-block"></i>
                                <h5>No Candidate Applications Found</h5>
                                <p class="mb-0 small">Try changing or clearing your search filters.</p>
                              </td>
                            </tr>
                          <?php else: ?>
                            <?php foreach ($all_apps as $app): 
                              $badge_class = match($app['status']) {
                                'New' => 'badge-info',
                                'In Review' => 'badge-warning',
                                'Shortlisted' => 'badge-success',
                                'Interviewed' => 'badge-primary',
                                'Rejected' => 'badge-danger',
                                default => 'badge-info'
                              };
                            ?>
                              <tr>
                                <td><strong class="text-primary font-monospace" style="font-size: 0.82rem;">#<?= htmlspecialchars($app['id']) ?></strong></td>
                                <td>
                                  <strong class="text-dark fs-6"><?= htmlspecialchars($app['name']) ?></strong>
                                  <div class="text-muted small mt-1">
                                    <i class="fa fa-phone me-1 text-secondary"></i><?= htmlspecialchars($app['phone']) ?>
                                  </div>
                                  <div class="text-muted small">
                                    <i class="fa fa-envelope me-1 text-secondary"></i><?= htmlspecialchars($app['email']) ?>
                                  </div>
                                  <div class="text-muted small">
                                    <i class="fa fa-map-marker-alt me-1 text-danger"></i><?= htmlspecialchars($app['city'] ?: 'Bihar') ?>
                                  </div>
                                  <div class="mt-1">
                                    <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($app['status'] ?: 'New') ?></span>
                                  </div>
                                </td>
                                <td>
                                  <div class="fw-semibold text-dark"><?= htmlspecialchars($app['qualification'] ?: 'Not Specified') ?></div>
                                  <span class="badge badge-count mt-1">Exp: <?= htmlspecialchars($app['experience'] ?: 'Fresher') ?></span>
                                </td>
                                <td>
                                  <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($app['position']) ?></div>
                                  <?php if (!empty($app['resume_file'])): ?>
                                    <a href="../files/resume/<?= urlencode($app['resume_file']) ?>" target="_blank" class="btn btn-xs btn-outline-primary btn-round">
                                      <i class="fa fa-file-pdf me-1"></i> View CV
                                    </a>
                                  <?php else: ?>
                                    <span class="text-muted small">No File</span>
                                  <?php endif; ?>
                                </td>
                                <td>
                                  <select class="form-select form-select-sm" style="min-width: 110px;" onchange="updateStatus('<?= htmlspecialchars($app['id']) ?>', this.value)">
                                    <option value="New" <?= ($app['status'] === 'New') ? 'selected' : '' ?>>New</option>
                                    <option value="In Review" <?= ($app['status'] === 'In Review') ? 'selected' : '' ?>>In Review</option>
                                    <option value="Shortlisted" <?= ($app['status'] === 'Shortlisted') ? 'selected' : '' ?>>Shortlisted</option>
                                    <option value="Interviewed" <?= ($app['status'] === 'Interviewed') ? 'selected' : '' ?>>Interviewed</option>
                                    <option value="Rejected" <?= ($app['status'] === 'Rejected') ? 'selected' : '' ?>>Rejected</option>
                                  </select>
                                </td>
                                <td class="text-muted small text-nowrap">
                                  <div><i class="far fa-calendar-alt text-primary me-1"></i><?= date('d M Y', strtotime($app['applied_at'])) ?></div>
                                  <div style="font-size: 0.72rem;"><i class="far fa-clock text-secondary me-1"></i><?= date('h:i A', strtotime($app['applied_at'])) ?></div>
                                </td>
                                <td class="text-center">
                                  <div class="d-inline-flex gap-2 align-items-center justify-content-center">
                                    <button type="button" class="btn-action-view" title="View Full Details" onclick='viewDetails(<?= json_encode($app) ?>)'>
                                      <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="career_enquiries.php?action=delete&id=<?= urlencode($app['id']) ?>" 
                                       class="btn-action-delete" 
                                       title="Remove Record" 
                                       onclick="return confirm('Delete candidate application <?= htmlspecialchars(addslashes($app['name'])) ?>?');">
                                      <i class="fas fa-trash-alt"></i>
                                    </a>
                                  </div>
                                </td>
                              </tr>
                            <?php endforeach; ?>
                          <?php endif; ?>
                        </tbody>
                      </table>
                    </div>

                    <!-- PHP Pagination Controls -->
                    <?php if ($total_pages > 1 || $total_records > 0): ?>
                      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4 pt-3 border-top">
                        <div class="text-muted small">
                          Showing <strong><?= ($total_records > 0) ? ($offset + 1) : 0 ?></strong> to 
                          <strong><?= min($offset + $per_page, $total_records) ?></strong> of 
                          <strong><?= number_format($total_records) ?></strong> candidate applications
                        </div>
                        <?php if ($total_pages > 1): ?>
                          <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm mb-0">
                              <!-- Previous Button -->
                              <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= ($page > 1) ? getPageUrl($page - 1, $search, $status, $position) : '#' ?>" tabindex="-1">
                                  <i class="fas fa-chevron-left me-1"></i> Prev
                                </a>
                              </li>

                              <!-- Page Numbers -->
                              <?php
                                $start_p = max(1, $page - 2);
                                $end_p   = min($total_pages, $page + 2);
                                if ($start_p > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="' . getPageUrl(1, $search, $status, $position) . '">1</a></li>';
                                    if ($start_p > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                for ($p = $start_p; $p <= $end_p; $p++):
                              ?>
                                <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                                  <a class="page-link" href="<?= getPageUrl($p, $search, $status, $position) ?>"><?= $p ?></a>
                                </li>
                              <?php endfor; ?>
                              <?php
                                if ($end_p < $total_pages) {
                                    if ($end_p < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    echo '<li class="page-item"><a class="page-link" href="' . getPageUrl($total_pages, $search, $status, $position) . '">' . $total_pages . '</a></li>';
                                }
                              ?>

                              <!-- Next Button -->
                              <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= ($page < $total_pages) ? getPageUrl($page + 1, $search, $status, $position) : '#' ?>">
                                  Next <i class="fas fa-chevron-right ms-1"></i>
                                </a>
                              </li>
                            </ul>
                          </nav>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>

                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <footer class="footer">
          <div class="container-fluid d-flex justify-content-between">
            <div class="copyright">
              2026, Sufia Healthcare Multispeciality Hospital, Siwan. All Rights Reserved.
            </div>
            <div>
              Designed &amp; Managed by <a href="http://www.digiritual.com" target="_blank">Digi Ritual</a>
            </div>
          </div>
        </footer>
      </div>
    </div>

    <!-- Candidate Detail Modal -->
    <div class="modal fade" id="candidateModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <div>
              <h5 class="modal-title fw-bold" id="mName">Candidate Name</h5>
              <span class="text-primary font-monospace small" id="mId"></span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <div class="p-3 bg-light rounded">
                  <div class="text-muted small text-uppercase fw-bold">Position Applied</div>
                  <strong class="text-dark" id="mPosition"></strong>
                </div>
              </div>
              <div class="col-md-6">
                <div class="p-3 bg-light rounded">
                  <div class="text-muted small text-uppercase fw-bold">Hiring Status</div>
                  <strong class="text-dark" id="mStatus"></strong>
                </div>
              </div>
              <div class="col-md-6">
                <div class="p-3 bg-light rounded">
                  <div class="text-muted small text-uppercase fw-bold">Phone Number</div>
                  <strong class="text-dark" id="mPhone"></strong>
                </div>
              </div>
              <div class="col-md-6">
                <div class="p-3 bg-light rounded">
                  <div class="text-muted small text-uppercase fw-bold">Email Address</div>
                  <strong class="text-dark" id="mEmail"></strong>
                </div>
              </div>
              <div class="col-md-6">
                <div class="p-3 bg-light rounded">
                  <div class="text-muted small text-uppercase fw-bold">Experience</div>
                  <strong class="text-dark" id="mExp"></strong>
                </div>
              </div>
              <div class="col-md-6">
                <div class="p-3 bg-light rounded">
                  <div class="text-muted small text-uppercase fw-bold">City / Location</div>
                  <strong class="text-dark" id="mCity"></strong>
                </div>
              </div>
              <div class="col-12">
                <div class="p-3 bg-light rounded">
                  <div class="text-muted small text-uppercase fw-bold">Academic &amp; Professional Qualifications</div>
                  <strong class="text-dark" id="mQual"></strong>
                </div>
              </div>
              <div class="col-12">
                <div class="p-3 bg-light rounded">
                  <div class="text-muted small text-uppercase fw-bold mb-1">Candidate Cover Letter / Note</div>
                  <div class="text-dark" id="mMsg" style="white-space: pre-wrap; line-height: 1.6;"></div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <a id="mCallBtn" href="#" class="btn btn-success"><i class="fa fa-phone me-1"></i> Call Candidate</a>
            <a id="mEmailBtn" href="#" class="btn btn-info"><i class="fa fa-envelope me-1"></i> Send Email</a>
            <a id="mResumeBtn" href="#" target="_blank" class="btn btn-primary"><i class="fa fa-file-pdf me-1"></i> Download CV</a>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Core JS Files -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>

    <!-- jQuery Scrollbar -->
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

    <!-- Datatables -->
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>

    <!-- kaiadmin JS -->
    <script src="assets/js/kaiadmin.min.js"></script>

    <script>


      function updateStatus(id, newStatus) {
        window.location.href = 'career_enquiries.php?action=status&id=' + encodeURIComponent(id) + '&val=' + encodeURIComponent(newStatus);
      }

      function viewDetails(app) {
        document.getElementById('mName').textContent = app.name || 'Candidate';
        document.getElementById('mId').textContent = 'Ref ID: ' + (app.id || '');
        document.getElementById('mPosition').textContent = app.position || '';
        document.getElementById('mStatus').textContent = app.status || 'New';
        document.getElementById('mPhone').textContent = app.phone || '';
        document.getElementById('mEmail').textContent = app.email || '';
        document.getElementById('mExp').textContent = app.experience || 'Fresher';
        document.getElementById('mCity').textContent = app.city || 'Siwan, Bihar';
        document.getElementById('mQual').textContent = app.qualification || 'Not Specified';
        document.getElementById('mMsg').textContent = app.message || 'No additional cover note provided.';

        document.getElementById('mCallBtn').href = 'tel:' + (app.phone || '');
        document.getElementById('mEmailBtn').href = 'mailto:' + (app.email || '') + '?subject=' + encodeURIComponent('Sufia Healthcare - Career Application ' + (app.id || ''));

        const resumeBtn = document.getElementById('mResumeBtn');
        if (app.resume_file) {
          resumeBtn.href = '../files/resume/' + encodeURIComponent(app.resume_file);
          resumeBtn.style.display = 'inline-block';
        } else {
          resumeBtn.style.display = 'none';
        }

        const myModal = new bootstrap.Modal(document.getElementById('candidateModal'));
        myModal.show();
      }
    </script>
  </body>
</html>
