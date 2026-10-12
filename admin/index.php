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
$active_sidebar = $current_user['sidebar_color'] ?? 'dark';
$active_header  = $current_user['header_color'] ?? 'white';
$active_footer  = $current_user['footer_color'] ?? 'white';
$total_users    = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// --- 1. Career Applications Stats ---
$total_apps   = (int)$pdo->query("SELECT COUNT(*) FROM career_applications")->fetchColumn();
$new_apps     = (int)$pdo->query("SELECT COUNT(*) FROM career_applications WHERE status = 'New'")->fetchColumn();
$in_rev_apps  = (int)$pdo->query("SELECT COUNT(*) FROM career_applications WHERE status = 'In Review'")->fetchColumn();
$short_apps   = (int)$pdo->query("SELECT COUNT(*) FROM career_applications WHERE status = 'Shortlisted'")->fetchColumn();
$interv_apps  = (int)$pdo->query("SELECT COUNT(*) FROM career_applications WHERE status = 'Interviewed'")->fetchColumn();
$rej_apps     = (int)$pdo->query("SELECT COUNT(*) FROM career_applications WHERE status = 'Rejected'")->fetchColumn();
$today_apps   = (int)$pdo->query("SELECT COUNT(*) FROM career_applications WHERE date(applied_at) = date('now', 'localtime')")->fetchColumn();

// --- 2. Contact Inquiries Stats ---
$total_inq   = (int)$pdo->query("SELECT COUNT(*) FROM contact_inquiries")->fetchColumn();
$new_inq     = (int)$pdo->query("SELECT COUNT(*) FROM contact_inquiries WHERE status = 'New'")->fetchColumn();
$prog_inq    = (int)$pdo->query("SELECT COUNT(*) FROM contact_inquiries WHERE status = 'In Progress'")->fetchColumn();
$resp_inq    = (int)$pdo->query("SELECT COUNT(*) FROM contact_inquiries WHERE status = 'Responded'")->fetchColumn();
$resolv_inq  = (int)$pdo->query("SELECT COUNT(*) FROM contact_inquiries WHERE status = 'Resolved'")->fetchColumn();
$today_inq   = (int)$pdo->query("SELECT COUNT(*) FROM contact_inquiries WHERE date(created_at) = date('now', 'localtime')")->fetchColumn();

// --- 3. Recent 5 Applications ---
$latest_apps = $pdo->query("SELECT * FROM career_applications ORDER BY id DESC LIMIT 5")->fetchAll();

// --- 4. Recent 5 Contact Inquiries ---
$latest_inq = $pdo->query("SELECT * FROM contact_inquiries ORDER BY id DESC LIMIT 5")->fetchAll();

// Monthly Trend data for chart (Last 6 Months)
$months = [];
$monthly_apps = [];
$monthly_inqs = [];

for ($i = 5; $i >= 0; $i--) {
    $m_label = date('M Y', strtotime("-$i months"));
    $m_format = date('Y-m', strtotime("-$i months"));
    $months[] = $m_label;

    $app_m = $pdo->prepare("SELECT COUNT(*) FROM career_applications WHERE strftime('%Y-%m', applied_at) = ?");
    $app_m->execute([$m_format]);
    $monthly_apps[] = (int)$app_m->fetchColumn();

    $inq_m = $pdo->prepare("SELECT COUNT(*) FROM contact_inquiries WHERE strftime('%Y-%m', created_at) = ?");
    $inq_m->execute([$m_format]);
    $monthly_inqs[] = (int)$inq_m->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Sufia Healthcare - Hospital Admin Dashboard</title>
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

    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="assets/css/demo.css" />

    <!-- Dynamic User Theme Styles -->
    <?= renderThemeStyles($active_sidebar, $active_header, $active_footer) ?>
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
              <li class="nav-item active">
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
                <div class="input-group">
                  <div class="input-group-prepend">
                    <button type="submit" class="btn btn-search pe-1">
                      <i class="fa fa-search search-icon"></i>
                    </button>
                  </div>
                  <input
                    type="text"
                    placeholder="Search candidate, phone, patient..."
                    class="form-control"
                    id="topNavSearch"
                  />
                </div>
              </nav>

              <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                <li class="nav-item">
                  <span class="badge badge-success px-3 py-2">🟢 Siwan Campus Active</span>
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
            <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
              <div>
                <h3 class="fw-bold mb-3">Sufia Healthcare - Hospital Command Dashboard</h3>
                <h6 class="op-7 mb-2">Real-time reports for Career Applications &amp; Patient Treatment Enquiries</h6>
              </div>
            </div>

            <!-- Top 4 KPI Stat Cards (KaiAdmin Card Stats) -->
            <div class="row">
              <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-icon">
                        <div class="icon-big text-center icon-primary bubble-shadow-small">
                          <i class="fas fa-user-md"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">Career Applications</p>
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
                          <i class="fas fa-clock"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">Pending Resumes</p>
                          <h4 class="card-title"><?= number_format($new_apps) ?></h4>
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
                          <i class="fas fa-envelope"></i>
                        </div>
                      </div>
                      <div class="col col-stats ms-3 ms-sm-0">
                        <div class="numbers">
                          <p class="card-category">Patient Enquiries</p>
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
                          <p class="card-category">Urgent Callbacks</p>
                          <h4 class="card-title"><?= number_format($new_inq) ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Charts Section (Monthly Trends & Status Breakdown) -->
            <div class="row">
              <div class="col-md-8">
                <div class="card card-round">
                  <div class="card-header">
                    <div class="card-head-row">
                      <div class="card-title">Enquiry &amp; Application Trends (Last 6 Months)</div>
                      <div class="card-tools">
                        <span class="badge badge-info me-2">Applications</span>
                        <span class="badge badge-success">Enquiries</span>
                      </div>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="chart-container" style="min-height: 350px">
                      <canvas id="monthlyTrendChart"></canvas>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="card card-round">
                  <div class="card-header">
                    <div class="card-title">Enquiries Status Breakdown</div>
                    <div class="card-category">Real-time resolution status</div>
                  </div>
                  <div class="card-body">
                    <div class="chart-container" style="min-height: 250px">
                      <canvas id="statusDoughnutChart"></canvas>
                    </div>
                    <div class="mt-3">
                      <div class="d-flex justify-content-between mb-1">
                        <span><i class="fas fa-circle text-danger me-1"></i> New / Pending</span>
                        <strong><?= $new_inq ?></strong>
                      </div>
                      <div class="d-flex justify-content-between mb-1">
                        <span><i class="fas fa-circle text-warning me-1"></i> In Progress</span>
                        <strong><?= $prog_inq ?></strong>
                      </div>
                      <div class="d-flex justify-content-between mb-1">
                        <span><i class="fas fa-circle text-success me-1"></i> Responded</span>
                        <strong><?= $resp_inq ?></strong>
                      </div>
                      <div class="d-flex justify-content-between">
                        <span><i class="fas fa-circle text-primary me-1"></i> Resolved</span>
                        <strong><?= $resolv_inq ?></strong>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Dual Recent Activities Tables -->
            <div class="row">
              <!-- Career Applications Recent 5 -->
              <div class="col-md-6">
                <div class="card card-round">
                  <div class="card-header">
                    <div class="card-head-row card-tools-still-right">
                      <div class="card-title">Recent Career Applications</div>
                      <div class="card-tools">
                        <a href="career_enquiries.php" class="btn btn-sm btn-label-primary btn-round">View All →</a>
                      </div>
                    </div>
                  </div>
                  <div class="card-body p-0">
                    <div class="table-responsive">
                      <table class="table align-items-center mb-0">
                        <thead class="thead-light">
                          <tr>
                            <th>Candidate</th>
                            <th>Position</th>
                            <th>Status</th>
                            <th>Applied</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php if (empty($latest_apps)): ?>
                            <tr><td colspan="4" class="text-center text-muted p-4">No applications received yet.</td></tr>
                          <?php else: ?>
                            <?php foreach ($latest_apps as $app): 
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
                                <td>
                                  <strong><?= htmlspecialchars($app['name']) ?></strong>
                                  <div class="text-muted small">📞 <?= htmlspecialchars($app['phone']) ?></div>
                                </td>
                                <td><span class="small font-weight-bold"><?= htmlspecialchars($app['position']) ?></span></td>
                                <td><span class="badge <?= $badge_class ?>"><?= htmlspecialchars($app['status']) ?></span></td>
                                <td class="text-muted small"><?= date('d M, h:i A', strtotime($app['applied_at'])) ?></td>
                              </tr>
                            <?php endforeach; ?>
                          <?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Contact Enquiries Recent 5 -->
              <div class="col-md-6">
                <div class="card card-round">
                  <div class="card-header">
                    <div class="card-head-row card-tools-still-right">
                      <div class="card-title">Recent Patient Enquiries</div>
                      <div class="card-tools">
                        <a href="contact_enquiries.php" class="btn btn-sm btn-label-success btn-round">View All →</a>
                      </div>
                    </div>
                  </div>
                  <div class="card-body p-0">
                    <div class="table-responsive">
                      <table class="table align-items-center mb-0">
                        <thead class="thead-light">
                          <tr>
                            <th>Patient</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Submitted</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php if (empty($latest_inq)): ?>
                            <tr><td colspan="4" class="text-center text-muted p-4">No enquiries received yet.</td></tr>
                          <?php else: ?>
                            <?php foreach ($latest_inq as $inq): 
                              $badge_class = match($inq['status']) {
                                'New' => 'badge-danger',
                                'In Progress' => 'badge-warning',
                                'Responded' => 'badge-success',
                                'Resolved' => 'badge-primary',
                                default => 'badge-info'
                              };
                            ?>
                              <tr>
                                <td>
                                  <strong><?= htmlspecialchars($inq['name']) ?></strong>
                                  <div class="text-muted small">📞 <?= htmlspecialchars($inq['phone']) ?></div>
                                </td>
                                <td><span class="badge badge-count"><?= htmlspecialchars($inq['department'] ?: 'General') ?></span></td>
                                <td><span class="badge <?= $badge_class ?>"><?= htmlspecialchars($inq['status']) ?></span></td>
                                <td class="text-muted small"><?= date('d M, h:i A', strtotime($inq['created_at'])) ?></td>
                              </tr>
                            <?php endforeach; ?>
                          <?php endif; ?>
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

    <!-- Core JS Files -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>

    <!-- jQuery Scrollbar -->
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

    <!-- Chart JS -->
    <script src="assets/js/plugin/chart.js/chart.min.js"></script>

    <!-- kaiadmin JS -->
    <script src="assets/js/kaiadmin.min.js"></script>

    <script>
      // 1. Line / Bar Chart for Monthly Trends
      const ctxTrend = document.getElementById('monthlyTrendChart').getContext('2d');
      new Chart(ctxTrend, {
        type: 'line',
        data: {
          labels: <?= json_encode($months) ?>,
          datasets: [
            {
              label: 'Career Applications',
              borderColor: '#177dff',
              pointBackgroundColor: '#177dff',
              pointRadius: 4,
              backgroundColor: 'rgba(23, 125, 255, 0.1)',
              legendColor: '#177dff',
              fill: true,
              borderWidth: 2.5,
              data: <?= json_encode($monthly_apps) ?>
            },
            {
              label: 'Patient Enquiries',
              borderColor: '#31ce36',
              pointBackgroundColor: '#31ce36',
              pointRadius: 4,
              backgroundColor: 'rgba(49, 206, 54, 0.1)',
              legendColor: '#31ce36',
              fill: true,
              borderWidth: 2.5,
              data: <?= json_encode($monthly_inqs) ?>
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          legend: {
            position: 'top'
          },
          tooltips: {
            bodySpacing: 4,
            mode: 'nearest',
            intersect: 0,
            position: 'nearest',
            xPadding: 10,
            yPadding: 10,
            caretPadding: 10
          },
          scales: {
            yAxes: [{
              ticks: {
                beginAtZero: true,
                stepSize: 1
              }
            }]
          }
        }
      });

      // 2. Status Doughnut Chart
      const ctxDoughnut = document.getElementById('statusDoughnutChart').getContext('2d');
      new Chart(ctxDoughnut, {
        type: 'doughnut',
        data: {
          labels: ['New / Pending', 'In Progress', 'Responded', 'Resolved'],
          datasets: [{
            data: [<?= $new_inq ?>, <?= $prog_inq ?>, <?= $resp_inq ?>, <?= $resolv_inq ?>],
            backgroundColor: ['#f25961', '#ffad46', '#31ce36', '#177dff'],
            borderWidth: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          legend: {
            display: false
          }
        }
      });

      // Quick Search redirect
      const searchBox = document.getElementById('topNavSearch');
      if (searchBox) {
        searchBox.addEventListener('keypress', function(e) {
          if (e.key === 'Enter') {
            const query = encodeURIComponent(this.value.trim());
            if (query) {
              window.location.href = 'career_enquiries.php?search=' + query;
            }
          }
        });
      }
    </script>
  </body>
</html>
