<?php
session_start();
require_once __DIR__ . '/db.php';

// Security: Enforce admin session
if (!isset($_SESSION['sufia_admin_logged_in']) || $_SESSION['sufia_admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$pdo = getDbConnection();
$current_user = getCurrentAdminUser($pdo);

if (!$current_user) {
    header("Location: login.php?action=logout");
    exit;
}

$feedback = '';
$error = '';

// Handle Add New User
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name          = trim($_POST['name'] ?? '');
    $username      = trim($_POST['username'] ?? '');
    $password      = trim($_POST['password'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $age           = !empty($_POST['age']) ? (int)$_POST['age'] : null;
    $gender        = trim($_POST['gender'] ?? 'Male');
    $role          = trim($_POST['role'] ?? 'Staff');
    $sidebar_color = trim($_POST['sidebar_color'] ?? 'dark');

    if (empty($name) || empty($username) || empty($password)) {
        $error = "Name, Username, and Password are required.";
    } else {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $chk->execute([$username]);
        if ($chk->fetchColumn() > 0) {
            $error = "Username '$username' is already taken. Please choose another.";
        } else {
            $ins = $pdo->prepare("INSERT INTO users (username, password, name, phone, email, age, gender, role, sidebar_color, header_color, footer_color) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'white', 'white')");
            $ins->execute([$username, $password, $name, $phone, $email, $age, $gender, $role, $sidebar_color]);
            $feedback = "New user '$name' (@$username) created successfully.";
        }
    }
}

// Handle Edit User
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $edit_id  = (int)$_POST['user_id'];
    $name     = trim($_POST['name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $age      = !empty($_POST['age']) ? (int)$_POST['age'] : null;
    $gender   = trim($_POST['gender'] ?? 'Male');
    $role     = trim($_POST['role'] ?? 'Staff');
    $new_pass = trim($_POST['password'] ?? '');

    if (empty($name)) {
        $error = "Name cannot be empty.";
    } else {
        if (!empty($new_pass)) {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, email = ?, age = ?, gender = ?, role = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $email, $age, $gender, $role, $new_pass, $edit_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, email = ?, age = ?, gender = ?, role = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $email, $age, $gender, $role, $edit_id]);
        }
        $feedback = "User details updated successfully.";
    }
}

// Handle Delete User
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];

    if ($del_id === (int)$current_user['id']) {
        $error = "You cannot delete your own active administrator account.";
    } else {
        $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $del->execute([$del_id]);
        $feedback = "User removed successfully.";
    }
}

// Handle Export CSV
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Sufia_Users_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Full Name', 'Username', 'Role', 'Phone', 'Email', 'Age', 'Gender', 'Sidebar Theme', 'Created At']);

    $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['id'] ?? '',
            $row['name'] ?? '',
            $row['username'] ?? '',
            $row['role'] ?? '',
            $row['phone'] ?? '',
            $row['email'] ?? '',
            $row['age'] ?? '',
            $row['gender'] ?? '',
            $row['sidebar_color'] ?? '',
            $row['created_at'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}

// Active theme colors
$active_sidebar = $current_user['sidebar_color'] ?: 'dark';
$active_header  = $current_user['header_color'] ?: 'white';
$active_footer  = $current_user['footer_color'] ?: 'white';

$all_users     = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
$total_users   = count($all_users);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Users Directory &amp; Staff Accounts - Sufia Healthcare</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/kaiadmin/favicon.ico" type="image/x-icon" />

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
      .btn-action-edit, .btn-action-view {
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
      .btn-action-edit:hover, .btn-action-view:hover {
        background: #2563eb;
        color: #ffffff !important;
        border-color: #2563eb;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
      }
      .btn-action-edit i, .btn-action-view i, .btn-action-delete i {
        transition: transform 0.2s ease;
      }
      .btn-action-edit:hover i, .btn-action-view:hover i, .btn-action-delete:hover i {
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
              <li class="nav-item">
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
              <li class="nav-item active">
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
            <div class="logo-header" data-background-color="<?= htmlspecialchars($active_sidebar) ?>">
              <a href="index.php" class="logo">
                <img src="assets/img/logo-sufia.png" alt="navbar brand" class="navbar-brand" height="30" />
              </a>
              <div class="nav-toggle">
                <button class="btn btn-toggle toggle-sidebar"><i class="gg-menu-right"></i></button>
                <button class="btn btn-toggle sidenav-toggler"><i class="gg-menu-left"></i></button>
              </div>
              <button class="topbar-toggler more"><i class="gg-more-vertical-alt"></i></button>
            </div>
          </div>
          <!-- Navbar Header -->
          <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
            <div class="container-fluid">
              <nav class="navbar navbar-header-left navbar-expand-lg navbar-form nav-search p-0 d-none d-lg-flex">
                <div class="input-group">
                  <div class="input-group-prepend">
                    <button type="button" class="btn btn-search pe-1"><i class="fa fa-search search-icon"></i></button>
                  </div>
                  <input type="text" placeholder="Search staff users..." class="form-control" onkeyup="if(window.table) window.table.search(this.value).draw();" />
                </div>
              </nav>

              <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                <li class="nav-item">
                  <span class="badge badge-success px-3 py-2">🟢 Active Session</span>
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
                            <img src="assets/img/profile.jpg" alt="image profile" class="avatar-img rounded" />
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
                        <a class="dropdown-item" href="profile.php">Theme Colors</a>
                        <a class="dropdown-item" href="users.php">Users List</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="login.php?action=logout">Logout</a>
                      </li>
                    </div>
                  </ul>
                </li>
              </ul>
            </div>
          </nav>
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
                  <li class="nav-item"><a href="users.php">Users List</a></li>
                </ul>
              </div>
              <div class="d-flex align-items-center gap-2">
                <a href="users.php?action=export_csv" class="btn btn-success btn-round btn-sm">
                  <i class="fa fa-download me-1"></i> Export to CSV
                </a>
                <button type="button" class="btn btn-primary btn-round btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                  <i class="fas fa-user-plus me-1"></i> Add New User
                </button>
              </div>
            </div>

            <!-- Feedback Alerts -->
            <?php if (!empty($feedback)): ?>
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>✓ Success!</strong> <?= htmlspecialchars($feedback) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>

            <!-- Users List Table Card -->
            <div class="row">
              <div class="col-md-12">
                <div class="card card-round">
                  <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                      <h4 class="card-title mb-0">Registered System Users</h4>
                      <span class="badge badge-primary"><?= $total_users ?> Users</span>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive">
                      <table id="users-datatables" class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                          <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Contact Details</th>
                            <th>Created Date</th>
                            <th class="text-center" style="width: 110px;">Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($all_users as $u): ?>
                            <?php $u_json = htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8'); ?>
                            <tr>
                              <td class="fw-bold text-muted">#<?= (int)$u['id'] ?></td>
                              <td>
                                <div class="d-flex align-items-center">
                                  <div class="avatar-sm me-2">
                                    <span class="avatar-title rounded-circle bg-primary text-white fw-bold">
                                      <?= strtoupper(substr($u['name'] ?: 'U', 0, 1)) ?>
                                    </span>
                                  </div>
                                  <span class="fw-bold text-dark"><?= htmlspecialchars($u['name']) ?></span>
                                </div>
                              </td>
                              <td>
                                <span class="text-muted">@<?= htmlspecialchars($u['username']) ?></span>
                              </td>
                              <td>
                                <span class="badge badge-<?= ($u['role'] === 'Administrator' || $u['role'] === 'Admin') ? 'primary' : 'secondary' ?>">
                                  <?= htmlspecialchars($u['role']) ?>
                                </span>
                              </td>
                              <td>
                                <div><i class="fas fa-phone-alt me-1 text-muted small"></i> <?= htmlspecialchars($u['phone'] ?: '—') ?></div>
                                <div class="text-muted small"><i class="fas fa-envelope me-1 text-muted small"></i> <?= htmlspecialchars($u['email'] ?: '—') ?></div>
                              </td>
                              <td>
                                <span class="text-muted small"><?= date('d M Y', strtotime($u['created_at'])) ?></span>
                              </td>
                              <td class="text-center">
                                <div class="d-inline-flex gap-2 align-items-center justify-content-center">
                                  <button type="button" class="btn-action-edit" title="Edit User" onclick="editUser(<?= $u_json ?>)">
                                    <i class="fas fa-pencil-alt"></i>
                                  </button>
                                  <?php if ((int)$u['id'] !== (int)$current_user['id']): ?>
                                    <a href="users.php?action=delete&id=<?= (int)$u['id'] ?>" class="btn-action-delete" title="Delete User" onclick="return confirm('Are you sure you want to delete user <?= addslashes($u['name']) ?>?');">
                                      <i class="fas fa-trash-alt"></i>
                                    </a>
                                  <?php endif; ?>
                                </div>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
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

    <!-- Modal: Add New User -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <form action="users.php" method="POST">
            <div class="modal-header bg-primary text-white">
              <h5 class="modal-title" id="addUserModalLabel"><i class="fas fa-user-plus me-2"></i> Add New Staff / Administrator</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-muted">FULL NAME *</label>
                  <input type="text" name="name" class="form-control" required placeholder="Enter Your Full Name" />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-muted">USERNAME (LOGIN ID) *</label>
                  <input type="text" name="username" class="form-control" required placeholder="Enter Username" autocomplete="off" />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-muted">PASSWORD *</label>
                  <input type="password" name="password" class="form-control" required placeholder="••••••••" autocomplete="new-password" />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-muted">USER ROLE</label>
                  <select name="role" class="form-select">
                    <option value="Admin">Administrator</option>
                    <option value="Manager">Hospital Manager</option>
                    <option value="Doctor">Doctor</option>
                    <option value="Staff" selected>Staff / Reception</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-muted">PHONE NUMBER</label>
                  <input type="text" name="phone" class="form-control" placeholder="Enter Your Phone Number" />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-muted">EMAIL ADDRESS</label>
                  <input type="email" name="email" class="form-control" placeholder="Enter Your Email Address" />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-muted">AGE</label>
                  <input type="number" name="age" min="18" max="100" class="form-control" placeholder="Enter Your Age" />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-muted">GENDER</label>
                  <select name="gender" class="form-select">
                    <option value="Male" selected>Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label fw-bold small text-muted">INITIAL SIDEBAR THEME</label>
                  <select name="sidebar_color" class="form-select">
                    <option value="dark" selected>Midnight Black (Classic)</option>
                    <option value="white">Clean White (Light)</option>
                    <option value="green">Hospital Green (Sufia Medical)</option>
                    <option value="teal">Healthcare Teal (Cyan)</option>
                    <option value="blue">Royal Blue</option>
                    <option value="purple">Medical Indigo</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="modal-footer bg-light">
              <button type="button" class="btn btn-secondary btn-round" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" name="add_user" class="btn btn-primary btn-round">
                <i class="fas fa-check me-1"></i> Create User Account
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal: Edit User -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <form action="users.php" method="POST">
            <input type="hidden" name="user_id" id="eUserId" />
            <div class="modal-header bg-info text-white">
              <h5 class="modal-title" id="editUserModalLabel"><i class="fas fa-user-edit me-2"></i> Edit User Information</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-muted">FULL NAME *</label>
                  <input type="text" name="name" id="eName" class="form-control" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-muted">USERNAME</label>
                  <input type="text" id="eUsername" class="form-control bg-light" readonly disabled />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-muted">PASSWORD (LEAVE BLANK TO KEEP UNCHANGED)</label>
                  <input type="password" name="password" class="form-control" placeholder="••••••••" autocomplete="new-password" />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-muted">USER ROLE</label>
                  <select name="role" id="eRole" class="form-select">
                    <option value="Admin">Administrator</option>
                    <option value="Manager">Hospital Manager</option>
                    <option value="Doctor">Doctor</option>
                    <option value="Staff">Staff / Reception</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-muted">PHONE NUMBER</label>
                  <input type="text" name="phone" id="ePhone" class="form-control" />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-muted">EMAIL ADDRESS</label>
                  <input type="email" name="email" id="eEmail" class="form-control" />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-muted">AGE</label>
                  <input type="number" name="age" id="eAge" min="18" max="100" class="form-control" />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-muted">GENDER</label>
                  <select name="gender" id="eGender" class="form-select">
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="modal-footer bg-light">
              <button type="button" class="btn btn-secondary btn-round" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" name="edit_user" class="btn btn-info btn-round text-white">
                <i class="fas fa-save me-1"></i> Save Changes
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Core JS Files (Untouched KaiAdmin Assets) -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>

    <script>
      $(document).ready(function () {
        window.table = $("#users-datatables").DataTable({
          pageLength: 10,
          order: [[0, "desc"]],
          columnDefs: [{ orderable: false, targets: [6] }],
          language: {
            search: "Filter users:",
            lengthMenu: "Show _MENU_ users per page"
          }
        });
      });

      function editUser(user) {
        document.getElementById('eUserId').value = user.id;
        document.getElementById('eName').value = user.name || '';
        document.getElementById('eUsername').value = '@' + (user.username || '');
        document.getElementById('eRole').value = user.role || 'Staff';
        document.getElementById('ePhone').value = user.phone || '';
        document.getElementById('eEmail').value = user.email || '';
        document.getElementById('eAge').value = user.age || '';
        document.getElementById('eGender').value = user.gender || 'Male';

        const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
        modal.show();
      }
    </script>
  </body>
</html>
