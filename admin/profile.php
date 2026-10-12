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

// Handle Profile & Theme Update
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name     = trim($_POST['name'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $age      = !empty($_POST['age']) ? (int)$_POST['age'] : null;
        $gender   = trim($_POST['gender'] ?? 'Male');
        $new_pass = trim($_POST['new_password'] ?? '');

        if (empty($name)) {
            $error = "Name cannot be empty.";
        } else {
            if (!empty($new_pass)) {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, email = ?, age = ?, gender = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $phone, $email, $age, $gender, $new_pass, $current_user['id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, email = ?, age = ?, gender = ? WHERE id = ?");
                $stmt->execute([$name, $phone, $email, $age, $gender, $current_user['id']]);
            }
            $feedback = "Your personal profile details have been successfully updated.";
            $current_user = getCurrentAdminUser($pdo);
        }
    }

    if (isset($_POST['update_theme'])) {
        $sidebar_color = trim($_POST['sidebar_color'] ?? 'dark');
        $header_color  = trim($_POST['header_color'] ?? 'white');
        $footer_color  = trim($_POST['footer_color'] ?? 'white');

        $allowed_sidebar = ['dark', 'white', 'green', 'teal', 'blue', 'purple', 'dark2'];
        $allowed_header  = ['white', 'dark', 'green', 'teal', 'blue', 'purple'];
        $allowed_footer  = ['white', 'dark', 'green', 'teal', 'blue'];

        if (!in_array($sidebar_color, $allowed_sidebar)) $sidebar_color = 'dark';
        if (!in_array($header_color, $allowed_header)) $header_color = 'white';
        if (!in_array($footer_color, $allowed_footer)) $footer_color = 'white';

        $stmt = $pdo->prepare("UPDATE users SET sidebar_color = ?, header_color = ?, footer_color = ? WHERE id = ?");
        $stmt->execute([$sidebar_color, $header_color, $footer_color, $current_user['id']]);

        $_SESSION['sufia_sidebar_color'] = $sidebar_color;
        $_SESSION['sufia_header_color']  = $header_color;
        $_SESSION['sufia_footer_color']  = $footer_color;

        $feedback = "Theme colors updated successfully! New colors are now active across your admin portal.";
        $current_user = getCurrentAdminUser($pdo);
    }
}

// Active colors
$active_sidebar = $current_user['sidebar_color'] ?: 'dark';
$active_header  = $current_user['header_color'] ?: 'white';
$active_footer  = $current_user['footer_color'] ?: 'white';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>My Profile &amp; Theme Customizer - Sufia Healthcare</title>
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
      .color-card-option {
        cursor: pointer;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 12px;
        position: relative;
        background: #ffffff;
      }
      .color-card-option:hover {
        border-color: #0eaba3;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
      }
      .color-card-option input[type="radio"]:checked + .color-card-inner {
        font-weight: 700;
      }
      .color-card-option.selected {
        border-color: #0eaba3;
        background: #f0fdfa;
      }
      .color-swatch-circle {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        border: 2px solid #ffffff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        display: inline-block;
        flex-shrink: 0;
      }
      .profile-stat-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px;
        text-align: center;
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
              <li class="nav-item active">
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
            <div class="logo-header" data-background-color="<?= htmlspecialchars($active_sidebar) ?>">
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
                <div class="input-group">
                  <div class="input-group-prepend">
                    <button type="button" class="btn btn-search pe-1">
                      <i class="fa fa-search search-icon"></i>
                    </button>
                  </div>
                  <input
                    type="text"
                    placeholder="Search anything..."
                    class="form-control"
                  />
                </div>
              </nav>

              <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                <li class="nav-item">
                  <span class="badge badge-success px-3 py-2">🟢 Active Session</span>
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
                  <li class="nav-item"><span>My Profile &amp; Theme Settings</span></li>
                </ul>
              </div>
              <div class="d-flex align-items-center gap-2">
                <a href="users.php" class="btn btn-sm btn-outline-primary btn-round">
                  <i class="fas fa-users me-1"></i> View All Users
                </a>
                <a href="index.php" class="btn btn-sm btn-outline-secondary btn-round">
                  <i class="fas fa-home me-1"></i> Dashboard
                </a>
              </div>
            </div>

            <!-- Alerts -->
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

            <div class="row">
              <!-- Left Column: Profile Card & Quick Stats -->
              <div class="col-md-4">
                <div class="card card-profile card-round">
                  <div class="card-header" style="background: linear-gradient(135deg, #0eaba3 0%, #071a2e 100%); height: 110px; border-radius: 12px 12px 0 0;">
                    <div class="profile-picture text-center pt-4">
                      <div class="avatar avatar-xl">
                        <img src="assets/img/profile.jpg" alt="Profile" class="avatar-img rounded-circle border border-3 border-white shadow-sm" style="width: 84px; height: 84px; object-fit: cover;" />
                      </div>
                    </div>
                  </div>
                  <div class="card-body pt-5 text-center">
                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($current_user['name']) ?></h4>
                    <p class="text-muted mb-2">@<?= htmlspecialchars($current_user['username']) ?></p>
                    <span class="badge badge-success px-3 py-1 mb-3"><?= htmlspecialchars($current_user['role']) ?></span>

                    <hr />

                    <div class="text-start px-2">
                      <p class="mb-2"><strong><i class="fas fa-phone me-2 text-primary"></i> Phone:</strong> <?= htmlspecialchars($current_user['phone'] ?: 'Not Provided') ?></p>
                      <p class="mb-2"><strong><i class="fas fa-envelope me-2 text-primary"></i> Email:</strong> <?= htmlspecialchars($current_user['email'] ?: 'Not Provided') ?></p>
                      <p class="mb-2"><strong><i class="fas fa-user me-2 text-primary"></i> Age / Gender:</strong> <?= htmlspecialchars($current_user['age'] ? $current_user['age'] . ' yrs' : 'N/A') ?> · <?= htmlspecialchars($current_user['gender'] ?: 'Male') ?></p>
                      <p class="mb-0"><strong><i class="fas fa-calendar-alt me-2 text-primary"></i> Joined:</strong> <?= date('d M Y', strtotime($current_user['created_at'])) ?></p>
                    </div>

                    <hr />

                    <div class="text-start px-2">
                      <label class="text-muted small text-uppercase fw-bold mb-2">Active Color Scheme</label>
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <span>Sidebar:</span>
                        <span class="badge badge-secondary text-capitalize"><?= htmlspecialchars($active_sidebar) ?></span>
                      </div>
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <span>Header:</span>
                        <span class="badge badge-secondary text-capitalize"><?= htmlspecialchars($active_header) ?></span>
                      </div>
                      <div class="d-flex justify-content-between align-items-center">
                        <span>Footer:</span>
                        <span class="badge badge-secondary text-capitalize"><?= htmlspecialchars($active_footer) ?></span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Right Column: Settings Forms -->
              <div class="col-md-8">
                <!-- 1. Theme Color Customization Card -->
                <div class="card card-round mb-4">
                  <div class="card-header bg-light">
                    <div class="d-flex align-items-center justify-content-between">
                      <h4 class="card-title mb-0">
                        <i class="fas fa-palette text-primary me-2"></i> Custom Theme Colors (Sidebar, Header, Footer)
                      </h4>
                      <!-- <span class="badge badge-info">Auto-saves to SQLite</span> -->
                    </div>
                  </div>
                  <div class="card-body">
                    <form action="profile.php" method="POST">
                      <!-- 1.1 Sidebar Color Options -->
                      <div class="mb-4">
                        <label class="form-label fw-bold text-dark fs-6 mb-2">
                          1. Choose Sidebar Color:
                        </label>
                        <div class="row g-2">
                          <?php
                            $sb_options = [
                              'dark'   => ['label' => 'Midnight Black', 'color' => '#0b1a2d', 'desc' => 'Classic Dark'],
                              'white'  => ['label' => 'Clean White',    'color' => '#ffffff', 'desc' => 'Bright Light'],
                              'green'  => ['label' => 'Hospital Green', 'color' => '#0b5e43', 'desc' => 'Sufia Medical'],
                              'teal'   => ['label' => 'Healthcare Teal','color' => '#0eaba3', 'desc' => 'Modern Cyan'],
                              'blue'   => ['label' => 'Royal Blue',     'color' => '#1572e8', 'desc' => 'Clinical Blue'],
                              'purple' => ['label' => 'Medical Indigo', 'color' => '#5867dd', 'desc' => 'Royal Purple'],
                            ];
                            foreach ($sb_options as $key => $opt):
                              $is_checked = ($active_sidebar === $key);
                          ?>
                            <div class="col-sm-6 col-md-4">
                              <label class="color-card-option <?= $is_checked ? 'selected' : '' ?> w-100">
                                <input type="radio" name="sidebar_color" value="<?= $key ?>" <?= $is_checked ? 'checked' : '' ?> class="form-check-input mt-0" />
                                <span class="color-swatch-circle" style="background: <?= $opt['color'] ?>; <?= $key === 'white' ? 'border: 2px solid #cbd5e1;' : '' ?>"></span>
                                <div class="color-card-inner">
                                  <div class="text-dark small fw-bold"><?= $opt['label'] ?></div>
                                  <div class="text-muted" style="font-size: 0.72rem;"><?= $opt['desc'] ?></div>
                                </div>
                              </label>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      </div>

                      <!-- 1.2 Top Header Color Options -->
                      <div class="mb-4">
                        <label class="form-label fw-bold text-dark fs-6 mb-2">
                          2. Choose Top Header Color:
                        </label>
                        <div class="row g-2">
                          <?php
                            $hd_options = [
                              'white' => ['label' => 'Pure White',    'color' => '#ffffff'],
                              'dark'  => ['label' => 'Midnight Dark', 'color' => '#0b1a2d'],
                              'green' => ['label' => 'Emerald Green', 'color' => '#0b5e43'],
                              'teal'  => ['label' => 'Healthcare Teal','color' => '#0eaba3'],
                              'blue'  => ['label' => 'Royal Blue',    'color' => '#1572e8'],
                            ];
                            foreach ($hd_options as $key => $opt):
                              $is_checked = ($active_header === $key);
                          ?>
                            <div class="col-6 col-md">
                              <label class="color-card-option <?= $is_checked ? 'selected' : '' ?> w-100 p-2">
                                <input type="radio" name="header_color" value="<?= $key ?>" <?= $is_checked ? 'checked' : '' ?> class="form-check-input mt-0" />
                                <span class="color-swatch-circle" style="width: 24px; height: 24px; background: <?= $opt['color'] ?>; <?= $key === 'white' ? 'border: 1px solid #cbd5e1;' : '' ?>"></span>
                                <span class="text-dark small fw-semibold"><?= $opt['label'] ?></span>
                              </label>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      </div>

                      <!-- 1.3 Footer Color Options -->
                      <div class="mb-4">
                        <label class="form-label fw-bold text-dark fs-6 mb-2">
                          3. Choose Footer Color:
                        </label>
                        <div class="row g-2">
                          <?php
                            $ft_options = [
                              'white' => ['label' => 'White / Light',  'color' => '#ffffff'],
                              'dark'  => ['label' => 'Dark Slate',     'color' => '#0b1a2d'],
                              'green' => ['label' => 'Medical Green',  'color' => '#0b5e43'],
                              'teal'  => ['label' => 'Teal Accent',    'color' => '#0eaba3'],
                              'blue'  => ['label' => 'Royal Blue',     'color' => '#1572e8'],
                            ];
                            foreach ($ft_options as $key => $opt):
                              $is_checked = ($active_footer === $key);
                          ?>
                            <div class="col-6 col-md">
                              <label class="color-card-option <?= $is_checked ? 'selected' : '' ?> w-100 p-2">
                                <input type="radio" name="footer_color" value="<?= $key ?>" <?= $is_checked ? 'checked' : '' ?> class="form-check-input mt-0" />
                                <span class="color-swatch-circle" style="width: 24px; height: 24px; background: <?= $opt['color'] ?>; <?= $key === 'white' ? 'border: 1px solid #cbd5e1;' : '' ?>"></span>
                                <span class="text-dark small fw-semibold"><?= $opt['label'] ?></span>
                              </label>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      </div>

                      <div class="d-flex justify-content-end">
                        <button type="submit" name="update_theme" class="btn btn-primary btn-round px-4">
                          <i class="fas fa-save me-1"></i> Save Theme Colors
                        </button>
                      </div>
                    </form>
                  </div>
                </div>

                <!-- 2. Personal Profile Information Card -->
                <div class="card card-round">
                  <div class="card-header bg-light">
                    <h4 class="card-title mb-0">
                      <i class="fas fa-user-edit text-primary me-2"></i> Edit Personal Information
                    </h4>
                  </div>
                  <div class="card-body">
                    <form action="profile.php" method="POST">
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label fw-bold small text-muted">FULL NAME</label>
                          <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($current_user['name']) ?>" placeholder="Enter Your Full Name" />
                        </div>
                        <div class="col-md-6">
                          <label class="form-label fw-bold small text-muted">USERNAME (LOGIN ID)</label>
                          <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($current_user['username']) ?>" readonly disabled />
                          <small class="text-muted" style="font-size: 0.74rem;">Username cannot be changed.</small>
                        </div>

                        <div class="col-md-6">
                          <label class="form-label fw-bold small text-muted">PHONE NUMBER</label>
                          <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($current_user['phone'] ?? '') ?>" placeholder="Enter Your Phone Number" />
                        </div>
                        <div class="col-md-6">
                          <label class="form-label fw-bold small text-muted">EMAIL ADDRESS</label>
                          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($current_user['email'] ?? '') ?>" placeholder="Enter Your Email Address" />
                        </div>

                        <div class="col-md-6">
                          <label class="form-label fw-bold small text-muted">AGE</label>
                          <input type="number" name="age" min="18" max="100" class="form-control" value="<?= htmlspecialchars((string)($current_user['age'] ?? '30')) ?>" placeholder="Enter Your Age" />
                        </div>
                        <div class="col-md-6">
                          <label class="form-label fw-bold small text-muted">GENDER</label>
                          <select name="gender" class="form-select">
                            <option value="Male" <?= ($current_user['gender'] === 'Male') ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($current_user['gender'] === 'Female') ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($current_user['gender'] === 'Other') ? 'selected' : '' ?>>Other</option>
                          </select>
                        </div>

                        <div class="col-12">
                          <label class="form-label fw-bold small text-muted">CHANGE PASSWORD (LEAVE BLANK TO KEEP UNCHANGED)</label>
                          <input type="password" name="new_password" class="form-control" placeholder="••••••••" autocomplete="new-password" />
                        </div>
                      </div>

                      <div class="d-flex justify-content-end mt-4">
                        <button type="submit" name="update_profile" class="btn btn-success btn-round px-4">
                          <i class="fas fa-check me-1"></i> Update Profile Details
                        </button>
                      </div>
                    </form>
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

    <!-- Core JS Files (Untouched KaiAdmin Assets) -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>

    <script>
      // Highlight selected radio card option
      document.querySelectorAll('.color-card-option input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
          const groupName = this.getAttribute('name');
          document.querySelectorAll(`input[name="${groupName}"]`).forEach(r => {
            r.closest('.color-card-option').classList.remove('selected');
          });
          this.closest('.color-card-option').classList.add('selected');
        });
      });
    </script>
  </body>
</html>
