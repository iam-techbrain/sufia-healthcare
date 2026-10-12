<?php
session_start();
require_once __DIR__ . '/db.php';

// Security: Enforce admin session
if (!isset($_SESSION['sufia_admin_logged_in']) || $_SESSION['sufia_admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$pdo = getDbConnection();
$feedback = '';

// Handle Status Change
if (isset($_GET['action']) && $_GET['action'] === 'status' && isset($_GET['id']) && isset($_GET['val'])) {
    $target_id  = (int)$_GET['id'];
    $new_status = trim($_GET['val']);
    $valid_statuses = ['New', 'In Progress', 'Responded', 'Resolved'];

    if (in_array($new_status, $valid_statuses)) {
        $stmt = $pdo->prepare("UPDATE contact_inquiries SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $target_id]);
        header("Location: contact_enquiries.php?msg=status_updated");
        exit;
    }
}

// Handle Delete Inquiry
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];

    $del_stmt = $pdo->prepare("DELETE FROM contact_inquiries WHERE id = ?");
    $del_stmt->execute([$del_id]);

    header("Location: contact_enquiries.php?msg=deleted");
    exit;
}

// --- PHP Search, Filter & Pagination Parameters ---
$search     = trim($_GET['search'] ?? '');
$status     = trim($_GET['status'] ?? '');
$department = trim($_GET['department'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 10;
$offset     = ($page - 1) * $per_page;

$where_clauses = [];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR message LIKE ? OR id = ?)";
    $term = "%$search%";
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

if ($department !== '' && $department !== 'all') {
    $where_clauses[] = "department = ?";
    $params[] = $department;
}

$where_sql = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Handle Export CSV (supports active filters, Excel compatible)
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Sufia_Patient_Enquiries_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // Add UTF-8 BOM for Microsoft Excel compatibility
    fputs($output, "\xEF\xBB\xBF");

    fputcsv($output, ['ID', 'Patient Name', 'Phone', 'Email', 'Department', 'Status', 'Submitted At', 'Query / Message']);

    $stmt = $pdo->prepare("SELECT * FROM contact_inquiries $where_sql ORDER BY id DESC");
    $stmt->execute($params);
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['id'] ?? '',
            $row['name'] ?? '',
            $row['phone'] ?? '',
            $row['email'] ?? '',
            $row['department'] ?? '',
            $row['status'] ?? '',
            $row['created_at'] ?? '',
            $row['message'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}

// Flash Messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'status_updated') $feedback = "Enquiry status updated successfully.";
    if ($_GET['msg'] === 'deleted') $feedback = "Enquiry record removed successfully.";
}

// Stats Calculation (All-time metrics for KPI cards)
$total_inq     = (int)$pdo->query("SELECT COUNT(*) FROM contact_inquiries")->fetchColumn();
$new_inq       = (int)$pdo->query("SELECT COUNT(*) FROM contact_inquiries WHERE status = 'New'")->fetchColumn();
$in_prog_inq   = (int)$pdo->query("SELECT COUNT(*) FROM contact_inquiries WHERE status = 'In Progress'")->fetchColumn();
$resolved_inq  = (int)$pdo->query("SELECT COUNT(*) FROM contact_inquiries WHERE status IN ('Responded', 'Resolved')")->fetchColumn();
$today_inq     = (int)$pdo->query("SELECT COUNT(*) FROM contact_inquiries WHERE date(created_at) = date('now', 'localtime')")->fetchColumn();

// Count total matching records for current filter
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM contact_inquiries $where_sql");
$count_stmt->execute($params);
$total_records = (int)$count_stmt->fetchColumn();

// Calculate total pages
$total_pages = max(1, (int)ceil($total_records / $per_page));
if ($page > $total_pages && $total_records > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}

// Fetch paginated inquiries strictly ordered by ID DESC (newest first)
$data_stmt = $pdo->prepare("SELECT * FROM contact_inquiries $where_sql ORDER BY id DESC LIMIT $per_page OFFSET $offset");
$data_stmt->execute($params);
$all_inquiries = $data_stmt->fetchAll();

// Get unique departments list for filter dropdown
$distinct_departments = $pdo->query("SELECT DISTINCT department FROM contact_inquiries WHERE department IS NOT NULL AND department != '' ORDER BY department ASC")->fetchAll(PDO::FETCH_COLUMN);

// Pagination URL builder
function getContactPageUrl($p, $search, $status, $department) {
    $params = ['page' => $p];
    if ($search !== '') $params['search'] = $search;
    if ($status !== '' && $status !== 'all') $params['status'] = $status;
    if ($department !== '' && $department !== 'all') $params['department'] = $department;
    return 'contact_enquiries.php?' . http_build_query($params);
}

$current_user   = getCurrentAdminUser($pdo);
$active_sidebar = $current_user['sidebar_color'] ?? 'dark';
$active_header  = $current_user['header_color'] ?? 'white';
$active_footer  = $current_user['footer_color'] ?? 'white';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Contact &amp; Patient Enquiries - Sufia Healthcare Admin</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <!--<link rel="icon" href="assets/img/kaiadmin/favicon.ico" type="image/x-icon" />-->

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

    <!-- CSS Files (Untouched KaiAdmin Assets) -->
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
                alt="Sufia Healthcare"
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
              <li class="nav-item active">
                <a href="contact_enquiries.php">
                  <i class="fas fa-envelope"></i>
                  <p>Contact Enquiries</p>
                </a>
              </li>
              <li class="nav-item">
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
                <form method="GET" action="contact_enquiries.php" class="d-flex align-items-center">
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
                      placeholder="Search contact enquiries..."
                      class="form-control"
                    />
                  </div>
                </form>
              </nav>

              <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                <li class="nav-item">
                  <span class="badge badge-success px-3 py-2">🟢 Hospital Support Active</span>
                </li>

                <li class="nav-item topbar-user dropdown hidden-caret ms-2">
                  <a
                    class="dropdown-toggle profile-pic"
                    data-bs-toggle="dropdown"
                    href="#"
                    aria-expanded="false"
                  >
                    <div class="avatar-sm">
                      <img
                        src="assets/img/profile.jpg"
                        alt="admin"
                        class="avatar-img rounded-circle"
                      />
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
                            <img
                              src="assets/img/profile.jpg"
                              alt="image profile"
                              class="avatar-img rounded"
                            />
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
                        <a class="dropdown-item" href="career_enquiries.php"><i class="fas fa-user-md me-2"></i> Career Applications</a>
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
                  <li class="nav-item"><a href="contact_enquiries.php">Contact Enquiries</a></li>
                </ul>
              </div>
              <div class="d-flex align-items-center gap-2">
                <a href="contact_enquiries.php?action=export_csv" class="btn btn-success btn-round btn-sm">
                  <i class="fa fa-download me-1"></i> Export to CSV
                </a>
              </div>
            </div>

            <!-- Feedback Alert -->
            <?php if (!empty($feedback)): ?>
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> <?= htmlspecialchars($feedback) ?>
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
                          <i class="fas fa-envelope"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">Total Inquiries</p>
                          <h4 class="card-title"><?= number_format($total_inq) ?></h4>
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
                        <div class="icon-big text-center icon-danger bubble-shadow-small">
                          <i class="fas fa-bell"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">New / Pending</p>
                          <h4 class="card-title"><?= number_format($new_inq) ?></h4>
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
                          <i class="fas fa-phone-volume"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">In Follow-Up</p>
                          <h4 class="card-title"><?= number_format($in_prog_inq) ?></h4>
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
                          <i class="fas fa-check-circle"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">Resolved / Closed</p>
                          <h4 class="card-title"><?= number_format($resolved_inq) ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Datatable Card with default 10 entries per page -->
            <div class="row">
              <div class="col-md-12">
                <div class="card card-round">
                  

                  <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                      <div>
                        <h4 class="card-title mb-1">Patient Contact Inquiries</h4>
                        <small class="text-muted">Page <?= $page ?> of <?= $total_pages ?> · Total <?= number_format($total_records) ?> Enquiries</small>
                      </div>
                      <div class="d-flex gap-2">
                        <a href="contact_enquiries.php?action=export_csv<?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['action' => ''])) : '' ?>" class="btn btn-sm btn-outline-success btn-round">
                          <i class="fa fa-download me-1"></i> Export Filtered CSV
                        </a>
                      </div>
                    </div>
                  </div>
                  <div class="card-body">
                    <!-- PHP Filter Form -->
                    <form method="GET" action="contact_enquiries.php" class="row g-2 mb-4 align-items-end p-3 rounded" style="background: #f8fafc; border: 1px solid #ebedf2;">
                      <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1"><i class="fas fa-search me-1"></i> Search Enquiries</label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search patient name, phone, email, #ID, query..." value="<?= htmlspecialchars($search) ?>" />
                      </div>
                      <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1"><i class="fas fa-filter me-1"></i> Status</label>
                        <select name="status" class="form-select form-select-sm">
                          <option value="all" <?= ($status === '' || $status === 'all') ? 'selected' : '' ?>>All Statuses</option>
                          <option value="New" <?= ($status === 'New') ? 'selected' : '' ?>>New</option>
                          <option value="In Progress" <?= ($status === 'In Progress') ? 'selected' : '' ?>>In Progress</option>
                          <option value="Responded" <?= ($status === 'Responded') ? 'selected' : '' ?>>Responded</option>
                          <option value="Resolved" <?= ($status === 'Resolved') ? 'selected' : '' ?>>Resolved</option>
                        </select>
                      </div>
                      <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1"><i class="fas fa-hospital me-1"></i> Department</label>
                        <select name="department" class="form-select form-select-sm">
                          <option value="all" <?= ($department === '' || $department === 'all') ? 'selected' : '' ?>>All Departments</option>
                          <?php foreach ($distinct_departments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept) ?>" <?= ($department === $dept) ? 'selected' : '' ?>>
                              <?= htmlspecialchars($dept) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary flex-fill">
                          <i class="fas fa-filter me-1"></i> Filter
                        </button>
                        <a href="contact_enquiries.php" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                          <i class="fas fa-redo"></i>
                        </a>
                      </div>
                    </form>

                    <div class="table-responsive">
                      <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                          <tr>
                            <th style="min-width: 130px;">Ref ID &amp; Date</th>
                            <th style="min-width: 220px;">Patient / Contact</th>
                            <th style="min-width: 110px;">Department</th>
                            <th style="min-width: 250px;">Query Summary</th>
                            <th style="min-width: 140px;">Status</th>
                            <th class="text-center" style="width: 90px;">Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php if (empty($all_inquiries)): ?>
                            <tr>
                              <td colspan="6" class="text-center py-4 text-muted">No contact inquiries recorded yet.</td>
                            </tr>
                          <?php else: ?>
                            <?php foreach ($all_inquiries as $inq): ?>
                              <?php
                                $badge_class = 'badge-danger';
                                if ($inq['status'] === 'In Progress') $badge_class = 'badge-warning';
                                if ($inq['status'] === 'Responded') $badge_class = 'badge-info';
                                if ($inq['status'] === 'Resolved') $badge_class = 'badge-success';

                                $clean_phone = preg_replace('/[^0-9]/', '', $inq['phone']);
                                if (strlen($clean_phone) === 10) {
                                    $wa_phone = '91' . $clean_phone;
                                } else {
                                    $wa_phone = $clean_phone;
                                }

                                $inq_json = htmlspecialchars(json_encode($inq), ENT_QUOTES, 'UTF-8');
                              ?>
                              <tr>
                                <td data-order="<?= (int)$inq['id'] ?>">
                                  <span class="fw-bold text-primary fs-6">#ENQ-<?= str_pad((string)$inq['id'], 4, '0', STR_PAD_LEFT) ?></span>
                                  <div class="text-muted mt-1 small" style="white-space: nowrap; font-size: 0.76rem;">
                                    <i class="far fa-calendar-alt text-primary me-1"></i><?= date('d M Y', strtotime($inq['created_at'])) ?>
                                  </div>
                                  <div class="text-muted" style="font-size: 0.70rem;">
                                    <i class="far fa-clock text-secondary me-1"></i><?= date('h:i A', strtotime($inq['created_at'])) ?>
                                  </div>
                                </td>
                                <td>
                                  <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($inq['name']) ?></div>
                                  <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                    <span class="text-muted small fw-semibold me-1">
                                      <i class="fas fa-phone-alt me-1 text-secondary" style="font-size: 11px;"></i><?= htmlspecialchars($inq['phone']) ?>
                                    </span>
                                    <a href="tel:<?= urlencode($inq['phone']) ?>" class="btn btn-icon btn-round btn-xs btn-outline-success" title="Direct Phone Call" style="width: 24px; height: 24px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 10px;">
                                      <i class="fas fa-phone"></i>
                                    </a>
                                    <a href="https://wa.me/<?= urlencode($wa_phone) ?>?text=<?= urlencode('Hello ' . $inq['name'] . ', this is Sufia Healthcare Siwan regarding your medical enquiry.') ?>" target="_blank" class="btn btn-icon btn-round btn-xs btn-outline-success" title="WhatsApp Chat" style="width: 24px; height: 24px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 11px;">
                                      <i class="fab fa-whatsapp"></i>
                                    </a>
                                    <?php if (!empty($inq['email'])): ?>
                                      <a href="mailto:<?= urlencode($inq['email']) ?>?subject=<?= urlencode('Sufia Healthcare - Response to Medical Enquiry #' . $inq['id']) ?>" class="btn btn-icon btn-round btn-xs btn-outline-primary" title="Send Email" style="width: 24px; height: 24px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 10px;">
                                        <i class="fas fa-envelope"></i>
                                      </a>
                                    <?php endif; ?>
                                  </div>
                                  <?php if (!empty($inq['email'])): ?>
                                    <div class="text-muted small mt-1" style="font-size: 0.78rem;">
                                      <i class="fas fa-at me-1 text-secondary" style="font-size: 10px;"></i><?= htmlspecialchars($inq['email']) ?>
                                    </div>
                                  <?php endif; ?>
                                </td>
                                <td>
                                  <span class="badge badge-secondary"><?= htmlspecialchars($inq['department'] ?: 'General') ?></span>
                                </td>
                                <td>
                                  <span title="<?= htmlspecialchars($inq['message']) ?>">
                                    <?= htmlspecialchars(mb_strimwidth($inq['message'] ?? '', 0, 60, '...')) ?>
                                  </span>
                                </td>
                                <td>
                                  <div class="d-flex flex-column gap-1" style="max-width: 135px;">
                                    <div><span class="badge <?= $badge_class ?>"><?= htmlspecialchars($inq['status']) ?></span></div>
                                    <select class="form-select form-select-sm" style="font-size: 0.78rem; padding: 3px 8px;" onchange="updateStatus(<?= (int)$inq['id'] ?>, this.value)">
                                      <option value="New" <?= $inq['status'] === 'New' ? 'selected' : '' ?>>New</option>
                                      <option value="In Progress" <?= $inq['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                      <option value="Responded" <?= $inq['status'] === 'Responded' ? 'selected' : '' ?>>Responded</option>
                                      <option value="Resolved" <?= $inq['status'] === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                    </select>
                                  </div>
                                </td>
                                <td class="text-center">
                                  <div class="d-inline-flex gap-2 align-items-center justify-content-center">
                                    <button
                                      type="button"
                                      class="btn-action-view"
                                      title="View Enquiry Details"
                                      onclick="viewDetails(<?= $inq_json ?>)"
                                    >
                                      <i class="fas fa-eye"></i>
                                    </button>
                                    <a
                                      href="contact_enquiries.php?action=delete&id=<?= (int)$inq['id'] ?>"
                                      class="btn-action-delete"
                                      title="Delete"
                                      onclick="return confirm('Are you sure you want to delete inquiry #<?= (int)$inq['id'] ?> from <?= addslashes($inq['name']) ?>?');"
                                    >
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
                          Showing <strong><?= ($total_records === 0) ? 0 : ($offset + 1) ?></strong> to <strong><?= min($offset + $per_page, $total_records) ?></strong> of <strong><?= number_format($total_records) ?></strong> enquiries
                        </div>
                        <?php if ($total_pages > 1): ?>
                          <ul class="pagination pagination-sm mb-0">
                            <!-- Prev -->
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                              <a class="page-link" href="<?= getContactPageUrl($page - 1, $search, $status, $department) ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                              </a>
                            </li>

                            <?php
                              $start_p = max(1, $page - 2);
                              $end_p   = min($total_pages, $page + 2);
                              if ($start_p > 1) {
                                  echo '<li class="page-item"><a class="page-link" href="' . getContactPageUrl(1, $search, $status, $department) . '">1</a></li>';
                                  if ($start_p > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                              }
                              for ($i = $start_p; $i <= $end_p; $i++) {
                                  $active = ($i === $page) ? 'active' : '';
                                  echo '<li class="page-item ' . $active . '"><a class="page-link" href="' . getContactPageUrl($i, $search, $status, $department) . '">' . $i . '</a></li>';
                              }
                              if ($end_p < $total_pages) {
                                  if ($end_p < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                  echo '<li class="page-item"><a class="page-link" href="' . getContactPageUrl($total_pages, $search, $status, $department) . '">' . $total_pages . '</a></li>';
                              }
                            ?>

                            <!-- Next -->
                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                              <a class="page-link" href="<?= getContactPageUrl($page + 1, $search, $status, $department) ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                              </a>
                            </li>
                          </ul>
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
            <nav class="pull-left">
              <ul class="nav">
                <li class="nav-item">
                  <a class="nav-link" href="../index.html" target="_blank">
                    Sufia Healthcare Siwan
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="../contact.html" target="_blank">
                    Help &amp; Emergency
                  </a>
                </li>
              </ul>
            </nav>
            <div class="copyright">
              © <?= date('Y') ?> Sufia Healthcare. All rights reserved.
            </div>
          </div>
        </footer>
      </div>
    </div>

    <!-- Patient Enquiry Details Modal -->
    <div class="modal fade" id="enquiryModal" tabindex="-1" aria-labelledby="enquiryModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="enquiryModalLabel">
              <i class="fas fa-notes-medical me-2"></i> Patient Medical Enquiry Details
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <div class="row g-3">
              <div class="col-md-6">
                <div class="p-3 bg-light rounded border">
                  <label class="text-muted small text-uppercase fw-bold">Patient Name</label>
                  <h4 class="fw-bold text-dark mb-1" id="mName">-</h4>
                  <span class="badge badge-info" id="mRefId">Ref ID</span>
                </div>
              </div>
              <div class="col-md-6">
                <div class="p-3 bg-light rounded border">
                  <label class="text-muted small text-uppercase fw-bold">Department / Query Category</label>
                  <h5 class="fw-bold text-primary mb-1" id="mDept">-</h5>
                  <span class="badge badge-dark" id="mDate">-</span>
                </div>
              </div>
              <div class="col-md-6">
                <div class="p-3 bg-light rounded border">
                  <label class="text-muted small text-uppercase fw-bold">Phone Number</label>
                  <p class="fw-bold text-dark mb-0 fs-6" id="mPhone">-</p>
                </div>
              </div>
              <div class="col-md-6">
                <div class="p-3 bg-light rounded border">
                  <label class="text-muted small text-uppercase fw-bold">Email Address</label>
                  <p class="fw-bold text-dark mb-0 fs-6" id="mEmail">-</p>
                </div>
              </div>
              <div class="col-12">
                <div class="p-3 bg-light rounded border">
                  <label class="text-muted small text-uppercase fw-bold">Current Resolution Status</label>
                  <div>
                    <span class="badge badge-secondary fs-6" id="mStatus">New</span>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <div class="p-3 bg-light rounded border">
                  <label class="text-muted small text-uppercase fw-bold">Full Message / Medical Query</label>
                  <p class="text-dark mb-0 mt-1" id="mMessage" style="white-space: pre-wrap; line-height: 1.6;"></p>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer bg-light d-flex justify-content-between">
            <button type="button" class="btn btn-secondary btn-round" data-bs-dismiss="modal">Close</button>
            <div class="d-flex gap-2">
              <a href="#" id="mCallBtn" class="btn btn-success btn-round">
                <i class="fas fa-phone me-1"></i> Call Patient
              </a>
              <a href="#" id="mWhatsAppBtn" target="_blank" class="btn btn-success btn-round">
                <i class="fab fa-whatsapp me-1"></i> WhatsApp
              </a>
              <a href="#" id="mEmailBtn" class="btn btn-info btn-round">
                <i class="fas fa-envelope me-1"></i> Send Email
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Core JS Files (Untouched KaiAdmin Assets) -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>

    <!-- jQuery Scrollbar -->
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

    <!-- Datatables -->
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>

    <!-- KaiAdmin JS -->
    <script src="assets/js/kaiadmin.min.js"></script>

    <script>
      function updateStatus(id, newStatus) {
        window.location.href = 'contact_enquiries.php?action=status&id=' + encodeURIComponent(id) + '&val=' + encodeURIComponent(newStatus);
      }

      function viewDetails(inq) {
        document.getElementById('mName').textContent = inq.name || 'Patient';
        document.getElementById('mRefId').textContent = 'Ref ID: #ENQ-' + String(inq.id || '').padStart(4, '0');
        document.getElementById('mDept').textContent = inq.department || 'General Medicine';
        document.getElementById('mStatus').textContent = inq.status || 'New';
        document.getElementById('mPhone').textContent = inq.phone || 'N/A';
        document.getElementById('mEmail').textContent = inq.email || 'None Provided';
        document.getElementById('mDate').textContent = inq.created_at || '';
        document.getElementById('mMessage').textContent = inq.message || 'No description provided.';

        document.getElementById('mCallBtn').href = 'tel:' + (inq.phone || '');

        let cleanPhone = (inq.phone || '').replace(/[^0-9]/g, '');
        if (cleanPhone.length === 10) cleanPhone = '91' + cleanPhone;
        document.getElementById('mWhatsAppBtn').href = 'https://wa.me/' + cleanPhone + '?text=' + encodeURIComponent('Hello ' + (inq.name || '') + ', this is Sufia Healthcare regarding your medical enquiry.');

        if (inq.email) {
          document.getElementById('mEmailBtn').href = 'mailto:' + encodeURIComponent(inq.email) + '?subject=' + encodeURIComponent('Sufia Healthcare - Response to Medical Enquiry #' + inq.id);
          document.getElementById('mEmailBtn').style.display = 'inline-block';
        } else {
          document.getElementById('mEmailBtn').style.display = 'none';
        }

        const myModal = new bootstrap.Modal(document.getElementById('enquiryModal'));
        myModal.show();
      }
    </script>
  </body>
</html>
