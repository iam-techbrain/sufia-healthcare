<?php
session_start();
require_once __DIR__ . '/db.php';

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['sufia_admin_logged_in']);
    unset($_SESSION['sufia_admin_user']);
    session_destroy();
    header("Location: login.php?logged_out=1");
    exit;
}

// Redirect if already logged in
if (isset($_SESSION['sufia_admin_logged_in']) && $_SESSION['sufia_admin_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_btn'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error_msg = "Please enter both username and password.";
    } else {
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && $admin['password'] === $password) {
                $_SESSION['sufia_admin_logged_in'] = true;
                $_SESSION['sufia_admin_user']      = $admin['username'];
                $_SESSION['sufia_user_id']         = $admin['id'];
                $_SESSION['sufia_user_name']       = $admin['name'];
                $_SESSION['sufia_user_email']      = $admin['email'];
                $_SESSION['sufia_admin_role']      = $admin['role'] ?? 'Hospital Administrator';
                $_SESSION['sufia_sidebar_color']   = $admin['sidebar_color'] ?? 'dark';
                $_SESSION['sufia_header_color']    = $admin['header_color'] ?? 'white';
                $_SESSION['sufia_footer_color']    = $admin['footer_color'] ?? 'white';
                $_SESSION['sufia_login_time']      = date('Y-m-d H:i:s');

                header("Location: index.php");
                exit;
            } else {
                $error_msg = "Invalid username or password. Please try again.";
            }
        } catch (Exception $e) {
            $error_msg = "Database Error: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Portal Login | Sufia Healthcare</title>
    <meta name="robots" content="noindex, nofollow" />

    <link rel="icon" type="image/x-icon" href="../assets/img/favicon.ico" />
    <link rel="stylesheet" href="../css/premium.css" />

    <style>
      body.admin-login-body {
        background: radial-gradient(circle at 50% 30%, #0c2d4f 0%, #071a2e 100%);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 24px;
        margin: 0;
        font-family: var(--font-body, "Inter", sans-serif);
        color: #ffffff;
      }

      .admin-card {
        background: #ffffff;
        border-radius: 20px;
        width: 100%;
        max-width: 440px;
        padding: 40px 36px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
        color: var(--ink, #0e1a2b);
      }

      .admin-header {
        text-align: center;
        margin-bottom: 28px;
      }

      .admin-header img {
        height: 52px;
        margin: 0 auto 16px;
      }

      .admin-header h1 {
        font-size: 1.35rem;
        margin: 0 0 6px;
        color: var(--navy-950, #071a2e);
        font-weight: 700;
      }

      .admin-header p {
        font-size: 0.85rem;
        color: var(--slate, #647186);
        margin: 0;
      }

      .admin-alert {
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 0.88rem;
        margin-bottom: 20px;
      }

      .admin-alert-danger {
        background: #fdecec;
        border: 1px solid #f5c2c0;
        color: #b42318;
      }

      .admin-alert-info {
        background: #e8f7ee;
        border: 1px solid #b7e4c7;
        color: #187a3d;
      }

      .form-group {
        margin-bottom: 18px;
      }

      .form-group label {
        display: block;
        font-size: 0.84rem;
        font-weight: 600;
        color: var(--navy-900, #0b2545);
        margin-bottom: 6px;
      }

      .form-input {
        width: 100%;
        padding: 12px 14px;
        font-size: 0.94rem;
        border: 1px solid var(--line, #e3e9ef);
        border-radius: 8px;
        font-family: inherit;
        transition: border-color 0.2s, box-shadow 0.2s;
        box-sizing: border-box;
      }

      .form-input:focus {
        border-color: var(--cyan-500, #0eaba3);
        outline: none;
        box-shadow: 0 0 0 3px rgba(14, 171, 163, 0.15);
      }

      .btn-admin {
        width: 100%;
        padding: 13px;
        background: linear-gradient(135deg, var(--cyan-500, #0eaba3) 0%, var(--cyan-600, #0a8f8a) 100%);
        color: #ffffff;
        border: none;
        border-radius: 8px;
        font-size: 0.95rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-top: 8px;
      }

      .btn-admin:hover {
        background: linear-gradient(135deg, #0cbdb4 0%, #0b9e99 100%);
        box-shadow: 0 4px 14px rgba(14, 171, 163, 0.35);
        transform: translateY(-1px);
      }

      .credentials-hint {
        margin-top: 24px;
        background: var(--mist, #f5f8fa);
        border-radius: 8px;
        padding: 12px 14px;
        font-size: 0.78rem;
        color: var(--slate, #647186);
        border: 1px solid var(--line, #e3e9ef);
        line-height: 1.5;
      }

      .credentials-hint code {
        background: #ffffff;
        padding: 2px 6px;
        border-radius: 4px;
        border: 1px solid var(--line, #e3e9ef);
        color: var(--navy-900, #0b2545);
        font-weight: 600;
      }

      .admin-footer-links {
        margin-top: 20px;
        text-align: center;
        font-size: 0.82rem;
      }

      .admin-footer-links a {
        color: rgba(255, 255, 255, 0.75);
        text-decoration: none;
        transition: color 0.2s;
      }

      .admin-footer-links a:hover {
        color: var(--cyan-400, #3fc7bd);
        text-decoration: underline;
      }
    </style>
  </head>

  <body class="admin-login-body">
    <div class="admin-card">
      <div class="admin-header">
        <img src="../assets/img/logo-sufia.png" alt="Sufia Healthcare" />
        <h1>Hospital Admin Portal</h1>
      </div>

      <?php if (!empty($error_msg)): ?>
        <div class="admin-alert admin-alert-danger">
          <?= htmlspecialchars($error_msg) ?>
        </div>
      <?php endif; ?>

      <?php if (isset($_GET['logged_out'])): ?>
        <div class="admin-alert admin-alert-info">
          You have been logged out securely.
        </div>
      <?php endif; ?>

      <form action="login.php" method="POST">
        <div class="form-group">
          <label for="username">Admin Username</label>
          <input
            type="text"
            id="username"
            name="username"
            class="form-input"
            required
            placeholder="Enter Username or Email"
            autocomplete="username"
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
          />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input
            type="password"
            id="password"
            name="password"
            class="form-input"
            required
            placeholder="••••••••"
            autocomplete="current-password"
          />
        </div>

        <button type="submit" name="login_btn" class="btn-admin">
          Sign In to Admin Dashboard →
        </button>
      </form>

      <div class="credentials-hint" hidden>
        <strong>Default SQLite Credentials:</strong><br />
        Username: <code>admin</code> &nbsp;|&nbsp; Password: <code>admin123</code><br />
        Alternative: <code>sufia</code> &nbsp;|&nbsp; Password: <code>sufia@2026</code>
      </div>
    </div>

    <div class="admin-footer-links">
      <a href="../index.html">← Back to Sufia Healthcare Website</a> &nbsp;·&nbsp;
      <a href="../careers.php">View Careers Page</a>
    </div>
  </body>
</html>
